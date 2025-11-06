<?php

namespace App\Http\Controllers;

use App\Inventario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use FPDF;


class ReportesInventariosController extends Controller
{
    public function inventarioFisicoValorado(Request $request, $tipo)
    {
        $fechaVencimiento = $request->fecha_vencimiento;
        if ($tipo === 'item') {
            $resultados = DB::table('inventarios')
                ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
                ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->join('marcas', 'articulos.idmarca', '=', 'marcas.id')
                ->join('industrias', 'articulos.idindustria', '=', 'industrias.id')
                ->select(
                    'articulos.nombre AS nombre_producto',
                    'articulos.unidad_envase',
                    'almacens.nombre_almacen',
                    DB::raw('SUM(inventarios.saldo_stock) AS saldo_stock_total'),
                    DB::raw('(SUM(inventarios.saldo_stock) * articulos.precio_costo_unid) AS costo_total'),
                    'categorias.nombre AS nombre_categoria',
                    'marcas.nombre AS nombre_marca',
                    'industrias.nombre AS nombre_industria'
                )
                ->where('inventarios.fecha_vencimiento', '<=', $fechaVencimiento)
                ->groupBy(
                    'articulos.nombre',
                    'articulos.unidad_envase',
                    'almacens.nombre_almacen',
                    'categorias.nombre',
                    'marcas.nombre',
                    'industrias.nombre',
                    'articulos.precio_costo_unid'
                )
                ->orderBy('articulos.nombre')
                ->orderBy('almacens.nombre_almacen');

        } else if ($tipo === 'lote') {
            $resultados = DB::table('inventarios')
                ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->join('articulos', 'inventarios.idarticulo', '=', 'articulos.id')
                ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
                ->join('marcas', 'articulos.idmarca', '=', 'marcas.id')
                ->join('industrias', 'articulos.idindustria', '=', 'industrias.id')
                ->select(
                    'articulos.nombre AS nombre_producto',
                    'articulos.unidad_envase',
                    'articulos.precio_costo_unid',
                    'inventarios.saldo_stock',
                    DB::raw('(inventarios.saldo_stock * articulos.precio_costo_unid) AS costo_total'),
                    DB::raw('DATE_FORMAT(inventarios.created_at, "%Y-%m-%d") AS fecha_ingreso'),
                    'inventarios.fecha_vencimiento',
                    'almacens.nombre_almacen',
                    'categorias.nombre AS nombre_categoria',
                    'marcas.nombre AS nombre_marca',
                    'industrias.nombre AS nombre_industria'
                )
                ->where('inventarios.fecha_vencimiento', '<=', $fechaVencimiento)
                ->orderBy('articulos.nombre');

        }
        if ($request->has('idAlmacen') && $request->idAlmacen !== 'undefined') {
            $idAlmacen = $request->idAlmacen;
            $resultados->where('almacens.id', $idAlmacen);
        }
        if ($request->has('idArticulo') && $request->idArticulo !== 'undefined') {
            $idArticulo = $request->idArticulo;
            $resultados->where('articulos.id', $idArticulo);
        }
        if ($request->has('idMarca') && $request->idMarca !== 'undefined') {
            $idMarca = $request->idMarca;
            $resultados->where('articulos.idmarca', $idMarca);
        }
        if ($request->has('idLinea') && $request->idLinea !== 'undefined') {
            $idLinea = $request->idLinea;
            $resultados->where('articulos.idcategoria', $idLinea);

        }
        if ($request->has('idIndustria') && $request->idIndustria !== 'undefined') {
            $idIndustria = $request->idIndustria;
            $resultados->where('articulos.idindustria', $idIndustria);

        }
        $resultados = $resultados->get();
        return ['inventarios_valorado' => $resultados];

    }
    public function resumenFisicoMovimientos(Request $request)
    {
        $fechaInicio = $request->fechaInicio . ' 00:00:00';
        $fechaFin = $request->fechaFin . ' 23:59:59';

        $productos = DB::table('articulos')
            ->select(
                'articulos.id',
                'articulos.nombre as nombre_producto',
                'articulos.codigo',
                'categorias.nombre as nombre_categoria',
                'almacens.id as id_almacen',
                'almacens.nombre_almacen as nombre_almacen',
                'sucursales.nombre as nombre_sucursal'
            )
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('inventarios', 'inventarios.idarticulo', '=', 'articulos.id')
            ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->join('sucursales', 'almacens.sucursal', '=', 'sucursales.id')
            ->where('articulos.condicion', 1)
            ->groupBy(
                'articulos.id',
                'articulos.nombre',
                'articulos.codigo',
                'categorias.nombre',
                'almacens.id',
                'almacens.nombre_almacen',
                'sucursales.nombre'
            );

        if ($request->has('articulo') && $request->articulo !== 'undefined') {
            $productos->where('articulos.id', $request->articulo);
        }
        if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
            $productos->where('sucursales.id', $request->sucursal);
        }
        if ($request->has('marca') && $request->marca !== 'undefined') {
            $productos->where('articulos.idmarca', $request->marca);
        }
        if ($request->has('linea') && $request->linea !== 'undefined') {
            $productos->where('articulos.idcategoria', $request->linea);
        }

