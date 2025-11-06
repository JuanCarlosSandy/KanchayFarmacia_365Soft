<?php

namespace App\Http\Controllers;

use App\CreditoVenta;

use Illuminate\Http\Request;
use App\Persona;
use App\Exports\ClientExport;
use App\Imports\ClienteImport;
use App\User;
use App\Venta;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ClienteController extends Controller
{
    public function index(Request $request)
    {
        $buscar = $request->buscar;

        $idsExcluidos = DB::table('users')->select('id')
            ->union(DB::table('proveedores')->select('id'));

        $usuarios = Persona::whereNotIn('id', $idsExcluidos)
            ->when(!empty($buscar), function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    $q->where('nombre', 'like', "%{$buscar}%")
                        ->orWhere('num_documento', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
                        ->orWhere('telefono', 'like', "%{$buscar}%");
                });
            })
            ->orderBy('id', 'desc')
            ->get();

        return [
            'total' => $usuarios->count(),
            'usuarios' => $usuarios
        ];
    }



    public function selectCliente(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $filtro = $request->filtro;
        $clientes = Persona::where('nombre', 'like', '%' . $filtro . '%')
            ->whereNull('direccion')
            ->where('usuario', '>', 0)
            ->select('id', 'nombre', 'tipo_documento', 'num_documento', 'complemento_id', 'email', 'telefono')
            ->orderBy('nombre', 'asc')
            ->take(5)
            ->get();

        $clientesConCreditos = $clientes->map(function ($cliente) {
            $cantidadCreditos = CreditoVenta::where('idcliente', $cliente->id)
                ->where('estado', '!=', 'Completado')
                ->count();
            $cliente->cantidad_creditos = $cantidadCreditos;
            return $cliente;
        });

        return ['clientes' => $clientesConCreditos];
    }


    public function seleccionarClientePorNumero(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $filtro = $request->numero;
        $clientes = Persona::where('num_documento', 'like', '%' . $filtro . '%')
            ->whereNull('direccion')
            ->where('usuario', '>', 0)
            ->select('id', 'nombre', 'tipo_documento', 'num_documento', 'email', 'telefono')
            ->orderBy('tipo_documento', 'desc')
            ->take(5)
            ->get();
        $clientesConCreditos = $clientes->map(function ($cliente) {
            $cantidadCreditos = CreditoVenta::where('idcliente', $cliente->id)
                ->where('estado', '!=', 'Completado')
                ->count();

            $cliente->cantidad_creditos = $cantidadCreditos;
            return $cliente;
        });

        return ['clientes' => $clientesConCreditos];

        //return ['clientes' => $clientes];
    }

    public function store(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        // Verificar si ya existe un cliente con este número de documento
        $clienteExistente = Persona::where('num_documento', $request->num_documento)->first();

        if ($clienteExistente) {
            // Si ya existe, devolver ese ID
            return ['id' => $clienteExistente->id];
        }

        // Si no existe, crear uno nuevo
        $persona = new Persona();
        $persona->nombre = $request->nombre;
        $persona->usuario = Auth::user()->iduse;
        $persona->tipo_documento = $request->tipo_documento ?? null;
        $persona->num_documento = $request->num_documento ?? null;
        $persona->complemento_id = $request->complemento ?? null;
        $persona->telefono = $request->telefono ?? null;
        $persona->save();

        return ['id' => $persona->id];
    }

    public function update(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');
        $persona = Persona::findOrFail($request->id);
        $persona->nombre = $request->nombre;
        $persona->usuario = $request->usuariodos_id;
        $persona->tipo_documento = $request->tipo_documento;
        $persona->num_documento = $request->num_documento;
        $persona->complemento_id = $request->complemento;
        $persona->telefono = $request->telefono;
        $persona->save();
        Log::info('DAtOS ACTU8ALIZAR!!:', [
            'DATOS' => $persona,
        ]);
    }

    public function listarReporteClienteExcel()
    {
        return Excel::download(new ClientExport, 'clientes.xlsx');
    }

    //---seleccionar usuario vendedor--
    public function selectUsuarioVendedor(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $filtro = $request->filtro;
        $clientes = User::join('personas', 'users.id', '=', 'personas.id')
            ->select(
                'personas.id as ID',
                'personas.nombre',
                'users.idrol',
                'users.iduse as ID_use'
            )->where('users.idrol', '=', 2)
            ->orWhere('personas.nombre', 'like', '%' . $filtro . '%')
            ->orderBy('personas.nombre', 'asc')
            //->toSql();
            ->get();

        return ['clientes' => $clientes];
    }
    //---listado por id lo que se pidio de Personana--
    public function indexUsuario(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        $idusuario = $request->idusuario;
        $usuario = User::join('personas', 'users.id', '=', 'personas.id')
            //->join('roles', 'users.idrol', '=', 'roles.id')
            ->select(
                'personas.id as ID',
                'personas.nombre',
                'personas.usuario',
                //'users.iduse as ID_use'
            )
            //->where('personas.usuario', '=', $idusuario)->get();
            ->where('users.iduse', '=', $idusuario)->get();
        return ['usuario' => $usuario];
    }
    //---listado por id lo que se pidio de Personana--
    public function indexUsuarioFiltro(Request $request)
    {
        if (!$request->ajax())
            return redirect('/');

        //$idusuario = $request->idusuario;
        $filtro = $request->filtro;
        $usuariodos = User::join('personas', 'users.id', '=', 'personas.id')
            //->join('roles', 'users.idrol', '=', 'roles.id')
            ->select(
                'personas.nombre',
                'personas.usuario',
                'users.iduse'
            )
            //->where('personas.usuario', '=', $idusuario)->get();
            ->where('users.idrol', '=', 2)
            ->orWhere('personas.nombre', 'like', '%' . $filtro . '%')
            ->orderBy('personas.nombre', 'asc')
            //->toSql();
            ->get();

        return ['usuariodos' => $usuariodos];
    }

    public function getUserInfo()
    {
        $user = Auth::user();
        return response()->json(['user' => $user]);
    }
    // public function selectUsuarioVendedor(Request $request){
    //     if (!$request->ajax()) return redirect('/');

    //     $buscar = $request->buscar;
    //     $criterio = $request->criterio;

    //     if ($buscar==''){
    //         $personas = User::join('personas', 'users.id', '=', 'personas.id')
    //             ->join('roles', 'users.idrol', '=', 'roles.id')
    //             ->select(
    //                 'personas.id as ID', 'personas.nombre',
    //                 'roles.id',
    //                 )  ->where('roles.id', '=', '2')->paginate(6);
    //         Persona::orderBy('id', 'desc')->paginate(6);
    //     }
    //     else{
    //         $personas = User::join('personas', 'users.id', '=', 'personas.id')
    //         ->join('roles', 'users.idrol', '=', 'roles.id')
    //         ->select(
    //             'personas.id', 'personas.nombre',
    //             'roles.id as ID',
    //             )  ->where('roles.id', '=', '2')->paginate(6);        }


    //     return [
    //         'pagination' => [
    //             'total'        => $personas->total(),
    //             'current_page' => $personas->currentPage(),
    //             'per_page'     => $personas->perPage(),
    //             'last_page'    => $personas->lastPage(),
    //             'from'         => $personas->firstItem(),
    //             'to'           => $personas->lastItem(),
    //         ],
    //         'personas' => $personas
    //     ];
    // }

    public function importarCliente(Request $request)
    {
        $path = $request->file('select_users_file')->getRealPath();
        Excel::import(new ClienteImport, $path);
    }

    public function verificarExistencia(Request $request)
    {
        //$venta = Venta::findOrFail($request->documento);
        //$documento = $venta->cliente->num_documento;
        $documento = $request->documento;
        $cliente = Persona::where('num_documento', $documento)->first();

        if ($cliente) {
            return response()->json(['existe' => true, 'cliente' => $cliente]);
        } else {
            return response()->json(['existe' => false]);
        }
    }

    public function verificarExistencia2(Request $request)
    {
        $venta = Venta::findOrFail($request->documento);
        $documento = $venta->cliente->num_documento;
        $cliente = Persona::where('num_documento', $documento)->first();

        if ($cliente) {
            return response()->json(['existe' => true, 'cliente' => $cliente]);
        } else {
            return response()->json(['existe' => false]);
        }
    }

  public function buscarPorDocumento(Request $request)
    {
        $busqueda = $request->query('documento');

        if (!$busqueda || trim($busqueda) === '') {
            return response()->json([], 400); // evita consultas vacías
        }

        // IDs a excluir (usuarios y proveedores)
        $idsExcluidos = DB::table('users')->select('id')
            ->union(DB::table('proveedores')->select('id'));

        // Separa las palabras del texto de búsqueda (soporta “juan sandy 789”)
        $palabras = preg_split('/\s+/', trim($busqueda));

        $clientes = Persona::whereNotIn('id', $idsExcluidos)
            ->where(function ($query) use ($palabras) {
                foreach ($palabras as $palabra) {
                    $query->where(function ($subquery) use ($palabra) {
                        $subquery->where('nombre', 'LIKE', "%{$palabra}%")
                            ->orWhere('num_documento', 'LIKE', "%{$palabra}%")
                            ->orWhere('telefono', 'LIKE', "%{$palabra}%")
                            ->orWhere('email', 'LIKE', "%{$palabra}%");
                    });
                }
            })
            ->limit(10)
            ->get();

        if ($clientes->isNotEmpty()) {
            return response()->json($clientes);
        } else {
            return response()->json([], 404);
        }
    }

}
