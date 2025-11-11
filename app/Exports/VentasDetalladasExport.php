<?php
namespace App\Exports;

use App\Venta;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;

class VentasDetalladasExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths
{
    protected $filters;
    protected $detalleHeaderRows = [];

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function collection()
    {
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
        $line = 2; // comenzamos en fila 2 porque headings ocupa la 1

        foreach ($ventas as $venta) {
            // Fila resumen de la venta
            $rows->push([
                'Venta N°: ' . $venta->num_comprobante,
                'Fecha: ' . date('d/m/Y H:i', strtotime($venta->fecha_hora)),
                'Cliente: ' . ($venta->cliente->nombre ?? 'S/N'),
                'Vendedor: ' . ($venta->usuario->persona->nombre ?? 'S/N'),
                'Total (Bs): ' . number_format($venta->total, 2),
                'Estado: ' . ($venta->estado == 1 ? 'Registrado' : 'Anulado'),
                '', '', '', ''
            ]);
            $line++;
            // Cabecera de detalle
            $rows->push([
                'Producto', 'Cantidad', 'Precio', 'Descuento', 'Subtotal', '', '', '', ''
            ]);
            $this->detalleHeaderRows[] = $line;
            $line++;
            // Detalles
            foreach ($venta->detalles as $d) {
                $subtotal = ($d->precio * $d->cantidad) - $d->descuento;
                $rows->push([
                    mb_strimwidth($d->producto->nombre ?? '-', 0, 40, '...'),
                    $d->cantidad,
                    number_format($d->precio, 2),
                    number_format($d->descuento, 2),
                    number_format($subtotal, 2),
                    '', '', '', ''
                ]);
                $line++;
            }
            // Línea vacía
            $rows->push(['', '', '', '', '', '', '', '', '']);
            $line++;
        }
        return $rows;
    }

    public function headings(): array
    {
        return [
            'Detalle de Ventas Detalladas',
            '', '', '', '', '', '', '', ''
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 40,
            'B' => 15,
            'C' => 15,
            'D' => 15,
            'E' => 18,
            'F' => 10,
            'G' => 10,
            'H' => 10,
            'I' => 10,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo del título principal
        $sheet->getStyle('A1')->getFont()->setBold(true);

        // Aplicar fondo celeste a encabezados de detalle
        foreach ($this->detalleHeaderRows as $row) {
            $sheet->getStyle("A{$row}:E{$row}")->applyFromArray([
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => 'DDEBF7'],
                ],
            ]);
        }

        return [];
    }
}