        $productos = $productos->get();

        $resultados = [];

        foreach ($productos as $producto) {
            $ingresos = DB::table('detalle_ingresos')
                ->join('ingresos', 'detalle_ingresos.idingreso', '=', 'ingresos.id')
                ->where('ingresos.idalmacen', $producto->id_almacen)
                ->where('detalle_ingresos.idarticulo', $producto->id)
                ->whereBetween('ingresos.fecha_hora', [$fechaInicio, $fechaFin])
                ->sum('detalle_ingresos.cantidad');


            $ventas = DB::table('ventas')
                ->join('detalle_ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
                ->where('ventas.idalmacen', $producto->id_almacen)
                ->where('detalle_ventas.idarticulo', $producto->id)
                ->whereBetween('ventas.fecha_hora', [$fechaInicio, $fechaFin])
                ->sum('detalle_ventas.cantidad');

            $traspasosEntrada = DB::table('detalle_traspasos')
    ->join('traspasos', 'detalle_traspasos.idtraspaso', '=', 'traspasos.id')
    ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
    ->where('inventarios.idarticulo', $producto->id)
    ->where('traspasos.almacen_destino', $producto->id_almacen)
    ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
    ->sum('detalle_traspasos.cantidad_traspaso');

            $traspasosSalida = DB::table('detalle_traspasos')
                ->join('traspasos', 'detalle_traspasos.idtraspaso', '=', 'traspasos.id')
                ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
                ->where('inventarios.idarticulo', $producto->id)
                ->where('traspasos.almacen_origen', $producto->id_almacen)
                ->where('traspasos.tipo_traspaso', 'Salida')
                ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
                ->sum('detalle_traspasos.cantidad_traspaso');

            $ajuste = DB::table('ajuste_invetarios')
                ->where('producto', $producto->id)
                ->where('almacen', $producto->id_almacen)
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->sum('cantidad');

            $saldo_stock = DB::table('inventarios')
                ->where('idarticulo', $producto->id)
                ->where('idalmacen', $producto->id_almacen)
                ->sum('saldo_stock');

            $resultados[] = [
                'codigo' => $producto->codigo,
                'sucursal' => $producto->nombre_sucursal,
                'almacen' => $producto->nombre_almacen,
                'nombre_producto' => $producto->nombre_producto,
                'categoria' => $producto->nombre_categoria,
                'total_ventas' => $ventas,
                'total_ingresos' => $ingresos,
                'total_traspasos_entrada' => $traspasosEntrada,
                'total_traspasos_salida' => $traspasosSalida,
                'total_ajuste' => $ajuste,
                'saldo_stock_actual' => $saldo_stock
            ];
        }

        return ['resultados' => $resultados];
    }

