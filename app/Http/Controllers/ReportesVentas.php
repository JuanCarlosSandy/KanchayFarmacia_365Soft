<?php

namespace App\Http\Controllers;

use App\DetalleVenta;
use App\Moneda;
use App\Venta;
use App\Exports\VentasGeneralExport;
use App\Exports\VentasDetalladasExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use FPDF;


class ReportesVentas extends Controller
{
    public function ResumenVentasPorDocumento(Request $request)
    {
        $fechaInicio = $request->fechaInicio . ' 00:00:00';
        $fechaFin = $request->fechaFin . ' 23:59:59';
        $moneda = $request->moneda;
        
        $ventas = Venta::join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('tipo_ventas', 'ventas.idtipo_venta', '=', 'tipo_ventas.id')
            ->join('roles', 'users.idrol', '=', 'roles.id')
            ->join('sucursales', 'ventas.idsucursal', '=', 'sucursales.id') 
            ->select(
                'ventas.num_comprobante as Factura',
                'ventas.id',
                'sucursales.nombre as Nombre_sucursal',
                'ventas.fecha_hora',
                DB::raw("'$moneda' as Tipo_Cambio"),
                'tipo_ventas.nombre_tipo_ventas as Tipo_venta',
                'roles.nombre AS nombre_rol',
                'users.usuario',
                'personas.nombre',
                'ventas.total AS importe_BS',
                DB::raw("ROUND((ventas.total / $moneda), 2) AS importe_usd"),
                'ventas.estado' 
            )
            ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin])
            ->orderBy('ventas.fecha_hora', 'desc');

        if ($request->has('estadoVenta') && $request->estadoVenta !== 'Todos') {
            $estado_venta = $request->estadoVenta;
            
            // Convertir texto a número
            if ($estado_venta === 'Registrado') {
                $ventas->where('ventas.estado', '=', 1);
            } elseif ($estado_venta === 'Anulado') {
                $ventas->where('ventas.estado', '=', 0);
            }
        }

        if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
            $sucursal = $request->sucursal;
            $ventas->where('ventas.idsucursal', '=', $sucursal); 
        }

        if ($request->has('ejecutivoCuentas') && $request->ejecutivoCuentas !== 'undefined') {
            $ejecutivoCuentas = $request->ejecutivoCuentas;
            $ventas->where('ventas.idusuario', '=', $ejecutivoCuentas);
        }

        if ($request->has('idcliente') && $request->idcliente !== 'undefined') {
            $cliente = $request->idcliente;
            $ventas->where('ventas.idcliente', '=', $cliente);
        }

        $ventas = $ventas->get();

        $total_importeBs = 0;
        $total_importeUSD = 0;
        
        foreach ($ventas as $venta) {
            // Solo sumar ventas registradas (estado = 1)
            if ($venta->estado == 1) {
                $total_importeBs += $venta->importe_BS;
                $total_importeUSD += $venta->importe_usd;
            }
        }
        
        return [
            'ventas' => $ventas,
            'total_BS' => number_format($total_importeBs, 2, '.', ''),
            'total_USD' => number_format($total_importeUSD, 2, '.', ''),
            'cantidad_ventas' => $ventas->count(),
            'ventas_registradas' => $ventas->where('estado', 1)->count(),
            'ventas_anuladas' => $ventas->where('estado', 0)->count()
        ];
    }
    public function ventasPorProducto(Request $request)
    {
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $fechaInicio = $fechaInicio . ' 00:00:00';
        $fechaFin = $fechaFin . ' 23:59:59';
        $ventas = Venta::join('detalle_ventas', 'ventas.id', 'detalle_ventas.idventa')
            ->join('personas', 'personas.id', '=', 'ventas.idcliente')
            ->join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('marcas', 'articulos.idmarca', '=', 'marcas.id')
            ->join('industrias', 'articulos.idindustria', '=', 'industrias.id')
            ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
            ->select(
                'ventas.fecha_hora',
                'personas.nombre',
                'detalle_ventas.*',
                'articulos.codigo',
                'articulos.descripcion',
                'categorias.nombre as nombre_categoria',
                'marcas.nombre as nombre_marca',
                'industrias.nombre as nombre_industria',
                'medidas.descripcion_medida as medida'
            )
            ->whereBetween('fecha_hora', [$fechaInicio, $fechaFin]);

        if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
            $sucursal = $request->sucursal;
            $ventas->where('sucursales.id', $sucursal);
        }

        if ($request->has('idcliente') && $request->idcliente !== 'undefined') {
            $cliente = $request->idcliente;
            $ventas->where('ventas.idcliente', $cliente);
        }
        if ($request->has('articulo') && $request->articulo !== 'undefined') {
            $articulo = $request->articulo;
            $ventas->where('detalle_ventas.idarticulo', $articulo);
        }
        if ($request->has('marca') && $request->marca !== 'undefined') {
            $idmarca = $request->marca;
            $ventas->where('articulos.idmarca', $idmarca);

        }
        if ($request->has('linea') && $request->linea !== 'undefined') {
            $idlinea = $request->linea;
            $ventas->where('articulos.idcategoria', $idlinea);

        }
        if ($request->has('industria') && $request->industria !== 'undefined') {
            $idindustria = $request->industria;
            $ventas->where('articulos.idindustria', $idindustria);

        }
        $ventas = $ventas->get();
        return ['resultados' => $ventas];
    }

    public function ResumenVentasPorDocumentoDetallado(Request $request)
    {
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $fechaInicio = $fechaInicio . ' 00:00:00';
        $fechaFin = $fechaFin . ' 23:59:59';
        $moneda = $request->moneda;
        $ventas = DetalleVenta::select(
            'articulos.codigo as codigo_item',
            'articulos.nombre as nombre_articulo',
            'ventas.num_comprobante as Numventa',
            'ventas.id',
            'ventas.total as Importe Bs',
            'ventas.fecha_hora as Fecha',
            'personas.id as id_cliente',
            'personas.nombre as Cliente',
            'users.usuario as Vendedor',
            'detalle_ventas.descuento', // <-- aquí agregas
            'tipo_ventas.nombre_tipo_ventas as Tipo de venta',
            'roles.nombre as Ejecutivo de Venta',
            'sucursales.nombre as Sucursal',
            'articulos.nombre',
            'detalle_ventas.cantidad',
            'detalle_ventas.precio',
            'categorias.nombre as nombre_categoria',
            'marcas.nombre as nombre_marca',
            'industrias.nombre as nombre_industria',
            'medidas.descripcion_medida as medida',
            'personas.num_documento as nit',

            DB::raw("ROUND((detalle_ventas.precio / detalle_ventas.cantidad), 2) AS precio_unitario"),
            DB::raw("'$moneda' as Tipo_cambio"),
            DB::raw("ROUND((detalle_ventas.precio / $moneda), 2) AS importe_usd")
        )
            ->join('ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
            ->join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('tipo_ventas', 'ventas.idtipo_venta', '=', 'tipo_ventas.id')
            ->join('roles', 'users.idrol', '=', 'roles.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
            ->join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')

            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('marcas', 'articulos.idmarca', '=', 'marcas.id')
            ->join('industrias', 'articulos.idindustria', '=', 'industrias.id')
            ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
            ->orderBy('personas.nombre')
            ->orderBy('ventas.fecha_hora')
            ->whereBetween('fecha_hora', [$fechaInicio, $fechaFin]);
        if ($request->has('estadoVenta')) {
            $estado_venta = $request->estadoVenta;
            if ($estado_venta !== 'Todos') {
                $ventas->where('ventas.estado', '=', $estado_venta);
            }
        }

        if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
            $sucursal = $request->sucursal;
            $ventas->where('sucursales.id', $sucursal);
        }

        if ($request->has('ejecutivoCuentas') && $request->ejecutivoCuentas !== 'undefined') {
            $ejecutivoCuentas = $request->ejecutivoCuentas;
            $ventas->where('ventas.idusuario', $ejecutivoCuentas);
        }

        if ($request->has('idcliente') && $request->idcliente !== 'undefined') {
            $cliente = $request->idcliente;
            $ventas->where('ventas.idcliente', $cliente);
        }
        $ventas = $ventas->get();

        $totalVentasPorCliente = [];

        foreach ($ventas as $venta) {
            $idCliente = $venta->id_cliente;
            $cantidadVenta = $venta->cantidad;
            $precioVenta = $venta->precio;

            if (!isset($totalVentasPorCliente[$idCliente])) {
                $totalVentasPorCliente[$idCliente] = [
                    'total_cantidad' => 0,
                    'total_precio' => 0,
                    'index' => null,
                ];
            }

            $totalVentasPorCliente[$idCliente]['total_cantidad'] += $cantidadVenta;
            $totalVentasPorCliente[$idCliente]['total_precio'] += $precioVenta;
            $totalVentasPorCliente[$idCliente]['index'] = $venta->id;
        }
        foreach ($ventas as $venta) {
            $idCliente = $venta->id_cliente;

            if (isset($totalVentasPorCliente[$idCliente]) && $venta->id == $totalVentasPorCliente[$idCliente]['index']) {
                $venta->total_cantidad_cliente = $totalVentasPorCliente[$idCliente]['total_cantidad'];
                $venta->total_precio_cliente = $totalVentasPorCliente[$idCliente]['total_precio'];
            }
        }

        return [
            'ventas' => $ventas,
        ];
    }

    public function reporteArticulosVendidos(Request $request)
    {
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;
        $fechaInicio = $fechaInicio . ' 00:00:00';
        $fechaFin = $fechaFin . ' 23:59:59';

        $query = DetalleVenta::select(
            'articulos.id as id_articulo',
            'articulos.nombre as nombre_articulo',
            DB::raw('SUM(detalle_ventas.cantidad) as cantidad_total'),
            DB::raw('DATE(ventas.fecha_hora) as fecha_venta')
        )
            ->join('ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
            ->join('articulos', 'detalle_ventas.idarticulo', '=', 'articulos.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->join('sucursales', 'users.idsucursal', '=', 'sucursales.id')
            ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin])
            ->groupBy('articulos.id', 'articulos.nombre', DB::raw('DATE(ventas.fecha_hora)'))
            ->orderBy('fecha_venta', 'asc');

        if ($request->has('estadoVenta')) {
            $estado_venta = $request->estadoVenta;
            if ($estado_venta !== 'Todos') {
                $query->where('ventas.estado', '=', $estado_venta);
            }
        }
        if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
            $sucursal = $request->sucursal;
            $query->where('sucursales.id', $sucursal);
        }
        if ($request->has('ejecutivoCuentas') && $request->ejecutivoCuentas !== 'undefined') {
            $ejecutivoCuentas = $request->ejecutivoCuentas;
            $query->where('ventas.idusuario', $ejecutivoCuentas);
        }
        if ($request->has('idcliente') && $request->idcliente !== 'undefined') {
            $cliente = $request->idcliente;
            $query->where('ventas.idcliente', $cliente);
        }
        if ($request->has('moneda') && $request->moneda !== 'undefined') {
            // Si necesitas filtrar por moneda, agrega aquí la lógica
        }

        $resultados = $query->get();
        return response()->json(['articulos_vendidos' => $resultados]);
    }

    public function descargarReporteGeneralPDF(Request $request)
    {
        $query = Venta::join('personas', 'ventas.idcliente', '=', 'personas.id')
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->select(
                'ventas.num_comprobante',
                'ventas.fecha_hora',
                'personas.nombre as cliente',
                'ventas.total',
                'users.usuario as vendedor',
                'ventas.estado'
            );

        // Filtros
        $filtros = [];

        if ($request->filled('sucursal') && $request->sucursal !== 'undefined') {
            $query->where('users.idsucursal', $request->sucursal);
            $filtros[] = "Sucursal: {$request->sucursal}";
        }

        if ($request->filled('tipoReporte')) {
            if ($request->tipoReporte === 'dia' && $request->filled('fechaSeleccionada')) {
                $fechaInicio = $request->fechaSeleccionada . ' 00:00:00';
                $fechaFin = $request->fechaSeleccionada . ' 23:59:59';
                $query->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin]);
                $filtros[] = "Fecha: {$request->fechaSeleccionada}";
            } else if ($request->tipoReporte === 'mes' && $request->filled('mesSeleccionado')) {
                $mesSeleccionado = $request->mesSeleccionado;
                $fechaInicio = $mesSeleccionado . '-01 00:00:00';
                $fechaFin = date('Y-m-t', strtotime($mesSeleccionado . '-01')) . ' 23:59:59';
                $query->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin]);
                $filtros[] = "Mes: " . date('F Y', strtotime($mesSeleccionado . '-01'));
            }
        }

        if ($request->filled('estadoVenta') && $request->estadoVenta !== 'Todos' && $request->estadoVenta !== 'undefined') {
            $query->where('ventas.estado', $request->estadoVenta);
            $filtros[] = "Estado: {$request->estadoVenta}";
        }

        if ($request->filled('idcliente') && $request->idcliente !== 'undefined') {
            $query->where('ventas.idcliente', $request->idcliente);
            $filtros[] = "Cliente ID: {$request->idcliente}";
        }

        $ventas = $query->orderBy('ventas.fecha_hora', 'asc')->get();

        $pdf = new PDFVentas();
        $pdf->AliasNbPages();
        $pdf->AddPage();

        // Encabezado
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, utf8_decode('REPORTE GENERAL DE VENTAS'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Fecha de generación: ' . date('d/m/Y H:i'), 0, 1, 'C');

        // Filtros usados
        if (count($filtros) > 0) {
            $pdf->Ln(2);
            foreach ($filtros as $filtro) {
                $pdf->SetFont('Arial', '', 9);
                $pdf->Cell(0, 5, utf8_decode($filtro), 0, 1);
            }
        }

        $pdf->Ln(5);

        // Cabecera tabla
        $pdf->SetFillColor(0, 102, 204);
        $pdf->SetTextColor(255);
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(30, 8, 'Nro Comprobante', 1, 0, 'C', true);
        $pdf->Cell(35, 8, 'Fecha y Hora', 1, 0, 'C', true);
        $pdf->Cell(50, 8, 'Cliente', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Total Venta', 1, 0, 'C', true);
        $pdf->Cell(30, 8, 'Vendedor', 1, 0, 'C', true);
        $pdf->Cell(20, 8, 'Estado', 1, 1, 'C', true);

        // Detalles
        $pdf->SetFont('Arial', '', 9);
        $pdf->SetTextColor(0);

        foreach ($ventas as $venta) {
            $pdf->Cell(30, 8, $venta->num_comprobante, 1);
            $pdf->Cell(35, 8, date('d/m/Y H:i', strtotime($venta->fecha_hora)), 1);
            $cliente = $venta->cliente ?? '-';
            $clienteRecortado = mb_strimwidth($cliente, 0, 30, '...');
            $pdf->Cell(50, 8, utf8_decode($clienteRecortado), 1);
            $pdf->Cell(30, 8, number_format($venta->total, 2), 1, 0, 'R');
            $vendedor = $venta->vendedor ?? '-';
            $vendedorRecortado = mb_strimwidth($vendedor, 0, 25, '...');
            $pdf->Cell(30, 8, utf8_decode($vendedorRecortado), 1, 0);
            $pdf->Cell(20, 8, $venta->estado == 1 ? 'Registrado' : 'Anulado', 1, 1, 'C');
        }

        // Descargar
        $pdf->Output('D', 'reporte_general_ventas_' . date('Ymd_His') . '.pdf');
        exit;
    }

    public function descargarVentasDetalladasPDF(Request $request)
    {
        $query = Venta::with(['detalles.producto', 'sucursal', 'usuario.persona', 'cliente'])
            ->join('users', 'ventas.idusuario', '=', 'users.id')
            ->select('ventas.*');

        $filtros = [];

        if ($request->filled('sucursal') && $request->sucursal !== 'undefined') {
            $query->where('users.idsucursal', $request->sucursal);
            $filtros[] = "Sucursal: {$request->sucursal}";
        }

        if ($request->filled('tipoReporte')) {
            if ($request->tipoReporte === 'dia' && $request->filled('fechaSeleccionada')) {
                $fechaInicio = $request->fechaSeleccionada . ' 00:00:00';
                $fechaFin = $request->fechaSeleccionada . ' 23:59:59';
                $query->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin]);
                $filtros[] = "Fecha: {$request->fechaSeleccionada}";
            } else if ($request->tipoReporte === 'mes' && $request->filled('mesSeleccionado')) {
                $mesSeleccionado = $request->mesSeleccionado;
                $fechaInicio = $mesSeleccionado . '-01 00:00:00';
                $fechaFin = date('Y-m-t', strtotime($mesSeleccionado . '-01')) . ' 23:59:59';
                $query->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin]);
                $filtros[] = "Mes: " . date('F Y', strtotime($mesSeleccionado . '-01'));
            }
        }

        if ($request->filled('estadoVenta') && $request->estadoVenta !== 'Todos' && $request->estadoVenta !== 'undefined') {
            $query->where('ventas.estado', $request->estadoVenta);
            $filtros[] = "Estado: {$request->estadoVenta}";
        }

        if ($request->filled('idcliente') && $request->idcliente !== 'undefined') {
            $query->where('ventas.idcliente', $request->idcliente);
            $filtros[] = "Cliente: {$request->idcliente}";
        }

        $ventas = $query->orderBy('ventas.fecha_hora', 'desc')->get();

        $pdf = new PDFDetalleVentas();
        $pdf->AliasNbPages();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, utf8_decode('REPORTE DETALLADO DE VENTAS'), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(0, 6, 'Fecha de generacion: ' . date('d/m/Y H:i'), 0, 1, 'C');
        $pdf->Ln(4);

        // Mostrar filtros aplicados
        if (count($filtros) > 0) {
            $pdf->SetFont('Arial', '', 9);
            foreach ($filtros as $filtro) {
                $pdf->Cell(0, 5, utf8_decode($filtro), 0, 1);
            }
            $pdf->Ln(3);
        }

        foreach ($ventas as $venta) {
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->SetFillColor(230, 230, 230);
            $pdf->Cell(0, 7, utf8_decode("Venta Nro: {$venta->num_comprobante}"), 0, 1, 'L', true);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(60, 6, 'Fecha: ' . date('d/m/Y H:i', strtotime($venta->fecha_hora)), 0, 0);
            $pdf->Cell(60, 6, 'Vendedor: ' . ($venta->usuario->persona->nombre ?? ''), 0, 1);
            $pdf->Cell(60, 6, 'Cliente: ' . ($venta->cliente->nombre ?? 'S/N'), 0, 0);
            $pdf->Cell(60, 6, 'Importe Bs: ' . number_format($venta->total, 2), 0, 1);
            $pdf->Cell(60, 6, 'Estado: ' . ($venta->estado == 1 ? 'Registrado' : 'Anulado'), 0, 1);
            $pdf->Ln(2);

            // Cabecera del detalle
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->SetFillColor(0, 102, 204);
            $pdf->SetTextColor(255);
            $pdf->Cell(90, 7, 'Producto', 1, 0, 'C', true);
            $pdf->Cell(20, 7, 'Cant.', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Precio', 1, 0, 'C', true);
            $pdf->Cell(25, 7, 'Descuento', 1, 0, 'C', true);
            $pdf->Cell(30, 7, 'Subtotal', 1, 1, 'C', true);

            // Detalles
            $pdf->SetFont('Arial', '', 9);
            $pdf->SetTextColor(0);
            foreach ($venta->detalles as $d) {
                $subtotal = ($d->precio * $d->cantidad) - $d->descuento;
                $nombreProducto = $d->producto->nombre ?? '-';
                $nombreRecortado = mb_strimwidth($nombreProducto, 0, 45, '...');
                $pdf->Cell(90, 6, utf8_decode($nombreRecortado), 1);
                $pdf->Cell(20, 6, $d->cantidad, 1, 0, 'C');
                $pdf->Cell(25, 6, number_format($d->precio, 2), 1, 0, 'R');
                $pdf->Cell(25, 6, number_format($d->descuento, 2), 1, 0, 'R');
                $pdf->Cell(30, 6, number_format($subtotal, 2), 1, 1, 'R');
            }
            $pdf->Ln(5);
        }
        $pdf->Output('D', 'ventas_detalladas_' . date('Ymd_His') . '.pdf');
        exit;
    }

    public function exportarVentasGeneralExcel(Request $request)
    {
        $filters = $request->only([
            'sucursal',
            'tipoReporte',
            'fechaSeleccionada',
            'mesSeleccionado',
            'estadoVenta',
            'idcliente'
        ]);
        $filename = 'ventas_general_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new VentasGeneralExport($filters), $filename);
    }

    public function exportarVentasDetalladasExcel(Request $request)
    {
        $filters = $request->only([
            'sucursal',
            'tipoReporte',
            'fechaSeleccionada',
            'mesSeleccionado',
            'estadoVenta',
            'idcliente'
        ]);
        $filename = 'ventas_detalladas_' . date('Ymd_His') . '.xlsx';
        return Excel::download(new VentasDetalladasExport($filters), $filename);
    }
}
class PDFVentas extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}
class PDFDetalleVentas extends FPDF
{
    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, utf8_decode('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}