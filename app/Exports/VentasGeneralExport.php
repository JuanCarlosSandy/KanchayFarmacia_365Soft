<?php
namespace App\Exports;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class VentasGeneralExport implements FromQuery, WithHeadings, WithMapping, WithColumnWidths, WithStyles
{
    protected $filters;

    public function __construct(array $filters)
    {
        $this->filters = $filters;
    }

    public function query()
    {
        $query = DB::table('ventas')
            ->join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->select(
                'ventas.num_comprobante',
                'ventas.fecha_hora',
                'personas.nombre as cliente',
                'ventas.total',
                'users.usuario as vendedor',
                'ventas.estado'
            );

        if (!empty($this->filters['sucursal']) && $this->filters['sucursal'] !== 'undefined') {
            $query->where('users.idsucursal', $this->filters['sucursal']);
        }

        if (!empty($this->filters['tipoReporte'])) {
            if ($this->filters['tipoReporte'] === 'dia' && !empty($this->filters['fechaSeleccionada'])) {
                $inicio = $this->filters['fechaSeleccionada'] . ' 00:00:00';
                $fin = $this->filters['fechaSeleccionada'] . ' 23:59:59';
                $query->whereBetween('ventas.fecha_hora', [$inicio, $fin]);
            } else if ($this->filters['tipoReporte'] === 'mes' && !empty($this->filters['mesSeleccionado'])) {
                $mesSeleccionado = $this->filters['mesSeleccionado'];
                $inicio = $mesSeleccionado . '-01 00:00:00';
                $fin = date('Y-m-t', strtotime($mesSeleccionado . '-01')) . ' 23:59:59';
                $query->whereBetween('ventas.fecha_hora', [$inicio, $fin]);
            }
        }

        if (!empty($this->filters['estadoVenta']) && $this->filters['estadoVenta'] !== 'Todos' && $this->filters['estadoVenta'] !== 'undefined') {
            $query->where('ventas.estado', $this->filters['estadoVenta']);
        }

        if (!empty($this->filters['idcliente']) && $this->filters['idcliente'] !== 'undefined') {
            $query->where('ventas.idcliente', $this->filters['idcliente']);
        }

        return $query->orderBy('ventas.fecha_hora', 'asc');
    }

    public function headings(): array
    {
        return ['N° Comprobante', 'Fecha y Hora', 'Cliente', 'Total Venta', 'Vendedor', 'Estado'];
    }

    public function map($row): array
    {
        return [
            $row->num_comprobante,
            date('d/m/Y H:i', strtotime($row->fecha_hora)),
            mb_strimwidth($row->cliente, 0, 30, '...'),
            number_format($row->total, 2),
            mb_strimwidth($row->vendedor, 0, 25, '...'),
            $row->estado == 1 ? 'Registrado' : 'Anulado'
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 20,
            'B' => 20,
            'C' => 40,
            'D' => 15,
            'E' => 30,
            'F' => 15,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Encabezado en negrita con fondo celeste
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => ['bold' => true],
            'fill' => [
                'fillType' => 'solid',
                'startColor' => ['rgb' => 'DDEBF7'],
            ],
        ]);
        return [];
    }
}
