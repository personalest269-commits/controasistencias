<?php

namespace App\Http\Controllers;

use App\Models\AdArchivoDigital;
use App\Models\PgEstadoCivil;
use App\Models\PgDepartamento;
use App\Models\PgPersona;
use App\Models\PgPersonaFoto;
use App\Models\PgTipoIdentificacion;
use App\Models\PgEmpresa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\Role;

class PersonasController extends Controller
{
    private function isValidCedulaEc(string $cedula): bool
    {
        $cedula = trim($cedula);
        if (!preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }

        $provincia = (int) substr($cedula, 0, 2);
        if ($provincia < 1 || $provincia > 24) {
            return false;
        }

        $tercer = (int) $cedula[2];
        if ($tercer > 5) {
            return false;
        }

        $suma = 0;
        for ($i = 0; $i < 9; $i++) {
            $d = (int) $cedula[$i];
            if ($i % 2 === 0) {
                $d *= 2;
                if ($d > 9) {
                    $d -= 9;
                }
            }
            $suma += $d;
        }

        $verificador = (10 - ($suma % 10)) % 10;
        return $verificador === (int) $cedula[9];
    }

    /**
     * Genera el valor de pg_usuario.usuario a partir de la identificación y el tipo seleccionado.
     * - CÉDULA/RUC: solo dígitos
     * - PASAPORTE u otros: alfanumérico (A-Z/0-9) en mayúsculas, sin espacios/guiones
     */
    private function usuarioFromIdentificacion(?string $tipoCodigo, ?string $identificacion): string
    {
        $tipoCodigo = trim((string) $tipoCodigo);
        $identificacion = trim((string) $identificacion);

        if ($identificacion === '') {
            return '';
        }

        $tipo = null;
        if ($tipoCodigo !== '') {
            $tipo = PgTipoIdentificacion::where('codigo', $tipoCodigo)->first();
        }

        $desc = strtoupper(trim((string) ($tipo->descripcion ?? '')));
        $esNumerico = ((int) ($tipo->validar ?? 0) === 1) || str_contains($desc, 'RUC') || str_contains($desc, 'R.U.C');

        if ($esNumerico) {
            return preg_replace('/\\D+/', '', $identificacion);
        }

        $alnum = preg_replace('/[^A-Za-z0-9]/', '', $identificacion);
        return strtoupper($alnum);
    }

    /**
     * El ID de pg_persona se genera en la base de datos mediante TRIGGER.
     * (Ver migration: create_pg_control_and_persona_trigger)
     */

    private function validarIdentificacionSegunTipo(?string $tipoCodigo, ?string $identificacion): void
    {
        $tipoCodigo = trim((string) $tipoCodigo);
        $identificacion = trim((string) $identificacion);

        if ($tipoCodigo === '' || $identificacion === '') {
            return;
        }

        $tipo = PgTipoIdentificacion::where('codigo', $tipoCodigo)->first();
        if (!$tipo) {
            return;
        }

        // Longitud
        if (!is_null($tipo->longitud) && $tipo->longitud > 0) {
            if ((int) $tipo->longitud_fija === 1 && strlen($identificacion) !== (int) $tipo->longitud) {
                throw ValidationException::withMessages([
                    'identificacion' => "La identificación debe tener exactamente {$tipo->longitud} caracteres para {$tipo->descripcion}.",
                ]);
            }
            if ((int) $tipo->longitud_fija !== 1 && strlen($identificacion) > (int) $tipo->longitud) {
                throw ValidationException::withMessages([
                    'identificacion' => "La identificación no debe exceder {$tipo->longitud} caracteres para {$tipo->descripcion}.",
                ]);
            }
        }

        // Validación especial (en la tabla: validar=1 para CÉDULA)
        if ((int) $tipo->validar === 1) {
            if (!$this->isValidCedulaEc($identificacion)) {
                throw ValidationException::withMessages([
                    'identificacion' => 'La cédula ingresada no es válida. Verifica los 10 dígitos.',
                ]);
            }
        }
    }
    private function normalizeExt($ext): string
    {
        $ext = strtolower(trim((string) $ext));
        return ltrim($ext, '.');
    }

    private function parseFechaNacimiento(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        // Formato esperado: dd/mm/aaaa
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            try {
                return Carbon::createFromFormat('d/m/Y', $value)->format('Y-m-d');
            } catch (\Throwable $e) {
                throw ValidationException::withMessages([
                    'fecha_nacimiento' => 'La fecha de nacimiento no es válida. Usa el formato dd/mm/aaaa.',
                ]);
            }
        }

