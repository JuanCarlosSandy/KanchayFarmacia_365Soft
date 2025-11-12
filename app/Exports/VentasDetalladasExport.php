<?php
namespace App\Exports;

use App\Venta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;
use Illuminate\Support\Collection;

class VentasDetalladasExport implements FromCollection, WithHeadings, WithColumnWidths, WithStyles, WithEvents
{
    protected $filters;
    protected $resumenRows = [];
    protected $totalVentasRegistradas = 0;
    protected $cachedCollection; // Nueva propiedad para cachear la colección

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
        // Si ya tenemos la colección cacheada, la retornamos
        if ($this->cachedCollection) {
            return $this->cachedCollection;
        }

        $query = Venta::with(['detalles.producto', 'usuario.persona', 'cliente']);

        if (!empty($this->filters['sucursal']) && $this->filters['sucursal'] !== 'undefined') {
            $query->whereHas('usuario', function ($q) {
                $q->where('idsucursal', $this->filters['sucursal']);
            });
        }

        if (!empty($this->filters['tipoReporte'])) {
            if ($this->filters['tipoReporte'] === 'dia' && !empty($this->filters['fechaSeleccionada'])) {
                $inicio = $this->filters['fechaSeleccionada'] . ' 00:00:00';
                $fin = $this->filters['fechaSeleccionada'] . ' 23:59:59';
                $query->whereBetween('fecha_hora', [$inicio, $fin]);
            } else if ($this->filters['tipoReporte'] === 'mes' && !empty($this->filters['mesSeleccionado'])) {
                $mesSeleccionado = $this->filters['mesSeleccionado'];
                $inicio = $mesSeleccionado . '-01 00:00:00';
                $fin = date('Y-m-t', strtotime($mesSeleccionado . '-01')) . ' 23:59:59';
                $query->whereBetween('fecha_hora', [$inicio, $fin]);
            }
        }

        if (!empty($this->filters['estadoVenta']) && $this->filters['estadoVenta'] !== 'Todos' && $this->filters['estadoVenta'] !== 'undefined') {
            $query->where('estado', $this->filters['estadoVenta']);
        }

        if (!empty($this->filters['idcliente']) && $this->filters['idcliente'] !== 'undefined') {
            $query->where('idcliente', $this->filters['idcliente']);
        }

        $ventas = $query->orderBy('fecha_hora', 'asc')->get();

        $rows = new Collection();
        $line = 2;

        foreach ($ventas as $venta) {
            $estado = $venta->estado == 1 ? 'Registrado' : 'Anulado';

            // Guardar la fila de resumen y su estado
            $this->resumenRows[$line] = $estado;

            // Fila resumen de la venta
            $rows->push([
                'Venta N°: ' . $venta->num_comprobante,
                'Fecha: ' . date('d/m/Y H:i', strtotime($venta->fecha_hora)),
                'Cliente: ' . mb_strimwidth($venta->cliente->nombre ?? 'S/N', 0, 40, '...'),
                'Vendedor: ' . ($venta->usuario->persona->nombre ?? 'S/N'),
                'Total: ' . number_format($venta->total, 2),
                'Estado: ' . $estado,
                '', '', '', ''
            ]);

            $line += 1;

            // Cabecera de detalle
            $rows->push([
                'Producto', 'Cantidad', 'Precio', 'Descuento', 'Subtotal', '', '', '', ''
            ]);

            $line += 1;

            // Detalles - SOLO SUMAR SUBTOTALES DE VENTAS REGISTRADAS
            foreach ($venta->detalles as $d) {
                $subtotal = ($d->precio * $d->cantidad) - $d->descuento;
                
                // SOLO SUMAR SI LA VENTA ESTÁ REGISTRADA
                if ($venta->estado == 1) {
                    $this->totalVentasRegistradas += $subtotal;
                }
                
                $rows->push([
                    mb_strimwidth($d->producto->nombre ?? '-', 0, 40, '...'),
                    $d->cantidad,
                    number_format($d->precio, 2),
                    number_format($d->descuento, 2),
                    number_format($subtotal, 2),
                    '', '', '', ''
                ]);
                $line += 1;
            }

            // Línea vacía
            $rows->push(['', '', '', '', '', '', '', '', '']);
            $line += 1;
        }

        // Cachear la colección para evitar procesamiento duplicado
        $this->cachedCollection = $rows;
        return $this->cachedCollection;
    }

    public function headings(): array
    {
        return [
            'Reporte Detallado de Ventas',
            '', '', '', '', '', '', '', ''
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 50,  // Venta N° y Producto
            'B' => 25,  // Fecha
            'C' => 40,  // Cliente
            'D' => 25,  // Vendedor
            'E' => 20,  // Total
            'F' => 20,  // Estado
            'G' => 10,
            'H' => 10,
            'I' => 10,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        $sheet->getStyle('A1')->getFont()->setBold(true);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Colorear filas de resumen de ventas
                foreach ($this->resumenRows as $row => $estado) {
                    if ($estado == 'Registrado') {
                        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'DDEBF7'], // Celeste
                            ],
                        ]);
                    } else {
                        $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                            'fill' => [
                                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                                'startColor' => ['rgb' => 'BC544B'], // Rojo
                            ],
                        ]);
                    }
                }

                // Aplicar fondo celeste a encabezados de detalle
                // Usamos la colección cacheada en lugar de llamar al método collection() nuevamente
                $row = 2;
                foreach ($this->cachedCollection as $index => $rowData) {
                    if (is_array($rowData) && isset($rowData[0]) && $rowData[0] == 'Producto') {
                        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                            'font' => ['bold' => true],
                        ]);
                    }
                    $row++;
                }

                // Agregar total de ventas registradas al final
                $row = $sheet->getHighestRow() + 1;
                $sheet->setCellValue('D' . $row, 'Total de Ventas Registradas:');
                $sheet->setCellValue('E' . $row, number_format($this->totalVentasRegistradas, 2));
                $sheet->getStyle("D{$row}:E{$row}")->applyFromArray([
                    'font' => ['bold' => true],
                ]);
            }
        ];
    }
}