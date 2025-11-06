<?php
namespace App\Http\Controllers;
use Illuminate\Support\Facades\Response;

use Barryvdh\DomPDF\Facade as PDF;
use FPDF;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Caja;
use App\Ingreso;
use App\Articulo;
use App\DetalleIngreso;
use App\IngresoCuota;
use App\User;
use App\Notifications\NotifyAdmin;
use Exception;

class IngresoController extends Controller
{
    public function generarNotaIngreso($idIngreso)
    {
        $ingreso = Ingreso::join(
            "personas",
            "ingresos.idproveedor",
            "=",
            "personas.id"
        )
            ->join("users", "ingresos.idusuario", "=", "users.id")
            ->select(
                "ingresos.id",
                "ingresos.tipo_comprobante",
                "ingresos.serie_comprobante",
                "ingresos.num_comprobante",
                "ingresos.created_at",
                "ingresos.impuesto",
                "ingresos.total",
                "ingresos.estado",
                "personas.nombre",
                "personas.tipo_documento",
                "personas.num_documento",
                "personas.direccion",
                "personas.email",
                "personas.telefono",
                "users.usuario"
            )
            ->where("ingresos.id", "=", $idIngreso)
            ->orderBy("ingresos.id", "desc")
            ->first();

        $detalles = DetalleIngreso::join(
            "articulos",
            "detalle_ingresos.idarticulo",
            "=",
            "articulos.id"
        )
            ->select(
                "detalle_ingresos.cantidad",
                "detalle_ingresos.precio",
                "articulos.nombre as articulo"
            )
            ->where("detalle_ingresos.idingreso", "=", $idIngreso)
            ->orderBy("detalle_ingresos.id", "desc")
            ->get();

        $pdf = PDF::loadView("pdf.nota_ingreso", [
            "ingreso" => $ingreso,
            "detalles" => $detalles,
        ]);

        return $pdf->download("nota_ingreso.pdf");
    }
    public function generarPdfBoleta($idIngreso)
    {
        $ingreso = Ingreso::join(
            "personas",
            "ingresos.idproveedor",
            "=",
            "personas.id"
        )
            ->join("users", "ingresos.idusuario", "=", "users.id")
            ->select(
                "ingresos.id",
                "ingresos.tipo_comprobante",
                "ingresos.serie_comprobante",
                "ingresos.num_comprobante",
                "ingresos.created_at",
                "ingresos.impuesto",
                "ingresos.total",
                "ingresos.estado",
                "personas.nombre",
                "personas.tipo_documento",
                "personas.num_documento",
                "personas.direccion",
                "personas.email",
                "personas.telefono",
                "users.usuario"
            )
            ->where("ingresos.id", "=", $idIngreso)
            ->orderBy("ingresos.id", "desc")
            ->first();

        $detalles = DetalleIngreso::join(
            "articulos",
            "detalle_ingresos.idarticulo",
            "=",
            "articulos.id"
        )
            ->select(
                "detalle_ingresos.cantidad",
                "detalle_ingresos.precio",
                "articulos.nombre as articulo"
            )
            ->where("detalle_ingresos.idingreso", "=", $idIngreso)
            ->orderBy("detalle_ingresos.id", "desc")
            ->get();

        $pdf = PDF::loadView("pdf.boleta", [
            "ingreso" => $ingreso,
            "detalles" => $detalles,
        ]);

        return $pdf->download("venta.pdf"); // Devuelve el PDF
    }

    public function index(Request $request)
{
    if (!$request->ajax()) {
        return redirect("/");
    }

    $buscar = $request->buscar;

    $usuario = \Auth::user();
    $idrol = $usuario->idrol;
    $idsucursal = $usuario->idsucursal;

    $query = Ingreso::join("users", "ingresos.idusuario", "=", "users.id")
        ->join("sucursales", "users.idsucursal", "=", "sucursales.id")
        ->join("almacens", "ingresos.idalmacen", "=", "almacens.id")
        ->select(
            "ingresos.id",
            "ingresos.tipo_comprobante",
            "ingresos.serie_comprobante",
            "ingresos.num_comprobante",
            "ingresos.fecha_hora",
            "ingresos.impuesto",
            "ingresos.total",
            "ingresos.estado",
            "users.usuario",
            "sucursales.nombre as nombre_sucursal",
            "almacens.nombre_almacen as nombre_almacen",
            "descuento_global"
        );

    // Filtro por rol
    if ($idrol == 1) {
        $query->where('users.idsucursal', $idsucursal);
    }

    // Búsqueda global en múltiples campos
    if (!empty($buscar)) {
        $query->where(function($q) use ($buscar) {
            $q->where("ingresos.tipo_comprobante", "like", "%" . $buscar . "%")
              ->orWhere("ingresos.serie_comprobante", "like", "%" . $buscar . "%")
              ->orWhere("ingresos.num_comprobante", "like", "%" . $buscar . "%")
              ->orWhere("ingresos.fecha_hora", "like", "%" . $buscar . "%")
              ->orWhere("ingresos.total", "like", "%" . $buscar . "%")
              ->orWhere("users.usuario", "like", "%" . $buscar . "%")
              ->orWhere("sucursales.nombre", "like", "%" . $buscar . "%")
              ->orWhere("almacens.nombre_almacen", "like", "%" . $buscar . "%");
        });
        $perPage = 11;
    } else {
        $perPage = 11;
    }

    $ingresos = $query->orderBy("ingresos.id", "desc")->paginate($perPage);

    return [
        "pagination" => [
            "total" => $ingresos->total(),
            "current_page" => $ingresos->currentPage(),
            "per_page" => $ingresos->perPage(),
            "last_page" => $ingresos->lastPage(),
            "from" => $ingresos->firstItem(),
            "to" => $ingresos->lastItem(),
        ],
        "ingresos" => $ingresos,
    ];
}