        // Fallback: si viene en formato YYYY-MM-DD u otro parseable
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'fecha_nacimiento' => 'La fecha de nacimiento no es válida. Usa el formato dd/mm/aaaa.',
            ]);
        }
    }


    public function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        // Symfony 7.4+ deprecates Request::get(), use query/request bags explicitly.
        $soloEliminados = $request->query('eliminados') == 1;
        $soloInactivos = $request->query('inactivos') == 1;
        $q = trim((string) $request->query('q', ''));
        $departamentoId = trim((string) $request->query('departamento_id', ''));
        $empresaId = trim((string) $request->query('empresa_id', ''));
        if ($departamentoId === '') {
            $departamentoId = null;
        }
        if ($empresaId === '') {
            $empresaId = null;
        }

        $query = PgPersona::with(['fotoActual.archivo']);

        // Filtros principales
        if ($soloEliminados) {
            $query = $query->soloEliminados();
        } else {
            // Activos (vigente='S' o NULL) por defecto, o inactivos (vigente='N') si lo pide
            if ($soloInactivos) {
                $query->where('vigente', 'N');
            } else {
                $query->where(function ($q) {
                    $q->where('vigente', 'S')->orWhereNull('vigente');
                });
            }
        }

        $query->orderByDesc('fecha_ingreso');

        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('id', 'like', "%{$q}%")
                    ->orWhere('identificacion', 'like', "%{$q}%")
                    ->orWhere('nombres', 'like', "%{$q}%")
                    ->orWhere('apellido1', 'like', "%{$q}%")
                    ->orWhere('apellido2', 'like', "%{$q}%")
                    ->orWhereHas('departamento', function ($dq) use ($q) {
                        $dq->where('descripcion', 'like', "%{$q}%");
                    });
            });
        }
		if ($departamentoId) {
    $query->where('departamento_id', $departamentoId);
}

        if ($empresaId) {
            $query->where('empresa_id', $empresaId);
        }

        // Ejecutar consulta (paginado) ANTES de calcular reactivados
        $personas = $query->paginate(20)->appends($request->query());

        // Reactivados: vigente cambió de N -> S en algún batch (últimos logs)
        $ids = $personas->getCollection()->pluck('identificacion')->filter()->unique()->values()->all();
        $reactivados = [];

        if (!empty($ids)) {
            $rows = DB::table('pg_importacion_logs')
                ->whereIn('identificacion', $ids)
                ->where('accion', 'UPDATE')
                ->whereRaw("JSON_EXTRACT(before_json, '$.vigente') = 'N'")
                ->whereRaw("JSON_EXTRACT(after_json, '$.vigente') = 'S'")
                ->orderByDesc('id')
                ->get();

            foreach ($rows as $r) {
                $reactivados[$r->identificacion] = $r->batch_id;
            }
        }



       