    public function resumenFisicoMovimientosDetallado(Request $request)
    {
        $fechaInicio = $request->fechaInicio;
        $fechaFin = $request->fechaFin;

        $fechaInicio = $fechaInicio . ' 00:00:00';
        $fechaFin = $fechaFin . ' 23:59:59';
        $productos = DB::table('articulos')
            ->select(
                'articulos.id',
                'articulos.nombre',
                'articulos.codigo',
                'articulos.descripcion',
                'categorias.nombre as nombre_categoria',
                'marcas.nombre as nombre_marca',
                'industrias.nombre as nombre_industria',
                'medidas.descripcion_medida as medida',
                'almacens.sucursal as idSucursal'
            )
            ->join('categorias', 'articulos.idcategoria', '=', 'categorias.id')
            ->join('marcas', 'articulos.idmarca', '=', 'marcas.id')
            ->join('industrias', 'articulos.idindustria', '=', 'industrias.id')
            ->join('medidas', 'articulos.idmedida', '=', 'medidas.id')
            ->join('inventarios', 'inventarios.idarticulo', '=', 'articulos.id')
            ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
            ->groupBy('articulos.id', 'articulos.nombre', 'articulos.codigo', 'articulos.descripcion', 'categorias.nombre', 'marcas.nombre', 'industrias.nombre', 'medidas.descripcion_medida', 'almacens.sucursal');


        if ($request->has('articulo') && $request->articulo !== 'undefined') {
            $idarticulo = $request->articulo;
            $productos->where('articulos.id', $idarticulo);
        }
        if ($request->has('sucursal') && $request->sucursal !== 'undefined') {
            $sucursal = $request->sucursal;
            $productos->where('almacens.sucursal', $sucursal);
        }
        // Agregar filtros opcionales si se proporcionan otros parámetros
        if ($request->has('marca') && $request->marca !== 'undefined') {
            $idmarca = $request->marca;
            $productos->where('articulos.idmarca', $idmarca);
        }
        if ($request->has('linea') && $request->linea !== 'undefined') {
            $idlinea = $request->linea;
            $productos->where('articulos.idcategoria', $idlinea);
        }
        $productos = $productos->get();

        $resultados = [];

        foreach ($productos as $producto) {
            $traspasos_ingreso = DB::table('detalle_traspasos')
                ->join('traspasos', 'detalle_traspasos.idtraspaso', '=', 'traspasos.id')
                ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
                ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->where('inventarios.idarticulo', $producto->id)
                ->where('traspasos.tipo_traspaso', 'Entrada')
                ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
                ->sum('detalle_traspasos.cantidad_traspaso');
            $traspasos_salida = DB::table('detalle_traspasos')
                ->join('traspasos', 'detalle_traspasos.idtraspaso', '=', 'traspasos.id')
                ->join('inventarios', 'detalle_traspasos.idinventario', '=', 'inventarios.id')
                ->join('almacens', 'inventarios.idalmacen', '=', 'almacens.id')
                ->where('inventarios.idarticulo', $producto->id)
                ->where('traspasos.tipo_traspaso', 'Salida')
                ->whereBetween('traspasos.fecha_traspaso', [$fechaInicio, $fechaFin])
                ->sum('detalle_traspasos.cantidad_traspaso');

            $saldoAnterior = DB::table('detalle_ingresos')
                ->join('ingresos', 'detalle_ingresos.idingreso', '=', 'ingresos.id')
                ->join('users', 'ingresos.idusuario', '=', 'users.id')
                ->where('users.idsucursal', $producto->idSucursal)
                ->where('detalle_ingresos.idarticulo', $producto->id)
                ->where('ingresos.fecha_hora', '<', $fechaInicio)
                ->sum('detalle_ingresos.cantidad');

            $egresosAnteriores = DB::table('ventas')
                ->join('detalle_ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
                ->where('detalle_ventas.idarticulo', $producto->id)
                ->where('ventas.fecha_hora', '<', $fechaInicio)
                ->sum('detalle_ventas.cantidad');
            $saldoAnterior -= $egresosAnteriores;

            $ingresos = DB::table('detalle_ingresos')
                ->join('ingresos', 'detalle_ingresos.idingreso', '=', 'ingresos.id')
                ->join('users', 'ingresos.idusuario', '=', 'users.id')
                ->where('users.idsucursal', $producto->idSucursal)
                ->where('detalle_ingresos.idarticulo', $producto->id)
                ->where('ingresos.fecha_hora', '>=', $fechaInicio)
                ->where('ingresos.fecha_hora', '<=', $fechaFin)
                ->sum('detalle_ingresos.cantidad');
            $ingresos += $traspasos_ingreso;
            $ventas = DB::table('ventas')
                ->join('detalle_ventas', 'detalle_ventas.idventa', '=', 'ventas.id')
                ->join('users', 'ventas.idusuario', '=', 'users.id')
                ->where('users.idsucursal', $producto->idSucursal)
                ->where('detalle_ventas.idarticulo', $producto->id)
                ->where('ventas.fecha_hora', '>=', $fechaInicio)
                ->where('ventas.fecha_hora', '<=', $fechaFin)
                ->sum('detalle_ventas.cantidad');
            $ventas += $traspasos_salida;
            $saldoActual = $saldoAnterior + $ingresos - $ventas;

            $resultado = [

                'nombre_producto' => $producto->nombre,
                'codigo' => $producto->codigo,
                'descripcion' => $producto->descripcion,
                'nombre_categoria' => $producto->nombre_categoria,
                'nombre_marca' => $producto->nombre_marca,
                'nombre_industria' => $producto->nombre_industria,
                'medida' => $producto->medida,
                'saldo_anterior' => $saldoAnterior,
                'ingresos' => $ingresos,
                'ventas' => $ventas,
                'saldo_actual' => $saldoActual,
                'traspasos_entrada' => $traspasos_ingreso,
                'traspasos_salida' => $traspasos_salida
            ];

            $resultados[] = $resultado;
        }

        return ['resultados' => $resultados, 'productos' => $productos];

    }

    public function exportarPDFResumenFisico(Request $request)
    {
        $data = $this->resumenFisicoMovimientos($request);
        $resultados = $data['resultados'];
        $productos = $data['productos'];

        $pdf = new Fpdf('P', 'mm', 'A4');
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 14);
        $pdf->Cell(0, 10, 'RESUMEN FISICO DE MOVIMIENTOS', 0, 1, 'C');

        // Información de filtros
        $pdf->SetFont('Arial', '', 10);
        $pdf->Cell(90, 6, 'Fecha Inicio: ' . $request->fechaInicio, 0, 0);
        $pdf->Cell(90, 6, 'Fecha Fin: ' . $request->fechaFin, 0, 0);
        $pdf->Ln();
        if (!empty($productos[0])) {
            $pdf->Cell(90, 6, 'Articulo: ' . $productos[0]->nombre, 0, 0);
            $pdf->Cell(60, 6, 'Categoria: ' . $productos[0]->nombre_categoria, 0, 0);
            $pdf->Cell(60, 6, 'Sucursal ID: ' . $productos[0]->nombre_sucursal, 0, 0);
            $pdf->Ln();
        }

        // Cabecera
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $pdf->Cell(20, 7, 'Codigo', 1, 0, 'C', true);
        $pdf->Cell(55, 7, 'Producto', 1, 0, 'C', true);
        $pdf->Cell(35, 7, 'Categoria', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Saldo Ant.', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Entradas', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Salidas', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Saldo Act.', 1, 1, 'C', true);

        // Cuerpo
        $pdf->SetFont('Arial', '', 9);
        foreach ($resultados as $item) {
            $pdf->Cell(20, 6, $item['codigo'], 1);
            $pdf->Cell(55, 6, utf8_decode($item['nombre_producto']), 1);
            $pdf->Cell(35, 6, utf8_decode($item['nombre_categoria']), 1);
            $pdf->Cell(20, 6, number_format($item['saldo_anterior'], 2), 1, 0, 'R');
            $pdf->Cell(20, 6, number_format($item['ingresos'], 2), 1, 0, 'R');
            $pdf->Cell(20, 6, number_format($item['ventas'], 2), 1, 0, 'R');
            $pdf->Cell(20, 6, number_format($item['saldo_actual'], 2), 1, 1, 'R');
        }

        // *** SOLUCIÓN ***
        return response($pdf->Output('S')) // 'S' -> Devuelve el PDF como string
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="resumen_movimientos_fisicos.pdf"');
    }

}
