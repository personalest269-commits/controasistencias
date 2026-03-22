<?php
namespace App\Http\Controllers;

Use App\Models\User;
Use App\Models\Role;
Use App\Models\Permission;
Use App\Models\PgPersona;
Use App\Http\Controllers;
use Illuminate\Http\Request;
use Auth;
use Hash;
use Session;
use Cookie;
use Yajra\DataTables\Facades\DataTables;
use Storage;
use Validator;
use Illuminate\Support\Facades\File;
use config;
use App\Models\Settings;
use App\Models\PgConfiguracion;
use App\Models\PgPlantilla;
use App\Mail\GenericTemplateMail;
use App\Services\EmailTemplateRenderer;
use Illuminate\Support\Facades\Mail;
use Socialite;
use App\Http\Controllers\ResponseController;
use Illuminate\Support\MessageBag;
use App\Http\Resources\User as UserResource;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\AdArchivoDigital;
use Illuminate\Support\Facades\Crypt;
use App\Services\IdGenerator;
use App\Models\Idioma;


Class UsersController extends Controller
{

    public $Now;
    protected $cookieFactory;
    protected $Response;
    public function __construct()
    {
        parent::__construct();
        $this->Response=new ResponseController();
        $this->Now = date('Y-m-d H:i:s');
    }
    
    /**
     * show Login page
     * @return VIEW
     */
                public function Login()
    {
        if (Auth::user()) {
            return $this->Response->prepareResult(true, [], [], [], 'redirect', route('dashboardIndex'));
        }

        // Idiomas activos para el selector (si existe la tabla).
        $idiomas = collect();
        try {
            if (Schema::hasTable('pg_idiomas')) {
                $idiomas = Idioma::query()
                    ->where('activo', 1)
                    ->orderBy('por_defecto', 'desc')
                    ->orderBy('nombre')
                    ->get();
            }
        } catch (\Throwable $e) {
            $idiomas = collect();
        }

        // Por defecto usamos el template CONTROL (diseño solicitado).
        // Si en la configuración existe LOGIN_TEMPLATE y es diferente a CONTROL, usamos el template alternativo.
        $tpl = strtoupper(trim((string) PgConfiguracion::valor('LOGIN_TEMPLATE', 'CONTROL')));
        $view = ($tpl === 'CONTROL') ? 'auth.login_control' : 'auth.login';

        return $this->Response->prepareResult(200, ['idiomas' => $idiomas], [], [], 'view', $view);
    }

    /**
     * Cambia el idioma (locale) del sistema.
     *
     * - Lee idiomas activos desde tabla `idiomas` (si existe).
     * - Guarda el código en Session('lang') y redirige de vuelta.
     */
    public function setLang(Request $request)
    {
        $lang = (string) $request->input('lang', '');

        // Validar contra tabla idiomas si existe
        try {
            if (Schema::hasTable('pg_idiomas')) {
                $exists = \App\Models\Idioma::query()
                    ->where('activo', 1)
                    ->where('codigo', $lang)
                    ->exists();
                if (!$exists) {
                    $lang = (string) (\App\Models\Idioma::query()
                        ->where('activo', 1)
                        ->where('por_defecto', 1)
                        ->value('codigo') ?? 'es');
                }
            }
        } catch (\Throwable $e) {
            // si algo falla, fallback
            if ($lang === '') {
                $lang = 'es';
            }
        }

        if ($lang === '') {
            $lang = 'es';
        }

        Session::put('lang', $lang);

        // Persistir también para invitados (login / frontend) y recordar preferencia
        // 1 año
        Cookie::queue('lang', $lang, 60 * 24 * 365);

        return redirect()->back();
    }

    /**
     * Cambia la interfaz (UI template) del panel.
     * Valores permitidos: 'gentelella' | 'admin_lte'
     * Se guarda en Session('ui_template') y redirige de vuelta.
     */
    public function setUiTemplate(Request $request)
    {
        $ui = (string) $request->input('ui_template', '');
        $allowed = ['gentelella', 'admin_lte'];
        if (!in_array($ui, $allowed, true)) {
            $ui = 'gentelella';
        }

        // Guardar en BD por usuario (id_plantillas)
        try {
            $plantilla = PgPlantilla::query()
                ->where('codigo', $ui)
                ->where('activo', 'S')
                ->first();

            if (Auth::check() && $plantilla) {
                $user = Auth::user();
                $user->id_plantillas = $plantilla->id;
                $user->save();
            }
        } catch (\Throwable $e) {
            // ignore
        }

        // Mantener session para que el cambio se vea inmediatamente
        Session::put('ui_template', $ui);
        return redirect()->back();
    }


    /**
     * Determina si Laravel Passport está correctamente configurado.
     *
     * Si Passport no está listo (tablas, cliente personal o llaves inválidas),
     * no intentamos generar tokens para evitar errores fatales durante el login.
     */
    protected function passportReady(): bool
    {
        // Passport puede estar instalado como paquete pero no configurado.
        if (!class_exists(\Laravel\Passport\Passport::class)) {
            return false;
        }

        // Si no existen las tablas de oauth, todavía no se instaló.
        if (!Schema::hasTable('oauth_clients')) {
            return false;
        }

        // Debe existir un personal access client.
        try {
            $hasPersonalClient = DB::table('oauth_clients')->where('personal_access_client', 1)->exists();
            if (!$hasPersonalClient) {
                return false;
            }
        } catch (\Throwable $e) {
            return false;
        }

        // Validar llaves (evita "Invalid key supplied").
        $privateKeyPath = storage_path('oauth-private.key');
        $publicKeyPath  = storage_path('oauth-public.key');
        if (!is_file($privateKeyPath) || !is_file($publicKeyPath)) {
            return false;
        }

        // openssl puede no estar habilitado; si falla, asumimos no listo.
        if (!function_exists('openssl_pkey_get_private')) {
            return false;
        }

        $privateKey = @file_get_contents($privateKeyPath);
        if (!$privateKey) {
            return false;
        }
        $key = @openssl_pkey_get_private($privateKey);
        return $key !== false && $key !== null;
    }

    /**
     * Validador para login web.
     *
     * Este método faltaba en algunas versiones del proyecto y provocaba:
     * BadMethodCallException: Method UsersController::ValidateAuth does not exist.
     */
    protected function ValidateAuth(Request $request)
    {
        $recSite = trim((string) PgConfiguracion::valor('RECAPTCHA_SITE_KEY', ''));
        $recSecret = trim((string) PgConfiguracion::valor('RECAPTCHA_SECRET_KEY', ''));
        $recEnabled = ($recSite !== '' && $recSecret !== '');

        $rules = [
            'login_usuario'  => ['required'],
            'login_password' => ['required', 'string', 'min:3'],
        ];
        if ($recEnabled) {
            $rules['g-recaptcha-response'] = ['required', 'string'];
        }

        $v = Validator::make($request->all(), $rules, [
            'login_usuario.required'   => 'El usuario es obligatorio.',
            'login_password.required'  => 'La contraseña es obligatoria.',
            'g-recaptcha-response.required' => 'Valida el captcha para continuar.',
        ]);

        $v->after(function ($validator) use ($request) {
            $raw = trim((string) $request->input('login_usuario'));

            // Caso especial: Admin (permite login sin validar cédula)
            if (strtolower($raw) === 'admin') {
                return;
            }

            // Caso normal: cédula (10 dígitos) + validación ECU
            $cedula = preg_replace('/\D+/', '', $raw);

            if (!preg_match('/^\d{10}$/', $cedula)) {
                $validator->errors()->add('login_usuario', 'El usuario debe tener 10 dígitos (cédula) o escribir Admin.');
                return;
            }

            if (!$this->isValidCedulaEc($cedula)) {
                $validator->errors()->add('login_usuario', 'La cédula ingresada no es válida.');
            }
        });

        return $v;
    }
    /**
     * Authenticate User
     * @param \Illuminate\Http\Request $request
     * @return JSON
     */
    public function auth(Request $request)
    {
        $ValidateAuth=$this->ValidateAuth($request);
        if($ValidateAuth->fails())
        { 
            return $this->Response->prepareResult(400, [], $ValidateAuth, '', 'redirect', route('login'));
        }

        // Validación reCAPTCHA (solo si existen ambas llaves en configuraciones)
        try {
            $recSite = trim((string) PgConfiguracion::valor('RECAPTCHA_SITE_KEY', ''));
            $recSecret = trim((string) PgConfiguracion::valor('RECAPTCHA_SECRET_KEY', ''));
            $recEnabled = ($recSite !== '' && $recSecret !== '');

            if ($recEnabled) {
                $token = (string) $request->input('g-recaptcha-response', '');
                $ok = false;

                if ($token !== '') {
                    $resp = Http::asForm()->timeout(8)->post('https://www.google.com/recaptcha/api/siteverify', [
                        'secret' => $recSecret,
                        'response' => $token,
                        'remoteip' => $request->ip(),
                    ]);

                    if ($resp->ok()) {
                        $json = $resp->json();
                        $ok = (bool) ($json['success'] ?? false);
                    }
                }

                if (!$ok) {
                    $bag = new MessageBag();
                    $bag->add('recaptcha', 'Captcha inválido. Inténtalo nuevamente.');
                    return $this->Response->prepareResult(400, [], $bag, '', 'redirect', route('login'));
                }
            }
        } catch (\Throwable $e) {
            // Si el captcha está habilitado pero no se puede validar (red, SSL, etc),
            // por seguridad bloqueamos el login.
            $recSite = trim((string) PgConfiguracion::valor('RECAPTCHA_SITE_KEY', ''));
            $recSecret = trim((string) PgConfiguracion::valor('RECAPTCHA_SECRET_KEY', ''));
            $recEnabled = ($recSite !== '' && $recSecret !== '');
            if ($recEnabled) {
                $bag = new MessageBag();
                $bag->add('recaptcha', 'No se pudo validar el captcha en este momento.');
                return $this->Response->prepareResult(400, [], $bag, '', 'redirect', route('login'));
            }
        }
        $rawUsuario = trim((string) $request->input('login_usuario'));

// Si escriben "Admin", buscamos el usuario que tenga rol Super-Admin y usamos su campo "usuario" real.
if (strtolower($rawUsuario) === 'admin') {
    $super = User::whereHas('roles', function ($q) {
            $q->whereIn('name', ['Super Admin','Super-Admin']);
        })
        ->first();

    $usuario = $super ? $super->usuario : 'admin';
} else {
    $alnum = preg_replace('/[^A-Za-z0-9]/', '', $rawUsuario);
    // Si es numérico (cédula/ruc) => solo dígitos, si no => pasaporte/otros en MAYÚSCULAS
    $usuario = preg_match('/^\d+$/', $alnum) ? $alnum : strtoupper($alnum);
}
if(Auth::attempt(['usuario' => $usuario, 'password' => $request->input('login_password')])) {
            $UserInfo = User::where('usuario', $usuario)->first();
            // El login web usa Auth::attempt (sesión). Este proyecto además intenta
            // crear un token de Passport para consumir endpoints protegidos.
            //
            // Problema: si Passport no está instalado/configurado (tablas/cliente/llaves)
            // createToken() puede lanzar errores como "Invalid key supplied".
            //
            // Solución: solo intentamos generar token si Passport está listo. Si no,
            // continuamos el login normal sin token.
            $UserInfo->token = null;
            if ($this->passportReady()) {
                try {
                    $UserInfo->token = $UserInfo->createToken('appbuilder_token')->accessToken;
                } catch (\Throwable $e) {
                    \Log::error('Passport token generation failed during login', [
                        'userId' => $UserInfo->id ?? null,
                        'exception' => $e->getMessage(),
                    ]);
                    $UserInfo->token = null;
                }
            }
            Session::put('name', $UserInfo->name);
            return $this->Response->prepareResult(200, new UserResource($UserInfo), [], '', 'redirect', route('dashboardIndex'));
        } else {
            // Mostrar mensaje de credenciales inválidas.
            return $this->Response->prepareResult(400, [], [], null, 'redirect', route('login'), tr('Credenciales incorrectas'));
        }
    }
    
    /**
     * Log user out
     * @return redirect
     */
    public function Logout()
    {
        try {
            Auth::logout();
            Session::forget('email');
            return $this->Response->prepareResult(200, [], [], '', 'redirect', route('login'));
        } catch (\Exception $exc) {
            return $this->Response->prepareResult(400, [], [], '', 'redirect', route('login'));
        }
    }
    
    /**
     * Register view
     * @return view
     */
    public function register()
    {
        // Si está desactivado el registro, redirigir al login
        try {
            if (!PgConfiguracion::bool('REGISTRO_USUARIO_ACTIVO', true)) {
                return $this->Response->prepareResult(200, [], [], '', 'redirect', route('login'));
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return $this->Response->prepareResult(200, [], [], '', 'view', 'auth.register');
    }
    protected function ValidateRegister(Request $request)
    {
        $v = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name'  => 'required',
            'usuario'    => [
                'required',
                'digits:10',
                Rule::unique('pg_usuario', 'usuario')->whereNull('estado'),
            ],
            'email'      => ['nullable', 'email', 'max:191'],
            'password'   => 'required',
        ]);

        $v->after(function ($validator) use ($request) {
            $cedula = preg_replace('/\D+/', '', (string) $request->input('usuario'));
            if ($cedula !== '' && !$this->isValidCedulaEc($cedula)) {
                $validator->errors()->add('usuario', 'La cédula ingresada no es válida.');
            }
        });

        return $v;
    }

    /**
     * Valida cédula ecuatoriana (10 dígitos).
     */
    private function isValidCedulaEc(string $cedula): bool
    {
        if (!preg_match('/^\d{10}$/', $cedula)) {
            return false;
        }
        $prov = (int) substr($cedula, 0, 2);
        if ($prov < 1 || $prov > 24) {
            return false;
        }
        $d3 = (int) $cedula[2];
        if ($d3 < 0 || $d3 > 5) {
            return false;
        }
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $n = (int) $cedula[$i];
            if ($i % 2 === 0) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
        }
        $mod = $sum % 10;
        $check = $mod === 0 ? 0 : 10 - $mod;
        return $check === (int) $cedula[9];
    }
    
    /**
     * Register new User
     * @param \Illuminate\Http\Request $request
     * @return type
     */
    public function RegisterPost(Request $request)
    {
        try {
            // Si está desactivado el registro por configuración del sistema
            if (!PgConfiguracion::bool('REGISTRO_USUARIO_ACTIVO', true)) {
                return $this->Response->prepareResult(200, [], [], '', 'redirect', route('login'));
            }

            $settings=  Settings::where('id',1)->first();
            if($settings->registration){
                $ValidateRegister=$this->ValidateRegister($request);
                if($ValidateRegister->fails())
                {
                    return $this->Response->prepareResult(400, [], $ValidateRegister,'','redirect',route('login').'#toregister');
                }
                $user = new User();
                $user->name=$request->input('first_name').' '.$request->input('last_name'); ;
                $user->email = $request->input('email');
                $user->password = Hash::make($request->input('password'));
                if ($request->file('image')){
                    $User->image = $this->UploadProfilePic($request);
                }
                else{
                    $user->image = 'photos/img.jpg';
                }
                $user->image = 'photos/img.jpg';
                $user->save();
                $user->roles()->sync(array(2));
                return $this->Response->prepareResult(200, [], [],'','redirect',route('login'));
                }
        } catch (\Exception $exc) {
                return $this->Response->prepareResult(400, [], [],'','redirect',route('login'));
        }
    }
    
    /**
     * Register Users as admin
     */
    public function RegisterUserToAdmin()
    {
        $Users=User::select('id')->get();
        foreach($Users as $User):
            $User=User::where('id',$User->id)->first();
            $User->roles()->sync(array(2));
        endforeach;
    }
    
    /**
     * Show all users
     * @return view
     */
    public function Index()
    {        
         try {
             $authUser = Auth::user();
             $isSuper = $authUser && ($authUser->hasRole('Super Admin') || $authUser->hasRole('Super-Admin'));

             $Roles = Role::query()
                 ->when(!$isSuper, function($q){
                     $q->whereNotIn('name', ['Super Admin','Super-Admin']);
                     if (\Schema::hasColumn('roles','display_name')) {
                         $q->whereNotIn('display_name', ['Super Admin','Super-Admin']);
                     }
                 })
                 ->get();
             return $this->Response->prepareResult(200, ['roles' => $Roles], [],'','view','users/users');
        } catch (\Exception $exc) {
            return $this->Response->prepareResult(400, [], [],'');
        }
    }
    
    /**
     * Get All Users
     * @return JSON
     */
    public function All()
    {
        $AuthUser = Auth::user();
        $UsersQuery = User::with('roles');
        // Si NO es Super-Admin, ocultar usuarios con rol Super-Admin
        if (!($AuthUser && ($AuthUser->hasRole('Super Admin') || $AuthUser->hasRole('Super-Admin')))) {
            $UsersQuery->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'Super-Admin');
            });
        }
        $Users = $UsersQuery->get();
        return Datatables::of($Users)->addColumn('Select', function($Users) { return '<input class="flat user_record" name="user_record"  type="checkbox" value="'.$Users->id.'" />';})
                ->addColumn('actions', function ($Users) {
                $User=Auth::user();    
                $column='';
                if($User->can('user_edit')){
                    $column .= '<a href="javascript:void(0)"  data-url="' . route('usersedit', $Users->id) . '" class="edit '.config('view.edit_classes')['button'].'"><i class="'.config('view.edit_classes')['icon'].'"></i> Edit</a>';  
                }
                if($User->can('user_delete')){
                    $column .= '<a href="javascript:void(0)" data-url="' . route('usersdelete', $Users->id) . '" class="delete '.config('view.delete_classes')['button'].'"><i class="'.config('view.delete_classes')['icon'].'"></i> Delete</a>';
                }
                return $column;
            })->addColumn('role', function ($Users) {
                $column='';
                foreach ($Users->Roles as $role){
                    $column.='<span class="badge bg-green">'.$role->name.'</span>';
                }
                return $column;
            })->rawColumns(['Select','role','actions'])->make(true);
    }
    
    /**
     * Get User ByID
     * @param type $ID
     * @return JSON
     */
    public function Edit($ID)
    {
        try {
            $data=User::with(['Roles','persona'])->where('id', $ID)->get();
            return $this->Response->prepareResult(200, $data, [],'');
        } catch (\Exception $exc) {
            
        }

    }

    /**
     * Bandbox/Select2: Buscar personas por identificación o nombre completo.
     * Retorna: { results: [ { id, text, email } ] }
     */
    public function SearchPersonas(Request $request)
    {
        try {
            // Symfony 7.4+ deprecates Request::get(), use query/request bags explicitly.
            $term = trim((string) ($request->query('q') ?? $request->query('term') ?? ''));
            $exactId = trim((string) ($request->query('id') ?? ''));
            $excludeUserId = trim((string) ($request->query('exclude_user_id') ?? ''));

            $query = PgPersona::query(); // por defecto solo activos (estado IS NULL)

            // Excluir personas ya vinculadas a otros usuarios (id_persona UNIQUE)
            $query->whereNotIn('id', function ($sub) use ($excludeUserId) {
                $sub->from('pg_usuario')
                    ->select('id_persona')
                    ->whereNull('estado')
                    ->whereNotNull('id_persona');
                if ($excludeUserId !== '') {
                    $sub->where('id', '<>', $excludeUserId);
                }
            });

            if ($exactId !== '') {
                $query->where('id', $exactId);
            } elseif ($term !== '') {
                $query->where(function ($q) use ($term) {
                    $q->where('identificacion', 'like', '%' . $term . '%')
                      ->orWhereRaw("CONCAT(TRIM(IFNULL(nombres,'')), ' ', TRIM(IFNULL(apellido1,'')), ' ', TRIM(IFNULL(apellido2,''))) LIKE ?", ['%' . $term . '%'])
                      ->orWhereRaw("CONCAT(TRIM(IFNULL(apellido1,'')), ' ', TRIM(IFNULL(apellido2,'')), ' ', TRIM(IFNULL(nombres,''))) LIKE ?", ['%' . $term . '%']);
                });
            }

            $personas = $query->orderBy('identificacion')
                ->limit(20)
                ->get(['id','identificacion','nombres','apellido1','apellido2','email']);

            $results = $personas->map(function ($p) {
                $nombre = trim(implode(' ', array_filter([
                    trim((string) $p->nombres),
                    trim((string) $p->apellido1),
                    trim((string) $p->apellido2),
                ])));

                $text = trim((string) $p->identificacion) !== ''
                    ? trim((string) $p->identificacion) . ' - ' . $nombre
                    : $nombre;

                return [
                    'id' => $p->id,
                    'text' => $text,
                    'email' => $p->email,
                    'identificacion' => $p->identificacion,
                ];
            })->values();

            return response()->json(['results' => $results]);
        } catch (\Throwable $e) {
            return response()->json(['results' => []]);
        }
    }

    /**
     * Create User or update it 
     * @param \Illuminate\Http\Request $request
     * @return JSON
     */
    public function CreateOrUpdate(Request $request)
    {
        try {
            $isEdit = trim((string) $request->input('id')) !== '';

            // Checkbox opcional: vincular persona
            $linkPersona = $request->input('link_persona') == '1';
            $idPersona = $linkPersona ? trim((string) $request->input('id_persona')) : null;

            // Si NO se vincula persona, aseguramos que no se valide ni se guarde.
            if (!$linkPersona) {
                $request->merge(['id_persona' => null]);
            }

            // Si se selecciona persona y tiene email, lo copiamos al email del usuario.
            $persona = null;
            $personaEmail = null;
            $personaCedula = null;
            if ($linkPersona && $idPersona) {
                $persona = PgPersona::where('id', $idPersona)->first();
                if ($persona) {
                    $personaEmail = trim((string) $persona->email);
                    $personaCedula = preg_replace('/\D+/', '', (string) $persona->identificacion);
                    if ($personaCedula !== '') {
                        $request->merge(['usuario' => $personaCedula]);
                    }
                    if ($personaEmail !== '') {
                        $request->merge(['email' => $personaEmail]);
                    }
                }
            }

            $userId = $isEdit ? trim((string) $request->input('id')) : null;

            $passwordRule = $isEdit ? 'nullable|string|min:6' : 'required|string|min:6';

            $rules = [
                'name' => 'required|string|max:255',
                'usuario' => [
                    'required',
                    'digits:10',
                    Rule::unique('pg_usuario', 'usuario')->whereNull('estado')->ignore($userId),
                ],
                'email' => [
                    'nullable',
                    'email',
                    Rule::unique('pg_usuario', 'email')->whereNull('estado')->ignore($userId),
                ],
                'password' => $passwordRule,
                'roles' => 'required',
                'id_persona' => $linkPersona ? [
                    'required',
                    Rule::unique('pg_usuario', 'id_persona')->whereNull('estado')->ignore($userId),
                ] : ['nullable'],
            ];

            $messages = [
                'name.required' => 'Debe ingresar el nombre.',
                'usuario.required' => 'Debe ingresar el usuario (cédula).',
                'usuario.digits' => 'El usuario debe tener 10 dígitos.',
                'usuario.unique' => 'La cédula ya está registrada en un usuario activo.',
                'email.email' => 'El email no es válido.',
                'email.unique' => 'El email ya está registrado en un usuario activo.',
                'password.required' => 'Debe ingresar la contraseña.',
                'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
                'roles.required' => 'Debe seleccionar un rol.',
                'id_persona.required' => 'Debe seleccionar una persona.',
                'id_persona.unique' => 'La persona ya ha sido ingresada en un usuario activo.',
            ];

            $validator = Validator::make($request->all(), $rules, $messages);

            $validator->after(function ($v) use ($request, $linkPersona, $idPersona, $persona) {
                if ($linkPersona) {
                    if (!$idPersona) {
                        return;
                    }
                    if (!$persona) {
                        $v->errors()->add('id_persona', 'La persona seleccionada no existe o está eliminada.');
                        return;
                    }
                }

                $cedula = preg_replace('/\D+/', '', (string) $request->input('usuario'));
                if ($cedula !== '' && !$this->isValidCedulaEc($cedula)) {
                    $v->errors()->add('usuario', 'La cédula ingresada no es válida.');
                }
            });

            if ($validator->fails()) {
                return $this->Response->prepareResult(400, [], $validator->errors(), null, 'ajax', null, 'Por favor corrige los campos marcados.');
            }

            $All_input = $request->input();

            if ($isEdit) {
                $User = User::where('id', $All_input['id'])->first();
                if (!$User) {
                    $bag = new MessageBag(['id' => ['Usuario no encontrado.']]);
                    return $this->Response->prepareResult(400, [], $bag, null, 'ajax', null, 'No se pudo guardar');
                }
            } else {
                $User = new User();
                $User->image = 'photos/img.jpg';
            }

            $User->id_persona = $linkPersona ? $idPersona : null;
            $User->name = $All_input['name'];
            $User->usuario = preg_replace('/\D+/', '', (string) ($All_input['usuario'] ?? ''));
            $User->email = $All_input['email'] ?? null;

            if (!empty($All_input['password'])) {
                $User->password = Hash::make($All_input['password']);
            }

            if ($request->file('image')) {
                $User->image = $this->UploadProfilePic($request);
            }

            $User->save();

            // Si el ID es generado por trigger (VARCHAR) Eloquent no lo conocerá.
            // Recuperamos el último ID generado en la misma conexión y lo asignamos
            // antes de sincronizar roles.
            if (!$isEdit) {
                try {
                    // IMPORTANTE: usar la MISMA conexión del modelo para leer variables de sesión (@last_usuario_id)
                    $row = $User->getConnection()->selectOne('SELECT @last_usuario_id AS id');
                    if ($row && !empty($row->id)) {
                        $User->id = (string) $row->id;
                    } else {
                        // Fallback: si por alguna razón no se pudo leer la variable de sesión,
                        // recuperamos el ID por usuario recién insertado.
                        $fallback = $User->getConnection()->selectOne(
                            'SELECT id FROM pg_usuario WHERE usuario = ? ORDER BY created_at DESC, id DESC LIMIT 1',
                            [$User->usuario]
                        );
                        if ($fallback && !empty($fallback->id)) {
                            $User->id = (string) $fallback->id;
                        }
                    }
                } catch (\Throwable $e) {
                    // noop
                }
            }

            if (empty($User->id)) {
                $bag = new MessageBag(['id' => ['No se pudo determinar el ID generado del usuario. Revise trigger/pg_control.']]);
                return $this->Response->prepareResult(400, [], $bag, null, 'ajax', null, 'No se pudo guardar');
            }

            // Role (solo uno)
            // IMPORTANTE: en este proyecto los IDs son VARCHAR(10) (ej: 0000000001).
            // Nunca castear a int porque se pierden los ceros a la izquierda y rompe FK.
            $roleId = trim((string) ($All_input['roles'] ?? ''));
            $User->roles()->sync([$roleId]);

            // Enviar credenciales SOLO si se creó y se proporcionó password.
            if (!$isEdit && $request->input('send_welcome_email') == '1' && !empty($All_input['password'])) {
                try {
                    $renderer = app(EmailTemplateRenderer::class);
                    $rendered = $renderer->render('new_user', app()->getLocale(), [
                        'app_name' => config('app.name'),
                        'company_name' => config('app.name'),
                        'email' => $User->email,
                        'password' => $All_input['password'],
                        'app_url' => route('login'),
                    ]);

                    $subject = $rendered['subject'] ?: 'New User';
                    $body = $rendered['body'] ?: ('Hello,<br><br>Your account has been created.<br>Email: ' . e($User->email) . '<br>Password: ' . e($All_input['password']) . '<br><br>Login: ' . e(route('login')));
                    $fromName = $rendered['from_name'] ?? null;

                    Mail::to($User->email)->send(new GenericTemplateMail($subject, $body, $fromName));
                } catch (\Throwable $e) {
                    // Do not block user creation if email sending fails.
                }
            }

            return $this->Response->prepareResult(200, $User, [], 'User Saved Successfully !');
        } catch (\Throwable $exc) {
            // Devolver un mensaje útil al frontend (se muestra en el modal) y dejar traza en log.
            \Log::error('CreateOrUpdate user failed', [
                'exception' => $exc->getMessage(),
                'trace' => $exc->getTraceAsString(),
            ]);

            $msg = $exc->getMessage();
            if ($exc instanceof \Illuminate\Database\QueryException) {
                // errorInfo[2] suele contener el mensaje SQL (columna/valor) en MySQL/MariaDB.
                $sqlMsg = $exc->errorInfo[2] ?? $msg;
                $msg = $sqlMsg;

                // Mensajes amigables para UNIQUE
                if (stripos($sqlMsg, 'Duplicate entry') !== false) {
                    // Email duplicado
                    if (stripos($sqlMsg, 'pg_usuario_email') !== false || stripos($sqlMsg, 'email') !== false) {
                        $msg = 'El correo ya está registrado en un usuario activo.';
                    }
                    // Persona duplicada
                    if (stripos($sqlMsg, 'id_persona') !== false || stripos($sqlMsg, 'uk_pg_usuario_id_persona') !== false || stripos($sqlMsg, 'pg_usuario_id_persona') !== false) {
                        $msg = 'La persona ya ha sido ingresada en un usuario activo.';
                    }
                }
            }

            return $this->Response->prepareResult(400, [], [], null, 'ajax', null, $msg ?: 'No se pudo guardar el usuario.');
        }
    }
    
    /**
     * Delete User
     * @param type $ID
     * @return JSON
     */
    public function Delete($ID)
    {
        try {
            if(config('sysconfig.users.delete')){
                User::where('id', $ID)->update(['estado' => 'X']);
                return $this->Response->prepareResult(200, [], [], 'User deleted successfully');
            }
            else{
                return $this->Response->prepareResult(400, [], [], 'Could not Delete User in Demo Version');            
            }   
        } catch (\Exception $exc) {
                return $this->Response->prepareResult(400, [], [], 'Could not Delete User in Demo Version');            
        }        
    }

    
    /**
     * Delete Multiple Users
     * @param Request $request
     * @return JSON
     */
    public function DeleteMultiple(Request $request)
    {
        try {
            if(config('sysconfig.users.delete')){
                User::whereIn('id', $request->selected_rows)->update(['estado' => 'X']);
                return $this->Response->prepareResult(200, [], [], 'User/s deleted successfully');
            }
            else{
                return $this->Response->prepareResult(400, [], [], 'Could not Delete User/s in Demo Version');            
            }   
        } catch (\Exception $exc) {
                return $this->Response->prepareResult(400, [], [], 'Could not Delete User/s in Demo Version');            
        }        
    }
    
    /**
     * User profile
     * @return view
     */
    public function Profile()
    {
        try {
            $User = Auth::user();
            return $this->Response->prepareResult(200,['user' => $User],[],[],'view','users.profile');
        } catch (\Exception $exc) {
            return $this->Response->prepareResult(400, [], [], []);
        }
    }
    
    /**
     * Update User profile
     * @param \Illuminate\Http\Request $request
     * @return JSON/REDIRECT
     */
    public function ProfileUpdate(Request $request)
    {
        try {
            // Validación (email único solo entre usuarios activos)
            $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => [
                    'required',
                    'email',
                    Rule::unique('pg_usuario', 'email')
                        ->whereNull('estado')
                        ->ignore(Auth::user()->id, 'id'),
                ],
                'password' => ['nullable', 'string', 'min:6'],
                'image' => ['nullable', 'image', 'max:5120'], // 5MB
            ]);

            DB::beginTransaction();

            $All_input = $request->input();
            $User = User::where('id', Auth::user()->id)->firstOrFail();
            $User->id_persona = $All_input['id_persona'] ?? $User->id_persona;
            $User->name = $All_input['name'];
            $User->email = $All_input['email'];

            if (!empty($All_input['password'])) {
                $User->password = Hash::make($All_input['password']);
            }

            // Foto de perfil: se guarda en ad_archivo_digital (cifrado) y se relaciona en pg_usuario.id_archivo
            if ($request->file('image')) {
                $archivoId = $this->saveProfilePhotoToArchivoDigital($User, $request->file('image'));

                if (empty($archivoId)) {
                    DB::rollBack();
                    return $this->Response->prepareResult(
                        422,
                        [],
                        ['image' => 'No se pudo guardar la foto de perfil. Intente nuevamente.'],
                        null,
                        'redirect',
                        route('userprofile'),
                        'No se pudo guardar la foto de perfil.'
                    );
                }

                $User->id_archivo = $archivoId;
                // Mantener el campo legacy "image" no-nulo para compatibilidad (en BD puede ser NOT NULL).
                // La UI ya prioriza id_archivo para mostrar la foto.
                if (empty($User->image)) {
                    $User->image = 'photos/img.jpg';
                }
            }

            $User->save();
            DB::commit();

                        $successMsg = $request->file('image') ? 'Perfil actualizado y foto guardada correctamente.' : 'Perfil actualizado correctamente.';

            return $this->Response->prepareResult(200, $User, [], $successMsg, 'redirect', route('userprofile'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Deja que Laravel maneje el redirect con errores de validación
            throw $e;
        } catch (\Throwable $exc) {
            try { DB::rollBack(); } catch (\Throwable $e) {}

            // Si el error ocurre al subir/guardar imagen, mostramos el mensaje debajo del input.
            $errors = [];
            if ($request->file('image')) {
                $errors['image'] = 'No se pudo guardar la foto de perfil. Verifique el archivo e intente nuevamente.';
            }

            \Log::error('Error al actualizar perfil', ['error' => $exc->getMessage()]);

            return $this->Response->prepareResult(
                400,
                [],
                $errors,
                null,
                'redirect',
                route('userprofile'),
                'No se pudo actualizar el perfil'
            );
        }
    }
    
    /**
     * Upload profile picture
     * @param \Illuminate\Http\Request $request
     * @return string
     */
    protected function UploadProfilePic(Request $request)
    {
        $Image = $request->file('image');
        $Extension = $Image->getClientOriginalExtension();
        $path = $Image->getFilename() . '.' . $Extension;
        Storage::disk('public_folder')->put($path, File::get($request->file('image')));
        return $path;
    }

    /**
     * Guarda la foto de perfil en ad_archivo_digital.digital (cifrada) y devuelve el ID.
     *
     * - Si el usuario ya tiene id_archivo, se actualiza ese registro.
     * - Si no tiene, se crea uno nuevo.
     */
    protected function saveProfilePhotoToArchivoDigital(User $user, $file): ?string
    {
        try {
            $binary = @file_get_contents($file->getRealPath());
            if ($binary === false) {
                return null;
            }

            $encrypted = Crypt::encryptString(base64_encode($binary));

            $ext = strtolower(trim((string) $file->getClientOriginalExtension()));
            $ext = ltrim($ext, '.');
            if ($ext === '') {
                $ext = 'jpg';
            }

            $mime = $file->getClientMimeType() ?: 'application/octet-stream';
            $now = now();

            // Soportar entornos donde ad_archivo_digital.id aún sea BIGINT (auto increment)
            // y entornos donde ya fue convertido a VARCHAR(10).
            $col = DB::selectOne("SHOW COLUMNS FROM `ad_archivo_digital` LIKE 'id'");
            $type = strtolower((string) ($col->Type ?? ''));
            $isNumericId = str_contains($type, 'int');

            if ($isNumericId) {
                $existingId = null;
                if (!empty($user->id_archivo) && ctype_digit((string) $user->id_archivo)) {
                    $candidate = (int) $user->id_archivo;
                    if (DB::table('ad_archivo_digital')->where('id', $candidate)->exists()) {
                        $existingId = $candidate;
                    }
                }

                $payload = [
                    'tipo_documento_codigo' => null,
                    'tipo_archivo_codigo' => null,
                    'nombre_original' => $file->getClientOriginalName(),
                    'ruta' => '',
                    'digital' => $encrypted,
                    'tipo_mime' => $mime,
                    'extension' => $ext,
                    'tamano' => (int) $file->getSize(),
                    'descripcion' => 'Foto perfil usuario ' . ($user->id ?? ''),
                    'estado' => null,
                    'updated_at' => $now,
                ];

                if ($existingId !== null) {
                    DB::table('ad_archivo_digital')->where('id', $existingId)->update($payload);
                    return (string) $existingId;
                }

                $payload['created_at'] = $now;
                DB::table('ad_archivo_digital')->insert($payload);
                return (string) DB::getPdo()->lastInsertId();
            }

            // ID varchar(10): usar Eloquent + generador
            $archivo = null;
            if (!empty($user->id_archivo)) {
                $archivo = AdArchivoDigital::where('id', $user->id_archivo)->first();
            }
            if (!$archivo) {
                $archivo = new AdArchivoDigital();
            }

            if (empty($archivo->id)) {
                $archivo->id = IdGenerator::next(AdArchivoDigital::OBJETO_CONTROL);
            }

            $archivo->tipo_documento_codigo = null;
            $archivo->tipo_archivo_codigo = null;
            $archivo->nombre_original = $file->getClientOriginalName();
            $archivo->ruta = '';
            $archivo->digital = $encrypted;
            $archivo->tipo_mime = $mime;
            $archivo->extension = $ext;
            $archivo->tamano = (int) $file->getSize();
            $archivo->descripcion = 'Foto perfil usuario ' . ($user->id ?? '');
            $archivo->estado = null;
            $archivo->save();

            return (string) $archivo->id;
        } catch (\Throwable $e) {
            \Log::warning('No se pudo guardar foto de perfil en ad_archivo_digital', [
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * privacy policy URL for 
     * social login purposes
     * @return view
     */
    public function privacyPolicy(){
        return view('privacyPolicy');
    }
    
    /**
     * Redirect to facebook provider
     * @return redirect
     */
    public function redirectToFacebookProvider(){
        return Socialite::driver('facebook')->redirect();
    }
    
    /**
     * Get User Social Details and log him in
     * @return type
     */
    public function handleFacebookCallback(){
         $User = Socialite::driver('facebook')->user();
         return $this->loginSocailUsers($User);
    }
    
    
    /**
     * Redirect to google provider
     * @return redirect
     */
    public function redirectToGoogleProvider(){
        return Socialite::driver('google')->redirect();
    }
    
    /**
     * Get User Social details and log him in
     * @return redirect
     */
    public function handleGoogleCallback(){
         $User = Socialite::driver('google')->user();
         return $this->loginSocailUsers($User);
    }
    
    /**
     * Redirect to twitter provider
     * @return redirect
     */
    public function redirectToTwitterProvider(){
        return Socialite::driver('twitter')->redirect();
    }
    
    /**
     * Get User Social details and log him in
     * @return redirect
     */
    public function handleTwitterCallback(){
         $User = Socialite::driver('twitter')->user();
         return $this->loginSocailUsers($User);
    }
    
    /**
     * Log User by his social details
     * @param type $User
     * @return redirect
     */
    public function loginSocailUsers($User){
        try {
            $Mail=$User->getEmail();
            $IsUser=User::where('email',$Mail)->get();
            if($IsUser->count()>0){
                $User=$IsUser->first();
                Auth::login($User);
            }
            else{
                //Create account for Him
                $NewUser= new User();
                $NewUser->name  =   $User->getName();
                $NewUser->email =   $User->getEmail();
                $NewUser->image =   $User->getAvatar();
                $NewUser->save();
                $NewUser->roles()->sync(array(1));
                Auth::login($NewUser);
            }
            return $this->Response->prepareResult(200, [], [], 'User logged in', 'redirect', route('dashboardIndex'));
        } catch (\Exception $exc) {
            return $this->Response->prepareResult(400, [], [], 'Could not log user in', 'redirect', route('dashboardIndex'));
        }
    }
}

?>
