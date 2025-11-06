<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Illuminate\Support\Facades\Log;
use Exception; // Importa la clase Exception
use App\Almacen;
use App\Articulo;
use App\Inventario;

class InventarioImport implements ToCollection
{
    private $almacenMapping;
    private $articuloMapping;

    private $errors = [];

    public function __construct()
    {
        $this->almacenMapping = $this->createAlmacenMapping();
        $this->articuloMapping = $this->createArticuloMapping();

    }

    private function createAlmacenMapping()
    {
        return Almacen::pluck('nombre_almacen', 'id')->toArray();
    }
    private function createArticuloMapping()
    {
        return Articulo::pluck('codigo', 'id')->toArray();
    }  
    public function collection(Collection $rows)
    {
        $rowNumber = 1;
        $importacionExitosa = true;
        try {
            \DB::beginTransaction();
    
            foreach ($rows as $row) {
                $idAlmacen = $this->getAlmacenId($row[0]);
                $idArticulo = $this->getArticuloId($row[1]);
                try {
                    $fechaVencimiento = $this->normalizarFecha($row[2] ?? null);
                    // Buscar si ya existe un registro con mismo almacen, articulo y fecha_vencimiento
                    $inventarioExistente = Inventario::where('idalmacen', $idAlmacen)
                        ->where('idarticulo', $idArticulo)
                        ->where('fecha_vencimiento', $fechaVencimiento)
                        ->first();
                    if ($inventarioExistente) {
                        // Sumar saldo_stock
                        $inventarioExistente->saldo_stock += $row[3];
                        $inventarioExistente->cantidad += $row[3];
                        $inventarioExistente->save();
                    } else {
                        Inventario::create([
                            'idalmacen' => $idAlmacen,
                            'idarticulo' => $idArticulo,
                            'fecha_vencimiento' => $fechaVencimiento,
                            'saldo_stock' => $row[3],
                            'cantidad'=> $row[3],
                        ]);
                    } 
                } catch (Exception $e) {
                    if (!$idAlmacen) {
                        $this->errors[] = "Error fila $rowNumber: No existe 'El almacen $row[0]'";
                    } else if (!$idArticulo) {
                        $this->errors[] = "Error fila $rowNumber: No se encontró 'Articulo $row[1]' en la base de datos";
                    } else {
                        $this->errors[] = "Error al procesar fila: " . $e->getMessage();
                    }
    
                    $importacionExitosa = false;
                }
    
                $rowNumber++;
            }
    
            if ($importacionExitosa) {
                \DB::commit(); // Confirmar la transacción si no hay errores
            } else {
                \DB::rollBack(); // Revertir la transacción en caso de error
            }
        } catch (Exception $e) {
            \DB::rollBack(); // Revertir la transacción en caso de error
            $importacionExitosa = false;
            $this->errors[] = "Error al procesar fila: " . $e->getMessage();
        }
        return $this->getErrorsResponse($importacionExitosa);
    }
    

    
    private function getArticuloId($nombreArticulo)
    {
        $idArticulo = array_search($nombreArticulo, $this->articuloMapping);

        return $idArticulo !== false ? $idArticulo : null;
    }

    private function getAlmacenId($nombreAlmacen)
{
    $nombreAlmacen = trim(strtolower($nombreAlmacen));
    foreach ($this->almacenMapping as $id => $nombre) {
        if (trim(strtolower($nombre)) === $nombreAlmacen) {
            return $id;
        }
    }
    return null;
}

    public function getErrors()
    {
        return $this->errors ?? [];
    }

    private function getErrorsResponse($importacionExitosa)
    {
        if (!$importacionExitosa) {
            return response()->json(['errors' => $this->errors], 422);
        } else {
            return response()->json(['mensaje' => 'Importación exitosa'], 200);
        }
    }
 private function normalizarFecha($fecha)
    {
        if (!$fecha || strtolower($fecha) === 'null') {
            return '2099-01-01';
        }

        // Si es un número (posible serial de Excel)
        if (is_numeric($fecha)) {
            // Excel serial date to Y-m-d
            // 25569 is the number of days between 1899-12-30 and 1970-01-01
            $unixDate = ($fecha - 25569) * 86400;
            return gmdate('Y-m-d', $unixDate);
        }

        // Si ya está en formato yyyy-mm-dd
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return $fecha;
        }
        // dd/mm/yyyy o dd-mm-yyyy
        if (preg_match('/^(\d{2})[\/\-](\d{2})[\/\-](\d{4})$/', $fecha, $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        // yyyy/mm/dd o yyyy.mm.dd
        if (preg_match('/^(\d{4})[\/\.](\d{2})[\/\.](\d{2})$/', $fecha, $m)) {
            return "{$m[1]}-{$m[2]}-{$m[3]}";
        }
        // mm/dd/yyyy
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $fecha, $m)) {
            // Si el mes es > 12, probablemente es dd/mm/yyyy, ya cubierto arriba
            if ((int) $m[1] > 12) {
                return "{$m[3]}-{$m[2]}-{$m[1]}";
            }
            return "{$m[3]}-{$m[1]}-{$m[2]}";
        }

        // Intentar con Carbon si está disponible
        try {
            return \Carbon\Carbon::parse($fecha)->format('Y-m-d');
        } catch (\Exception $e) {
            // Si falla, retorna la fecha por defecto
            return '2099-01-01';
        }
    }
}