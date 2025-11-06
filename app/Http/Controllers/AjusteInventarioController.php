<?php

namespace App\Http\Controllers;

use App\AjusteInvetario;
use App\Inventario;
use App\TipoBajas;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AjusteInventarioController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        if ($buscar == '') {
            $ajuste = AjusteInvetario::join('articulos', 'ajuste_invetarios.producto', '=', 'articulos.id')
                ->join('tipo_bajas', 'ajuste_invetarios.idtipobajas', '=', 'tipo_bajas.id')
                ->join('almacens', 'ajuste_invetarios.almacen', '=', 'almacens.id')
                ->select(
                    'ajuste_invetarios.*',
                    'articulos.nombre as nombre',
                    'tipo_bajas.nombre as tipo',
                    'almacens.nombre_almacen as nombre_almacen'
                )
                ->orderBy('id', 'desc')->paginate(10);
        } else {
            $ajuste = AjusteInvetario::join('articulos', 'ajuste_invetarios.producto', '=', 'articulos.id')
                ->join('tipo_bajas', 'ajuste_invetarios.idtipobajas', '=', 'tipo_bajas.id')
                ->join('almacens', 'ajuste_invetarios.almacen', '=', 'almacens.id')
                ->select(
                    'ajuste_invetarios.*',
                    'articulos.nombre as nombre',
                    'tipo_bajas.nombre as tipo',
                    'almacens.nombre_almacen as nombre_almacen'
                )
                ->when($criterio == '', function ($query) use ($buscar) {
                    $query->where(function ($q) use ($buscar) {
                        $q->where('articulos.nombre', 'like', "%$buscar%")
                            ->orWhere('tipo_bajas.nombre', 'like', "%$buscar%")
                            ->orWhere('almacens.nombre_almacen', 'like', "%$buscar%")
                            ->orWhere('ajuste_invetarios.cantidad', 'like', "%$buscar%")
                            ->orWhere('ajuste_invetarios.created_at', 'like', "%$buscar%");
                    });
                }, function ($query) use ($criterio, $buscar) {
                    $query->where($criterio, 'like', "%$buscar%");
                })
                ->orderBy('id', 'desc')->paginate(10);
        }
        return [
            'pagination' => [
                'total' => $ajuste->total(),
                'current_page' => $ajuste->currentPage(),
                'per_page' => $ajuste->perPage(),
                'last_page' => $ajuste->lastPage(),
                'from' => $ajuste->firstItem(),
                'to' => $ajuste->lastItem(),
            ],
            'ajuste' => $ajuste
        ];
    }

    public function listarMotivo(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $buscar = $request->buscar;
        $criterio = $request->criterio;

        if ($buscar == '') {
            $motivo = TipoBajas::orderBy('id', 'desc')->paginate(6);
        } else {
            $motivo = TipoBajas::where('nombre', 'like', '%' . $buscar . '%')->orderBy('id', 'desc')->paginate(6);
        }


        return [
            'pagination' => [
                'total' => $motivo->total(),
                'current_page' => $motivo->currentPage(),
                'per_page' => $motivo->perPage(),
                'last_page' => $motivo->lastPage(),
                'from' => $motivo->firstItem(),
                'to' => $motivo->lastItem(),
            ],
            'motivos' => $motivo
        ];
    }
    //nuevo codigo para obtener el stock actual añadido en fecha 13/03/25
    public function obtenerStock(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        // Validamos que se envíe el id del producto y del almacén
        $producto = $request->producto;
        $almacen = $request->almacen;

        // Buscar la suma del stock total del producto en el almacén
        $stockTotal = Inventario::where('idarticulo', $producto)
            ->where('idalmacen', $almacen)
            ->sum('saldo_stock'); // Sumamos el saldo_stock para todos los registros que coincidan

        // Retornar el stock total o 0 si no existe en el inventario
        return response()->json(['stock_actual' => $stockTotal ?: 0]);
    }



    public function store(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $ajuste = new AjusteInvetario();
        $ajuste->cantidad = $request->cantidad;
        $ajuste->idtipobajas = $request->idtipobaja;
        $ajuste->producto = $request->producto;
        $ajuste->almacen = $request->idAlmacenSeleccionado;

        $ajuste->save();

        $detalle = [
            'cantidad' => $ajuste->cantidad,
            'idarticulo' => $ajuste->producto
        ];
        $this->actualizarInventario($ajuste->almacen, $detalle);

        return ['idArticulo' => $ajuste->id];
    }

    public function registrarMultiple(Request $request)
    {
        if (!$request->ajax()) {
            return redirect('/');
        }

        // Validación de datos recibidos
        $request->validate([
            'almacen_id' => 'required|integer',
            'motivo_id' => 'required|integer',
            'productos' => 'required|array|min:1',
            'productos.*.producto_id' => 'required|integer',
            'productos.*.cantidad' => 'required|integer|min:1',
            'productos.*.stock_anterior' => 'required|integer|min:0'
        ]);

        DB::beginTransaction();
        
        try {
            $ajustesCreados = [];
            $totalProductos = 0;
            $totalUnidades = 0;

            foreach ($request->productos as $productoData) {
                // Verificar que la cantidad no sea mayor al stock actual
                $stockActual = Inventario::where('idarticulo', $productoData['producto_id'])
                    ->where('idalmacen', $request->almacen_id)
                    ->sum('saldo_stock');

                if ($productoData['cantidad'] > $stockActual) {
                    throw new \Exception("La cantidad a ajustar ({$productoData['cantidad']}) es mayor al stock disponible ({$stockActual}) para el producto ID: {$productoData['producto_id']}");
                }

                // Crear el registro de ajuste
                $ajuste = new AjusteInvetario();
                $ajuste->cantidad = $productoData['cantidad'];
                $ajuste->idtipobajas = $request->motivo_id;
                $ajuste->producto = $productoData['producto_id'];
                $ajuste->almacen = $request->almacen_id;
                $ajuste->save();

                // Actualizar el inventario
                $detalle = [
                    'cantidad' => $ajuste->cantidad,
                    'idarticulo' => $ajuste->producto
                ];
                $this->actualizarInventario($ajuste->almacen, $detalle);

                $ajustesCreados[] = $ajuste->id;
                $totalProductos++;
                $totalUnidades += $productoData['cantidad'];
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Ajuste múltiple procesado correctamente",
                'data' => [
                    'ajustes_creados' => $ajustesCreados,
                    'total_productos' => $totalProductos,
                    'total_unidades_ajustadas' => $totalUnidades
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el ajuste múltiple: ' . $e->getMessage()
            ], 500);
        }
    }

    private function actualizarInventario($idAlmacen, $detalle)
    {
        $cantidadRestante = $detalle['cantidad'];
        $fechaActual = now();

        // Obtener los inventarios ordenados por fecha de vencimiento más reciente (de mayor a menor)
        $inventarios = Inventario::where('idalmacen', $idAlmacen)
            ->where('idarticulo', $detalle['idarticulo'])
            ->orderBy('fecha_vencimiento') // Ordenamos de la fecha más reciente a la más antigua
            ->get();

        foreach ($inventarios as $inventario) {
            if ($cantidadRestante <= 0) {
                break; // Si ya no queda cantidad por reducir, salimos del bucle
            }

            if ($inventario->saldo_stock >= $cantidadRestante) {
                // Si el inventario tiene suficiente stock, disminuimos la cantidad restante
                $inventario->saldo_stock -= $cantidadRestante;
                $cantidadRestante = 0; // Ya no queda cantidad restante
            } else {
                // Si el inventario no tiene suficiente stock, reducimos todo lo que pueda
                $cantidadRestante -= $inventario->saldo_stock;
                $inventario->saldo_stock = 0; // Ponemos el saldo a 0
            }
            // Guardar los cambios en el inventario
            $inventario->save();
        }

        // Si aún queda cantidad restante, se podría manejar un mensaje o algo similar, dependiendo del caso
        if ($cantidadRestante > 0) {
            // Acción en caso de que no se haya podido restar todo el stock (por ejemplo, lanzar una excepción o loguear un mensaje)
        }
    }

    public function registrarMotivo(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $motivo = new TipoBajas();

        $motivo->nombre = $request->nombre;
        $motivo->save();
    }
}