    public function obtenerCabecera(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        $id = $request->id;

        $ingreso = Ingreso::join("users", "ingresos.idusuario", "=", "users.id")
            ->select(
                "ingresos.id",
                "ingresos.tipo_comprobante",
                "ingresos.serie_comprobante",
                "ingresos.num_comprobante",
                "ingresos.fecha_hora",
                "ingresos.impuesto",
                "ingresos.total",
                "ingresos.estado",
                "users.usuario"
            )
            ->where("ingresos.id", "=", $id)
            ->orderBy("ingresos.id", "desc")
            ->take(1)
            ->get();

        return ["ingreso" => $ingreso];
    }

    public function obtenerDetalles(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        $id = $request->id;

        $detalles = DetalleIngreso::join(
            "articulos",
            "detalle_ingresos.idarticulo",
            "=",
            "articulos.id"
        )
            ->select(
                "detalle_ingresos.cantidad",
                "detalle_ingresos.precio",
                "articulos.nombre as articulo"
            )
            ->where("detalle_ingresos.idingreso", "=", $id)
            ->orderBy("detalle_ingresos.id", "desc")
            ->get();

        return ["detalles" => $detalles];
    }

    public function registrarIngreso(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }

        try {
            DB::beginTransaction();

            $ultimaCaja = Caja::latest()->first();

            if (!$ultimaCaja || $ultimaCaja->estado != "1") {
                return response()->json(
                    [
                        "status" => "error",
                        "message" => "Debe tener una caja abierta",
                    ],
                    400
                );
            }

            if (!isset($request->form) || !is_array($request->form)) {
                throw new \Exception("Los datos del formulario no son válidos");
            }

            $ingreso = new Ingreso();
            $ingreso->idproveedor =
                $request->form["proveedorSeleccionado"]["id"] ?? null;
            $ingreso->idusuario = $request->usuario_actual_id ?? auth()->id();
            $ingreso->tipo_comprobante =
                $request->form["tipo_comprobante"]["nombre"] ??
                "No especificado";
            $ingreso->serie_comprobante =
                $request->form["serie_comprobante"] ?? "No especificado";
            $ingreso->num_comprobante =
                $request->form["num_comprobante"] ?? "No especificado";
            $ingreso->fecha_hora = now();
            $ingreso->impuesto = 0;
            $ingreso->total = $request->saldoTotalCompra ?? 0;
            $ingreso->tipoCompra = $request->tipoCompra["id"] ?? null;
            $ingreso->frecuencia_cuotas =
                $request->form_cuotas["frecuencia_pagos"] ?? 0;
            $ingreso->estado = true;
            $ingreso->idalmacen = $request->almacenSeleccionado["id"] ?? null;
            $ingreso->idcaja = $ultimaCaja->id;
            $ingreso->descuento_global = $request->descuento_global ?? 0;
            $ingreso->num_cuotas = $request->form_cuotas["num_cuotas"] ?? 0;
            $ingreso->cuota_inicial =
                $request->form_cuotas["cuota_inicial"] ?? 0;
            $ingreso->tipo_pago_cuota =
                $request->form_cuotas["tipoPagoCuotaSeleccionado"]["nombre"] ??
                "Ninguna";
            $ingreso->save();

            if ($ingreso->tipoCompra == 2) {
                if (!is_array($request->cuotaData)) {
                    throw new \Exception("Las cuotas no son un array válido");
                }

                foreach ($request->cuotaData as $cuotaObj) {
                    if (!is_array($cuotaObj)) {
                        throw new \Exception(
                            "Una de las cuotas no es un array válido"
                        );
                    }

                    $cuota = new IngresoCuota();
                    $cuota->idingreso = $ingreso->id;
                    $cuota->fecha_pago = $cuotaObj["fecha_pago"] ?? null;
                    $cuota->precio_cuota = $cuotaObj["precio_cuota"] ?? 0;
                    $cuota->total_cancelado = $cuotaObj["total_cancelado"] ?? 0;
                    $cuota->saldo_restante = $cuotaObj["saldo_restante"] ?? 0;
                    $cuota->fecha_cancelado =
                        $cuotaObj["fecha_cancelado"] ?? null;
                    $cuota->estado = $cuotaObj["estado"] ?? "Pendiente";
                    $cuota->save();
                }

                $ultimaCaja->comprasCredito += $ingreso->total;
            } else {
                $ultimaCaja->comprasContado += $ingreso->total;
            }
            $ultimaCaja->save();

            if (!is_array($request->array_articulos_completo)) {
                throw new \Exception("El array de artículos no es válido");
            }

            foreach ($request->array_articulos_completo as $articulo) {
                if (!is_array($articulo)) {
                    throw new \Exception(
                        "Uno de los artículos no es un array válido"
                    );
                }

                $detalle = new DetalleIngreso();
                $detalle->idingreso = $ingreso->id;
                $detalle->idarticulo = $articulo["id"] ?? null;
                $detalle->cantidad = $articulo["unidadesTotales"] ?? 0;
                $detalle->descuento = $articulo["descuento"] ?? 0;
                $detalle->precio = $articulo["subtotal"] ?? 0;
                $detalle->save();
            }

            DB::commit();

            return response()->json(
                [
                    "status" => "success",
                    "message" => "Compra registrada con exito",
                    "ingreso" => $ingreso,
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(
                [
                    "status" => "error",
                    "message" =>
                        "Error al registrar la compra: " . $e->getMessage(),
                    "line" => $e->getLine(),
                    "file" => $e->getFile(),
                ],
                500
            );
        }
    }

    public function store(Request $request)
{
    if (!$request->ajax()) {
        return redirect("/");
    }

    try {
        DB::beginTransaction();

        // Ya no se requiere que la caja esté abierta
        $ultimaCaja = Caja::latest()->first();

        $ingreso = new Ingreso();
        $ingreso->idusuario = \Auth::user()->id;
        $ingreso->tipo_comprobante = $request->tipo_comprobante;
        $ingreso->serie_comprobante = $request->serie_comprobante;
        $ingreso->num_comprobante = $request->num_comprobante;
        $ingreso->fecha_hora = now()->setTimezone("America/La_Paz");
        $ingreso->impuesto = $request->impuesto;
        $ingreso->total = $request->total;
        $ingreso->idalmacen = $request->idalmacen;
        $ingreso->estado = 1;
        $ingreso->idcaja = $ultimaCaja ? $ultimaCaja->id : null; // si hay caja, asigna el id, si no, deja null
        $ingreso->descuento_global = 0;

        $ingreso->save();

        // Si hay caja, actualiza el monto
        if ($ultimaCaja) {
            $ultimaCaja->comprasContado += $request->total;
            $ultimaCaja->save();
        }

        $detalles = $request->data;
        foreach ($detalles as $det) {
            $detalle = new DetalleIngreso();
            $detalle->idingreso = $ingreso->id;
            $detalle->idarticulo = $det["idarticulo"];
            $detalle->cantidad = $det["cantidad"];
            $detalle->descuento = 0;
            $detalle->precio = $det["precio"];
            $detalle->save();
        }

        // Notificaciones
        $fechaActual = date("Y-m-d");
        $numVentas = DB::table("ventas")->whereDate("created_at", $fechaActual)->count();
        $numIngresos = DB::table("ingresos")->whereDate("created_at", $fechaActual)->count();

        $arregloDatos = [
            "ventas" => ["numero" => $numVentas, "msj" => "Ventas"],
            "ingresos" => ["numero" => $numIngresos, "msj" => "Ingresos"],
        ];

        foreach (User::all() as $notificar) {
            User::findOrFail($notificar->id)->notify(new NotifyAdmin($arregloDatos));
        }

        DB::commit();

        return [
            "id" => $ingreso->id,
        ];
    } catch (Exception $e) {
        DB::rollBack();
        \Log::error("Error en el proceso de registro de compra", ["error" => $e->getMessage()]);
        return response()->json(["error" => $e->getMessage()], 500);
    }
}

    public function desactivar(Request $request)
    {
        if (!$request->ajax()) {
            return redirect("/");
        }
        $ingreso = Ingreso::findOrFail($request->id);
        $ingreso->estado = 0;
        $ingreso->save();
    }
}
