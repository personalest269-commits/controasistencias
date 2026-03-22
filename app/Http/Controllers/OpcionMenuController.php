<?php

namespace App\Http\Controllers;

use App\Models\AdArchivoDigital;
use App\Models\PgOpcionMenu;
use App\Models\PgOpcionMenuRol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class OpcionMenuController extends Controller
{
    private function normalizeExt($ext): string
    {
        $ext = strtolower(trim((string) $ext));
        return ltrim($ext, '.');
    }

    public function __construct()
    {
        parent::__construct();
    }

    public function Index(Request $request)
    {
        // Symfony 7.4+ deprecates Request::get(), use query/request bags explicitly.
        $soloEliminados = $request->query('eliminados') == 1;

        $query = PgOpcionMenu::with(['padre', 'roles.role'])->orderBy('id_padre')->orderBy('orden');
        if ($soloEliminados) {
            $query = PgOpcionMenu::conEliminados()->soloEliminados()->with(['padre', 'roles.role'])->orderBy('id_padre')->orderBy('orden');
        }

        $opciones = $query->paginate(25)->appends($request->query());
        $roles = DB::table('roles')->orderBy('name')->get();

        return view('OpcionesMenu.index', [
            'opciones' => $opciones,
            'roles' => $roles,
            'soloEliminados' => $soloEliminados,
        ]);
    }

    public function Create()
    {
        $padres = PgOpcionMenu::orderBy('titulo')->get();
        $roles = DB::table('roles')->orderBy('name')->get();

        return view('OpcionesMenu.create', [
            'padres' => $padres,
            'roles' => $roles,
        ]);
    }

    public function Store(Request $request)
    {
        $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            // Los IDs pasan a VARCHAR(10). Aceptamos string numérico.
            'id_padre' => ['nullable', 'string', 'exists:pg_opcion_menu,id'],
            'url' => ['nullable', 'string', 'max:255'],
            'tipo' => ['required', 'in:G,M'],
            'activo' => ['required', 'in:S,N'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,id'],
            'imagen' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $menu = new PgOpcionMenu();
        $menu->titulo = strip_tags($request->titulo);
        $menu->id_padre = $request->filled('id_padre') ? (string) $request->id_padre : null;
        $menu->url = $request->filled('url') ? trim((string)$request->url) : ($request->tipo === 'G' ? '#' : null);
        $menu->tipo = $request->tipo;
        $menu->activo = $request->activo;
        $menu->orden = $request->filled('orden') ? (int)$request->orden : 0;
        $menu->estado = null;

        // imagen (opcional)
        if ($request->file('imagen')) {
            $archivoId = $this->guardarImagenDigital($request->file('imagen'), 'Icono menú: '.$menu->titulo);
            $menu->id_archivo = $archivoId;
        }

        $menu->save();

        $this->syncRoles($menu->id, (array) $request->input('roles', []));

        return redirect()->route('OpcionMenuIndex')->with('success', 'Opción de menú creada correctamente.');
    }

    public function Edit($id)
    {
        $menu = PgOpcionMenu::with(['roles.role', 'archivo'])->conEliminados()->where('id', $id)->firstOrFail();
        $padres = PgOpcionMenu::where('id', '!=', $menu->id)->orderBy('titulo')->get();
        $roles = DB::table('roles')->orderBy('name')->get();

        $rolesSeleccionados = $menu->roles->pluck('id_rol')->map(fn ($v) => (string) $v)->toArray();

        return view('OpcionesMenu.edit', [
            'menu' => $menu,
            'padres' => $padres,
            'roles' => $roles,
            'rolesSeleccionados' => $rolesSeleccionados,
        ]);
    }

    public function Update(Request $request, $id)
    {
        $menu = PgOpcionMenu::conEliminados()->where('id', $id)->firstOrFail();

        $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'id_padre' => ['nullable', 'string', 'exists:pg_opcion_menu,id', 'not_in:'.$menu->id],
            'url' => ['nullable', 'string', 'max:255'],
            'tipo' => ['required', 'in:G,M'],
            'activo' => ['required', 'in:S,N'],
            'orden' => ['nullable', 'integer', 'min:0', 'max:32767'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string', 'exists:roles,id'],
            'imagen' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'quitar_imagen' => ['nullable', 'in:1'],
        ]);

        $menu->titulo = strip_tags($request->titulo);
        $menu->id_padre = $request->filled('id_padre') ? (string) $request->id_padre : null;
        $menu->url = $request->filled('url') ? trim((string)$request->url) : ($request->tipo === 'G' ? '#' : null);
        $menu->tipo = $request->tipo;
        $menu->activo = $request->activo;
        $menu->orden = $request->filled('orden') ? (int)$request->orden : 0;

        if ($request->input('quitar_imagen') == '1') {
            $menu->id_archivo = null;
        }

        if ($request->file('imagen')) {
            $archivoId = $this->guardarImagenDigital($request->file('imagen'), 'Icono menú: '.$menu->titulo);
            $menu->id_archivo = $archivoId;
        }

        $menu->save();

        $this->syncRoles($menu->id, (array) $request->input('roles', []));

        return redirect()->route('OpcionMenuEdit', $menu->id)->with('success', 'Opción de menú actualizada correctamente.');
    }

    public function Delete($id)
    {
        $menu = PgOpcionMenu::conEliminados()->where('id', $id)->firstOrFail();
        $menu->delete();

        // también marcamos pivotes como eliminados lógicos
        PgOpcionMenuRol::conEliminados()->where('id_opcion_menu', $menu->id)->update(['estado' => 'X']);

        return redirect()->route('OpcionMenuIndex')->with('success', 'Opción de menú eliminada (lógico) correctamente.');
    }

    private function syncRoles(string $menuId, array $roles): void
    {
        // Normalizar
        $roles = array_values(array_unique(array_filter(array_map(function ($r) {
            if ($r === null || $r === '') { return null; }
            return (string) $r;
        }, $roles), fn ($v) => !is_null($v) && $v !== '0')));

        // Eliminar relaciones actuales (lógicamente) y recrear
        PgOpcionMenuRol::conEliminados()
            ->where('id_opcion_menu', $menuId)
            ->update(['estado' => 'X']);

        foreach ($roles as $rolId) {
            // Upsert
            $existing = PgOpcionMenuRol::conEliminados()
                ->where('id_opcion_menu', $menuId)
                ->where('id_rol', $rolId)
                ->first();

            if ($existing) {
                $existing->estado = null;
                $existing->save();
            } else {
                PgOpcionMenuRol::create([
                    'id_opcion_menu' => $menuId,
                    'id_rol' => $rolId,
                    'estado' => null,
                ]);
            }
        }
    }

    private function guardarImagenDigital($file, string $descripcion): string
    {
        $binary = @file_get_contents($file->getRealPath());
        if ($binary === false) {
            throw new \RuntimeException('No se pudo leer el archivo subido.');
        }

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
        $archivo->descripcion = $descripcion;
        $archivo->estado = null;
        $archivo->save();

        return (string) $archivo->id;
    }
}