$tiposIdentificacion = PgTipoIdentificacion::orderBy('descripcion')->get();
        $estadosCiviles = PgEstadoCivil::orderBy('descripcion')->get();

        $departamentos = PgDepartamento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->whereNull('vigencia_hasta')
            ->orderBy('descripcion')
            ->get();

        $empresas = collect();
        if (Schema::hasTable('pg_empresa')) {
            $empresas = PgEmpresa::query()->orderBy('nombre')->get(['id', 'nombre']);
        }

        $empresas = collect();
        if (Schema::hasTable('pg_empresa')) {
            $empresas = PgEmpresa::query()
                ->orderBy('nombre')
                ->get(['id', 'nombre']);
        }

        // Roles (para seleccionar rol cuando se crea usuario desde Persona)
        $rolesQuery = DB::table('roles')->select('id', 'name', 'display_name');
        if (Schema::hasColumn('roles', 'estado')) {
            $rolesQuery->whereNull('estado');
        }

        // Si NO es Super Admin, ocultar el rol Super Admin (para combos)
        $authUser = auth()->user();
        $isSuper = $authUser && ($authUser->hasRole('Super Admin') || $authUser->hasRole('Super-Admin'));
        if (!$isSuper) {
            $rolesQuery->whereNotIn('name', ['Super Admin','Super-Admin']);
            if (Schema::hasColumn('roles', 'display_name')) {
                $rolesQuery->whereNotIn('display_name', ['Super Admin','Super-Admin']);
            }
        }

        // Si NO es Super Admin, ocultar el rol Super Admin (para combos)
        $authUser = auth()->user();
        $isSuper = $authUser && ($authUser->hasRole('Super Admin') || $authUser->hasRole('Super-Admin'));
        if (!$isSuper) {
            // Filtrar por name/display_name (segun exista)
            $rolesQuery->where(function($q){
                $q->whereRaw('LOWER(name) <> ?', ['super admin'])
                  ->whereRaw('LOWER(name) <> ?', ['super-admin']);
            });
            if (Schema::hasColumn('roles', 'display_name')) {
                $rolesQuery->where(function($q){
                    $q->whereRaw('LOWER(display_name) <> ?', ['super admin'])
                      ->whereRaw('LOWER(display_name) <> ?', ['super-admin']);
                });
            }
        }
        $roles = $rolesQuery
            ->orderByRaw("COALESCE(display_name, name) ASC")
            ->get();

        return view('personas.index', [
            'personas' => $personas,
            'soloEliminados' => $soloEliminados,
            'soloInactivos' => $soloInactivos,
            'q' => $q,
            'departamentoId' => $departamentoId,
            'empresaId' => $empresaId,
            'tiposIdentificacion' => $tiposIdentificacion,
            'estadosCiviles' => $estadosCiviles,
            'departamentos' => $departamentos,
            'empresas' => $empresas,
            'roles' => $roles,
            'reactivados' => $reactivados,
        ]);
    }

    public function Store(Request $request)
{
    $crearUsuario = $request->boolean('crear_usuario');

    // =========================
    // Reglas de PERSONA (siempre)
    // =========================
    $rules = [
        'empresa_id' => ['required', 'string', 'max:10', Rule::exists('pg_empresa', 'id')->whereNull('estado')],
        'tipo' => ['required', Rule::in(['N', 'J'])],
        'nombres' => ['required', 'string', 'max:255'],
        'apellido1' => ['required', 'string', 'max:20'],
        'apellido2' => ['nullable', 'string', 'max:20'],
        'direccion' => ['nullable', 'string', 'max:255'],
        'departamento_id' => ['nullable', 'string', 'max:10', Rule::exists('pg_departamento', 'id')->whereNull('estado')],
        'fecha_nacimiento' => ['nullable', 'regex:/^\d{2}\/\d{2}\/\d{4}$/'],

        'tipo_identificacion' => [
            'required',
            'string',
            'max:5',
            Rule::exists('pg_tipo_identificacion', 'codigo')->whereNull('estado')
        ],
        // Validar duplicados solo contra registros ACTIVOS (estado IS NULL)
        'identificacion' => [
            'required',
            'string',
            'max:15',
            Rule::unique('pg_persona', 'identificacion')->where(function ($q) {
                $q->whereNull('estado');
            })
        ],

        'sexo' => ['nullable', 'string', 'size:1', Rule::in(['M', 'F'])],
        'celular' => ['nullable', 'string', 'max:30'],
        'email' => ['nullable', 'email', 'max:50'],
        'cod_estado_civil' => [
            'nullable',
            'string',
            'max:5',
            Rule::exists('pg_estado_civil', 'codigo')->whereNull('estado')
        ],

        'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

        'crear_usuario' => ['nullable', 'in:1'],
    ];

    // =========================
    // Reglas de USUARIO (solo si marcó "Crear usuario")
    // =========================
    if ($crearUsuario) {
        // El usuario se genera desde la identificación según el tipo seleccionado.
        // La longitud/formato se valida por catálogo (pg_tipo_identificacion)
        // y el duplicado en pg_usuario se valida en after().

        $rules['usuario_password'] = ['required', 'string', 'min:6'];

        // Rol del usuario (seleccionado en el formulario)
        $roleExists = Rule::exists('roles', 'id');
        if (Schema::hasColumn('roles', 'estado')) {
            $roleExists = $roleExists->whereNull('estado');
        }
        // IDs de roles son VARCHAR(10) (ej: 0000000001). No validar como integer.
        $rules['usuario_role_id'] = ['required', 'string', $roleExists];
    } else {
        // Si NO crea usuario, ignorar campos de usuario aunque vengan en el request
        $request->request->remove('usuario_password');
        $request->request->remove('usuario_role_id');
    }

        $messages = [
        'tipo.required' => 'El campo Tipo es obligatorio.',
        'nombres.required' => 'El campo Nombres es obligatorio.',
        'apellido1.required' => 'El campo Apellido 1 es obligatorio.',
        'tipo_identificacion.required' => 'El campo Tipo identificación es obligatorio.',
        'identificacion.required' => 'El campo Identificación es obligatorio.',
        'identificacion.unique' => 'Ya existe una persona registrada con esa identificación.',
        'fecha_nacimiento.regex' => 'La fecha de nacimiento debe tener el formato dd/mm/aaaa.',

        'identificacion.digits' => 'Para crear usuario, la identificación debe tener 10 dígitos (cédula).',
        'identificacion.unique' => 'Ya existe un usuario activo con esa cédula.',

        'usuario_password.required' => 'Falta rellenar: Contraseña del usuario.',
        'usuario_password.min' => 'La contraseña del usuario debe tener al menos 6 caracteres.',

        'usuario_role_id.required' => 'Falta seleccionar: Rol del usuario.',
        'usuario_role_id.exists' => 'El rol seleccionado no es válido.',
    ];

    $validator = Validator::make($request->all(), $rules, $messages);

    $validator->after(function ($v) use ($request, $crearUsuario) {

        // =========================
        // Mensaje resumen: campos usuario faltantes (cuando aplica)
        // =========================
        if (!$crearUsuario) {
            return;
        }

        $missing = [];
        if (blank($request->input('usuario_role_id'))) {
            $missing[] = 'Rol del usuario';
        }
        if (blank($request->input('usuario_password'))) {
            $missing[] = 'Contraseña del usuario';
        }

        // Validación de duplicado de usuario (pg_usuario.usuario) según tipo seleccionado
        $usuario = $this->usuarioFromIdentificacion($request->input('tipo_identificacion'), $request->input('identificacion'));
        if ($usuario !== '' && User::where('usuario', $usuario)->whereNull('estado')->exists()) {
            $v->errors()->add('identificacion', 'Ya existe un usuario activo con ese usuario.');
        }

        if (!empty($missing)) {
            $v->errors()->add('crear_usuario', 'Para crear el usuario falta rellenar: ' . implode(', ', $missing) . '.');
        }
    });

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput()
            ->with('open_modal_nueva_persona', 1);
    }

    // Validación adicional según tipo de identificación seleccionado
    try {
        $this->validarIdentificacionSegunTipo($request->tipo_identificacion, $request->identificacion);
    } catch (ValidationException $e) {
        return redirect()->back()
            ->withErrors($e->errors())
            ->withInput()
            ->with('open_modal_nueva_persona', 1);
    }

    try {
        $successMsg = DB::transaction(function () use ($request, $crearUsuario) {
        // Limpiar variable de sesión por seguridad (misma conexión)
        try {
            DB::statement('SET @last_persona_id = NULL');
        } catch (\Throwable $e) {
            // ignore
        }

        $persona = new PgPersona();
        // IMPORTANTE: enviamos NULL para que el trigger genere el id
        $persona->id = null;
        $persona->empresa_id = $request->input('empresa_id');
	        $persona->tipo = $request->tipo;
	        $persona->empresa_id = $request->input('empresa_id');
        $persona->nombres = $request->nombres;
        $persona->apellido1 = $request->apellido1;
        $persona->apellido2 = $request->apellido2;
        $persona->direccion = $request->direccion;
        $persona->fecha_nacimiento = $this->parseFechaNacimiento($request->fecha_nacimiento);

        // Guardamos el CÓDIGO seleccionado (ej: 2 = CÉDULA, 1 = RUC, etc.)
        $persona->tipo_identificacion = $request->tipo_identificacion;
        $persona->identificacion = $request->identificacion;

        $persona->sexo = $request->sexo;
        $persona->celular = $request->celular;
        $persona->email = $request->email;
        $persona->cod_estado_civil = $request->cod_estado_civil;
        $persona->departamento_id = $request->input('departamento_id') ?: null;

        // Asegurar que quede visible en el listado (Index filtra vigente='S')
        if (Schema::hasColumn('pg_persona', 'vigente') && empty($persona->vigente)) {
            $persona->vigente = 'S';
        }
        if (Schema::hasColumn('pg_persona', 'fecha_ingreso') && empty($persona->fecha_ingreso)) {
            $persona->fecha_ingreso = Carbon::now();
        }

        $persona->estado = null;
        $persona->save();

        // Recuperar el ID generado por el trigger (MySQL/MariaDB)
        $row = null;
        try {
            $row = DB::selectOne('SELECT @last_persona_id AS id');
        } catch (\Throwable $e) {
            // ignore
        }

        $generatedId = $row->id ?? null;
        if (!$generatedId) {
            throw new \RuntimeException('No se pudo obtener el ID generado por la base de datos (trigger).');
        }
        $persona->id = $generatedId;

        // Si se adjunta foto, se guarda el archivo en ad_archivo_digital.digital (cifrado) y se relaciona
        if ($request->file('foto')) {
            $file = $request->file('foto');
            $binary = @file_get_contents($file->getRealPath());

            if ($binary !== false) {
                $encrypted = Crypt::encryptString(base64_encode($binary));

                $archivo = new AdArchivoDigital();
                $archivo->tipo_documento_codigo = null;
                $archivo->tipo_archivo_codigo = null;
                $archivo->nombre_original = $file->getClientOriginalName();
                $archivo->ruta = '';
                $archivo->digital = $encrypted;
                $archivo->tipo_mime = $file->getClientMimeType();
                $archivo->extension = $this->normalizeExt($file->getClientOriginalExtension());
                $archivo->tamano = (int) $file->getSize();
                $archivo->descripcion = 'Foto persona ' . $persona->id;
                $archivo->estado = null;
                $archivo->save();

                $foto = new PgPersonaFoto();
                $foto->id_persona = $persona->id;
                $foto->id_archivo = $archivo->id;
                $foto->estado = null;
                $foto->save();
            }
        }

        $msg = 'Persona creada correctamente.';

        // Crear usuario (solo si marcó checkbox)
        if ($crearUsuario) {
            $usuarioLogin = $this->usuarioFromIdentificacion($persona->tipo_identificacion, $persona->identificacion);
            $emailUser = trim((string) $persona->email);
            $plainPassword = (string) $request->usuario_password;

            if ($usuarioLogin === '') {
                throw ValidationException::withMessages([
                    'identificacion' => 'No se pudo generar el usuario desde la identificación.',
                ]);
            }

            if (User::where('usuario', $usuarioLogin)->whereNull('estado')->exists()) {
                throw ValidationException::withMessages([
                    'identificacion' => 'Ya existe un usuario activo con ese usuario.',
                ]);
            }

            $user = new User();
            $user->id_persona = $persona->id;
            $user->name = $persona->nombre_completo ?: ('Persona ' . $persona->id);
            $user->usuario = $usuarioLogin;
            $user->email = $emailUser !== '' ? $emailUser : null;
            $user->password = Hash::make($plainPassword);
            $user->image = 'photos/img.jpg';
            // IMPORTANTE: el ID del usuario se genera por TRIGGER (VARCHAR(10)).
            // Eloquent NO conoce ese ID luego del insert, por eso debemos leer @last_usuario_id
            // en la MISMA conexión y asignarlo antes de guardar el rol.
            try {
                $user->getConnection()->statement('SET @last_usuario_id = NULL');
            } catch (\Throwable $e) {
                // ignore
            }

            $user->save();

            // Recuperar el ID generado por el trigger
            if (empty($user->id)) {
                try {
                    $row = $user->getConnection()->selectOne('SELECT @last_usuario_id AS id');
                    if ($row && !empty($row->id)) {
                        $user->id = (string) $row->id;
                    } else {
                        // Fallback: recuperar por usuario
                        $fallback = $user->getConnection()->selectOne(
                            'SELECT id FROM pg_usuario WHERE usuario = ? ORDER BY created_at DESC, id DESC LIMIT 1',
                            [$user->usuario]
                        );
                        if ($fallback && !empty($fallback->id)) {
                            $user->id = (string) $fallback->id;
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            if (empty($user->id)) {
                throw new \RuntimeException('No se pudo determinar el ID generado del usuario para asignar el rol.');
            }

            // Asignar rol seleccionado (solo uno)
            // Asignar rol seleccionado (solo uno). NO castear a int (pierde ceros).
            $roleId = trim((string) ($request->input('usuario_role_id') ?? ''));
            if ($roleId === '') {
                // Fallback: primer rol activo
                $roleId = (string) (DB::table('roles')->when(Schema::hasColumn('roles','estado'), function($q){ $q->whereNull('estado'); })
                    ->orderBy('id')->value('id') ?? '');
            }
            try {
                // Si Entrust está bien configurado, esto debe funcionar.
                $user->roles()->sync([$roleId]);
            } catch (\Throwable $e) {
                // Fallback directo al pivot (por si Entrust/relación no está usando usuario_id)
                DB::table('role_user')->where('usuario_id', $user->id)->delete();
                DB::table('role_user')->insert([
                    'usuario_id' => (string) $user->id,
                    'role_id' => (string) $roleId,
                ]);
            }

            $msg .= " Usuario creado ({$usuarioLogin}).";
        }

        return $msg;
    });
    } catch (ValidationException $e) {
        return redirect()->back()
            ->withErrors($e->errors())
            ->withInput()
            ->with('open_modal_nueva_persona', 1);
    } catch (\Throwable $e) {
        return redirect()->back()
            ->withErrors(['general' => 'No se pudo guardar la persona. Error: ' . $e->getMessage()])
            ->withInput()
            ->with('open_modal_nueva_persona', 1);
    }

    return redirect()->route('PersonasIndex')->with('success', $successMsg);
}


    public function Edit($id)
    {
        $persona = PgPersona::with(['fotoActual.archivo', 'usuarios'])
            ->conEliminados()
            ->where('id', $id)
            ->firstOrFail();

        $tiposIdentificacion = PgTipoIdentificacion::orderBy('descripcion')->get();
        $estadosCiviles = PgEstadoCivil::orderBy('descripcion')->get();

        $departamentos = PgDepartamento::query()
            ->where(function ($q) {
                $q->whereNull('estado')->orWhere('estado', '<>', 'X');
            })
            ->whereNull('vigencia_hasta')
            ->orderBy('descripcion')
            ->get();

        $rolesQuery = DB::table('roles')->select('id', 'name', 'display_name');
        if (Schema::hasColumn('roles', 'estado')) {
            $rolesQuery->whereNull('estado');
        }

        // Si NO es Super Admin, ocultar el rol Super Admin (para combos)
        $authUser = auth()->user();
        $isSuper = $authUser && ($authUser->hasRole('Super Admin') || $authUser->hasRole('Super-Admin'));
        if (!$isSuper) {
            $rolesQuery->whereNotIn('name', ['Super Admin','Super-Admin']);
            if (Schema::hasColumn('roles', 'display_name')) {
                $rolesQuery->whereNotIn('display_name', ['Super Admin','Super-Admin']);
            }
        }
        $roles = $rolesQuery->orderByRaw("COALESCE(display_name, name) ASC")->get();

        // Empresas (para selector en formulario)
        $empresas = [];
        if (Schema::hasTable('pg_empresa')) {
            $empresas = PgEmpresa::query()->orderBy('nombre')->get(['id', 'nombre']);
        }

        return view('personas.edit', [
            'persona' => $persona,
            'tiposIdentificacion' => $tiposIdentificacion,
            'estadosCiviles' => $estadosCiviles,
            'departamentos' => $departamentos,
            'empresas' => $empresas,
            'roles' => $roles,
        ]);
    }

    public function Update(Request $request, $id)
{
    $persona = PgPersona::conEliminados()->where('id', $id)->firstOrFail();

    $crearUsuario = $request->boolean('crear_usuario'); // en edición se usa para "Agregar usuario"

    // =========================
    // Reglas de PERSONA (siempre)
    // =========================
    $rules = [
        'empresa_id' => ['required', 'string', 'max:10', Rule::exists('pg_empresa', 'id')->whereNull('estado')],
        'tipo' => ['required', Rule::in(['N', 'J'])],
        'nombres' => ['required', 'string', 'max:255'],
        'apellido1' => ['required', 'string', 'max:20'],
        'apellido2' => ['nullable', 'string', 'max:20'],
        'direccion' => ['nullable', 'string', 'max:255'],
        'departamento_id' => ['nullable', 'string', 'max:10', Rule::exists('pg_departamento', 'id')->whereNull('estado')],
        'fecha_nacimiento' => ['nullable', 'regex:/^\d{2}\/\d{2}\/\d{4}$/'],

        'tipo_identificacion' => [
            'required',
            'string',
            'max:5',
            Rule::exists('pg_tipo_identificacion', 'codigo')->whereNull('estado')
        ],
        // Validar duplicados solo contra registros ACTIVOS (estado IS NULL)
        'identificacion' => [
            'required',
            'string',
            'max:15',
            Rule::unique('pg_persona', 'identificacion')
                ->where(function ($q) {
                    $q->whereNull('estado');
                })
                ->ignore($persona->id, 'id'),
        ],

        'sexo' => ['nullable', 'string', 'size:1', Rule::in(['M', 'F'])],
        'celular' => ['nullable', 'string', 'max:30'],
        'email' => ['nullable', 'email', 'max:50'],
        'cod_estado_civil' => [
            'nullable',
            'string',
            'max:5',
            Rule::exists('pg_estado_civil', 'codigo')->whereNull('estado')
        ],

        'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

        // checkbox de "Agregar usuario"
        'crear_usuario' => ['nullable', 'in:1'],
    ];

    // =========================
    // Reglas de USUARIO
    //  - Si marcó "Agregar usuario": valida y crea usuario
    //  - Si NO marcó: solo sincroniza correo (si ya existe usuario)
    // =========================
    if ($crearUsuario) {
        // Si ya tiene usuario, bloquear creación
        if ($persona->usuarios()->exists()) {
            throw ValidationException::withMessages([
                'crear_usuario' => 'Esta persona ya tiene un usuario asociado.',
            ]);
        }

        // El usuario se genera desde la identificación según el tipo seleccionado.
        // La longitud/formato se valida por catálogo (pg_tipo_identificacion)
        // y el duplicado en pg_usuario se valida en after().

        $rules['usuario_password'] = ['required', 'string', 'min:6'];

        // Rol del usuario (seleccionado en el formulario)
        $roleExists = Rule::exists('roles', 'id');
        if (Schema::hasColumn('roles', 'estado')) {
            $roleExists = $roleExists->whereNull('estado');
        }
        // IDs de roles son VARCHAR(10) (ej: 0000000001). No validar como integer.
        $rules['usuario_role_id'] = ['required', 'string', $roleExists];
    } else {
        $request->request->remove('usuario_password');
        $request->request->remove('usuario_role_id');
    }

    $messages = [
        'tipo.required' => 'El campo Tipo es obligatorio.',
        'nombres.required' => 'El campo Nombres es obligatorio.',
        'apellido1.required' => 'El campo Apellido 1 es obligatorio.',
        'tipo_identificacion.required' => 'El campo Tipo identificación es obligatorio.',
        'identificacion.required' => 'El campo Identificación es obligatorio.',
        'identificacion.unique' => 'Ya existe una persona registrada con esa identificación.',
        'fecha_nacimiento.regex' => 'La fecha de nacimiento debe tener el formato dd/mm/aaaa.',

        'identificacion.digits' => 'Para crear usuario, la identificación debe tener 10 dígitos (cédula).',
        'identificacion.unique' => 'Ya existe un usuario activo con esa cédula.',

        'usuario_password.required' => 'Falta rellenar: Contraseña del usuario.',
        'usuario_password.min' => 'La contraseña del usuario debe tener al menos 6 caracteres.',
        'usuario_role_id.required' => 'Falta seleccionar: Rol del usuario.',
        'usuario_role_id.exists' => 'El rol seleccionado no es válido.',
    ];

    $validator = Validator::make($request->all(), $rules, $messages);

    $validator->after(function ($v) use ($request, $persona, $crearUsuario) {
        $empresaId = trim((string) $request->input('empresa_id', ''));
        $departamentoId = trim((string) $request->input('departamento_id', ''));

        // El departamento seleccionado debe pertenecer a la empresa elegida.
        if ($empresaId !== '' && $departamentoId !== '') {
            $belongs = PgDepartamento::query()
                ->where('id', $departamentoId)
                ->where('empresa_id', $empresaId)
                ->where(function ($q) {
                    $q->whereNull('estado')->orWhere('estado', '<>', 'X');
                })
                ->exists();

            if (!$belongs) {
                $v->errors()->add('departamento_id', 'El departamento seleccionado no pertenece a la empresa elegida.');
            }
        }

        // =========================
        // Si NO está agregando usuario, pero ya existe uno, sincronizamos email
        // Validamos que el correo no esté usado por otro usuario
        // =========================
        $emailPersona = trim((string) $request->input('email'));
        if ($emailPersona !== '' && $persona->usuarios()->exists()) {
            $user = $persona->usuarios()->orderBy('id')->first();
            if ($user && $user->email !== $emailPersona) {
                $conflict = User::where('email', $emailPersona)
                    ->where('id', '<>', $user->id)
                    ->exists();
                if ($conflict) {
                    $v->errors()->add('email', 'Ese correo ya está en uso por otro usuario.');
                }
            }
        }

        // =========================
        // Mensaje resumen: campos usuario faltantes (cuando aplica)
        // =========================
        if (!$crearUsuario) {
            return;
        }

        $missing = [];
        if (blank($request->input('usuario_role_id'))) {
            $missing[] = 'Rol del usuario';
        }
        if (blank($request->input('usuario_password'))) {
            $missing[] = 'Contraseña del usuario';
        }

        // Validación de duplicado de usuario (pg_usuario.usuario) según tipo seleccionado
        $usuario = $this->usuarioFromIdentificacion($request->input('tipo_identificacion'), $request->input('identificacion'));
        if ($usuario !== '' && User::where('usuario', $usuario)->whereNull('estado')->exists()) {
            $v->errors()->add('identificacion', 'Ya existe un usuario activo con ese usuario.');
        }

        if (!empty($missing)) {
            $v->errors()->add('crear_usuario', 'Para agregar el usuario falta rellenar: ' . implode(', ', $missing) . '.');
        }
    });

    if ($validator->fails()) {
        return redirect()->back()
            ->withErrors($validator)
            ->withInput();
    }

    // Validación adicional según tipo de identificación seleccionado
    try {
        $this->validarIdentificacionSegunTipo($request->tipo_identificacion, $request->identificacion);
    } catch (ValidationException $e) {
        return redirect()->back()
            ->withErrors($e->errors())
            ->withInput();
    }

    try {
        $successMsg = DB::transaction(function () use ($request, $persona, $crearUsuario) {
        $persona->tipo = $request->tipo;
        $persona->nombres = $request->nombres;
        $persona->apellido1 = $request->apellido1;
        $persona->apellido2 = $request->apellido2;
        $persona->direccion = $request->direccion;
        $persona->fecha_nacimiento = $this->parseFechaNacimiento($request->fecha_nacimiento);
        $persona->tipo_identificacion = $request->tipo_identificacion;
        $persona->identificacion = $request->identificacion;
        $persona->sexo = $request->sexo;
        $persona->celular = $request->celular;
        $persona->email = $request->email;
        $persona->cod_estado_civil = $request->cod_estado_civil;
        $persona->empresa_id = $request->input('empresa_id');
        $persona->departamento_id = $request->input('departamento_id') ?: null;
        $persona->save();

        if ($request->file('foto')) {
            $file = $request->file('foto');
            $binary = @file_get_contents($file->getRealPath());
            if ($binary !== false) {
                $encrypted = Crypt::encryptString(base64_encode($binary));

                $archivo = new AdArchivoDigital();
                $archivo->tipo_documento_codigo = null;
                $archivo->tipo_archivo_codigo = null;
                $archivo->nombre_original = $file->getClientOriginalName();
                $archivo->ruta = '';
                $archivo->digital = $encrypted;
                $archivo->tipo_mime = $file->getClientMimeType();
                $archivo->extension = $this->normalizeExt($file->getClientOriginalExtension());
                $archivo->tamano = (int) $file->getSize();
                $archivo->descripcion = 'Foto persona ' . $persona->id;
                $archivo->estado = null;
                $archivo->save();

                $foto = new PgPersonaFoto();
                $foto->id_persona = $persona->id;
                $foto->id_archivo = $archivo->id;
                $foto->estado = null;
                $foto->save();
            }
        }

        $msg = 'Persona actualizada correctamente.';

        // Si ya tiene usuario y la persona tiene email => sincronizar el mismo correo
        $emailPersona = trim((string) $persona->email);
        if ($emailPersona !== '' && $persona->usuarios()->exists()) {
            $user = $persona->usuarios()->orderBy('id')->first();
            if ($user && $user->email !== $emailPersona) {
                $user->email = $emailPersona;
                $user->save();
                $msg .= " Correo de usuario actualizado ({$emailPersona}).";
            }
        }

        // Agregar usuario (solo si marcó checkbox)
        if ($crearUsuario) {
            $usuarioLogin = $this->usuarioFromIdentificacion($persona->tipo_identificacion, $persona->identificacion);
            $emailUser = trim((string) $persona->email);
            $plainPassword = (string) $request->usuario_password;

            if ($usuarioLogin === '') {
                throw ValidationException::withMessages([
                    'identificacion' => 'No se pudo generar el usuario desde la identificación.',
                ]);
            }

            if (User::where('usuario', $usuarioLogin)->whereNull('estado')->exists()) {
                throw ValidationException::withMessages([
                    'identificacion' => 'Ya existe un usuario activo con ese usuario.',
                ]);
            }

            $user = new User();
            $user->id_persona = $persona->id;
            $user->name = $persona->nombre_completo ?: ('Persona ' . $persona->id);
            $user->usuario = $usuarioLogin;
            $user->email = $emailUser !== '' ? $emailUser : null;
            $user->password = Hash::make($plainPassword);
            $user->image = 'photos/img.jpg';
            // IMPORTANTE: el ID del usuario se genera por TRIGGER (VARCHAR(10)).
            // Eloquent NO conoce ese ID luego del insert, por eso debemos leer @last_usuario_id
            // en la MISMA conexión y asignarlo antes de guardar el rol.
            try {
                $user->getConnection()->statement('SET @last_usuario_id = NULL');
            } catch (\Throwable $e) {
                // ignore
            }

            $user->save();

            // Recuperar el ID generado por el trigger
            if (empty($user->id)) {
                try {
                    $row = $user->getConnection()->selectOne('SELECT @last_usuario_id AS id');
                    if ($row && !empty($row->id)) {
                        $user->id = (string) $row->id;
                    } else {
                        // Fallback: recuperar por usuario
                        $fallback = $user->getConnection()->selectOne(
                            'SELECT id FROM pg_usuario WHERE usuario = ? ORDER BY created_at DESC, id DESC LIMIT 1',
                            [$user->usuario]
                        );
                        if ($fallback && !empty($fallback->id)) {
                            $user->id = (string) $fallback->id;
                        }
                    }
                } catch (\Throwable $e) {
                    // ignore
                }
            }

            if (empty($user->id)) {
                throw new \RuntimeException('No se pudo determinar el ID generado del usuario para asignar el rol.');
            }

            // Asignar rol seleccionado (solo uno)
            // Asignar rol seleccionado (solo uno). NO castear a int (pierde ceros).
            $roleId = trim((string) ($request->input('usuario_role_id') ?? ''));
            if ($roleId === '') {
                $roleId = (string) (DB::table('roles')->when(Schema::hasColumn('roles','estado'), function($q){ $q->whereNull('estado'); })
                    ->orderBy('id')->value('id') ?? '');
            }
            try {
                $user->roles()->sync([$roleId]);
            } catch (\Throwable $e) {
                DB::table('role_user')->where('usuario_id', $user->id)->delete();
                DB::table('role_user')->insert([
                    'usuario_id' => (string) $user->id,
                    'role_id' => (string) $roleId,
                ]);
            }

            $msg .= " Usuario creado ({$usuarioLogin}).";
        }

        return $msg;
    });
    } catch (ValidationException $e) {
        return redirect()->back()
            ->withErrors($e->errors())
            ->withInput();
    } catch (\Throwable $e) {
        return redirect()->back()
            ->withErrors(['general' => 'No se pudo actualizar la persona. Error: ' . $e->getMessage()])
            ->withInput();
    }

    return redirect()->route('PersonasEdit', $persona->id)->with('success', $successMsg);
}

    public function Delete($id)
    {
        $persona = PgPersona::conEliminados()->where('id', $id)->firstOrFail();
        $persona->delete();

        return redirect()->route('PersonasIndex')->with('success', 'Persona eliminada (lógico) correctamente.');
    }


/**
 * Gestión de empleados despedidos / no vigentes (vigente = 'N')
 */
public function Despedidos(Request $request)
{
    $q = trim((string) $request->query('q', ''));
    $departamentoId = trim((string) $request->query('departamento_id', ''));

    $query = PgPersona::with(['fotoActual.archivo'])
        ->where('vigente','N');

    if ($q !== '') {
        $query->where(function ($w) use ($q) {
            $w->where('id', 'like', "%{$q}%")
              ->orWhere('nombres', 'like', "%{$q}%")
              ->orWhere('apellido1', 'like', "%{$q}%")
              ->orWhere('apellido2', 'like', "%{$q}%")
              ->orWhere('identificacion', 'like', "%{$q}%")
              ->orWhereHas('departamento', function ($dq) use ($q) {
                  $dq->where('descripcion', 'like', "%{$q}%");
              });
        });
    }

    if ($departamentoId !== '') {
        $query->where('departamento_id', $departamentoId);
    }

    $personas = $query->paginate(20)->appends($request->query());

    $departamentos = PgDepartamento::query()
        ->where(function ($q) {
            $q->whereNull('estado')->orWhere('estado', '<>', 'X');
        })
        ->orderBy('descripcion')
        ->get();

    $rolesQuery = PgRol::query();
    $rolesQuery->whereNull('estado');
    $roles = $rolesQuery->orderBy('nombre')->get();

    return view('personas.despedidos', [
        'personas' => $personas,
        'departamentos' => $departamentos,
        'roles' => $roles,
    ]);
}



/**
 * Historial de altas/bajas (vigente) para una persona por identificacion.
 */
public function HistorialVigencia($id)
{
    $persona = PgPersona::findOrFail($id);

    // logs donde hubo cambios de vigente o bajas automáticas
    $logs = DB::table('pg_importacion_logs')
        ->where('identificacion', $persona->identificacion)
        ->where(function($q){
            $q->where('mensaje_error', 'AUTO_VIGENTE_N')
              ->orWhereRaw("JSON_EXTRACT(before_json, '$.vigente') <> JSON_EXTRACT(after_json, '$.vigente')")
              ->orWhereRaw("JSON_EXTRACT(before_json, '$.vigente') IS NULL AND JSON_EXTRACT(after_json, '$.vigente') IS NOT NULL")
              ->orWhereRaw("JSON_EXTRACT(before_json, '$.vigente') IS NOT NULL AND JSON_EXTRACT(after_json, '$.vigente') IS NULL");
        })
        ->orderByDesc('id')
        ->get();

    return view('personas.historial_vigencia', [
        'persona' => $persona,
        'logs' => $logs,
    ]);
}

}
