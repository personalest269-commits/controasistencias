<?php

namespace App\Http\Controllers;

use App\Models\FrMenu;
use App\Models\FrPaginaInicio;
use App\Models\FrPortafolio;
use App\Models\FrSeccion;
use App\Models\FrServicio;
use App\Services\ArchivoDigitalService;
use App\Services\IdGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class FrFrontedController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function Index()
    {
        if (!Schema::hasTable('fr_pagina_inicio')) {
            return redirect()->back()->withErrors(['fr' => 'No existe la estructura del frontend. Ejecuta migraciones.']);
        }

        $pagina = FrPaginaInicio::query()->whereNull('estado')->orderBy('id')->first();
        if (!$pagina) {
            $pagina = new FrPaginaInicio();
            $pagina->id = '0000000001';
            $pagina->save();
        }

        $menu = Schema::hasTable('fr_menu') ? FrMenu::query()->whereNull('estado')->orderBy('orden')->get() : collect();
        $secciones = Schema::hasTable('fr_seccion') ? FrSeccion::query()->whereNull('estado')->orderBy('orden')->get() : collect();
        $servicios = Schema::hasTable('fr_servicio') ? FrServicio::query()->whereNull('estado')->orderBy('orden')->get() : collect();
        $portafolio = Schema::hasTable('fr_portafolio') ? FrPortafolio::query()->whereNull('estado')->orderBy('orden')->get() : collect();

        return view('FrFronted.index', [
            'pagina' => $pagina,
            'menuItems' => $menu,
            'secciones' => $secciones,
            'servicios' => $servicios,
            'portafolio' => $portafolio,
        ]);
    }

    public function UpdatePagina(Request $request)
    {
        $pagina = FrPaginaInicio::query()->where('id', $request->input('id', '0000000001'))->first();
        if (!$pagina) {
            $pagina = new FrPaginaInicio();
            $pagina->id = '0000000001';
        }

        $rules = [
            'nombre_sitio_es' => 'nullable|string|max:200',
            'nombre_sitio_en' => 'nullable|string|max:200',
            'hero_titulo_es' => 'nullable|string|max:255',
            'hero_titulo_en' => 'nullable|string|max:255',
            'hero_subtitulo_es' => 'nullable|string',
            'hero_subtitulo_en' => 'nullable|string',
            'hero_boton_texto_es' => 'nullable|string|max:120',
            'hero_boton_texto_en' => 'nullable|string|max:120',
            'hero_boton_url' => 'nullable|string|max:600',
            'contacto_telefono' => 'nullable|string|max:120',
            'contacto_email' => 'nullable|string|max:255',
            'contacto_direccion_es' => 'nullable|string|max:255',
            'contacto_direccion_en' => 'nullable|string|max:255',
            'cookies_texto_es' => 'nullable|string',
            'cookies_texto_en' => 'nullable|string',
            'cookies_btn_aceptar_es' => 'nullable|string|max:80',
            'cookies_btn_aceptar_en' => 'nullable|string|max:80',
            'cookies_btn_rechazar_es' => 'nullable|string|max:80',
            'cookies_btn_rechazar_en' => 'nullable|string|max:80',
            'logo' => 'nullable|file|max:5120',
            'hero_fondo' => 'nullable|file|max:10240',
        ];

        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) {
            return redirect()->back()->withErrors($v)->withInput();
        }

        foreach (array_keys($rules) as $k) {
            if (in_array($k, ['logo', 'hero_fondo'], true)) continue;
            if ($request->has($k)) {
                $pagina->{$k} = is_string($request->{$k}) ? trim($request->{$k}) : $request->{$k};
            }
        }

        // Cookies checkbox
        $pagina->cookies_activo = $request->boolean('cookies_activo') ? 'S' : 'N';

        // Subir logo (solo imagen)
        if ($request->hasFile('logo')) {
            $file = $request->file('logo');
            if ($file && $file->isValid()) {
                $ext = strtolower($file->getClientOriginalExtension() ?: '');
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    return redirect()->back()->withErrors(['logo' => 'El logo debe ser una imagen (jpg/png/webp).'])->withInput();
                }
                $idArchivo = ArchivoDigitalService::store($file, 'Logo frontend');
                if (!$idArchivo) {
                    return redirect()->back()->withErrors(['logo' => 'No se pudo guardar el logo.'])->withInput();
                }
                $pagina->logo_archivo_id = $idArchivo;
            }
        }

        // Subir fondo hero (solo imagen)
        if ($request->hasFile('hero_fondo')) {
            $file = $request->file('hero_fondo');
            if ($file && $file->isValid()) {
                $ext = strtolower($file->getClientOriginalExtension() ?: '');
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    return redirect()->back()->withErrors(['hero_fondo' => 'El fondo debe ser una imagen (jpg/png/webp).'])->withInput();
                }
                $idArchivo = ArchivoDigitalService::store($file, 'Fondo hero frontend');
                if (!$idArchivo) {
                    return redirect()->back()->withErrors(['hero_fondo' => 'No se pudo guardar el fondo.'])->withInput();
                }
                $pagina->hero_fondo_archivo_id = $idArchivo;
            }
        }

        $pagina->estado = null;
        $pagina->save();

        return redirect()->route('FrFrontedIndex')->with('success', 'Frontend actualizado.');
    }

    public function SaveMenu(Request $request)
    {
        if (!Schema::hasTable('fr_menu')) {
            return redirect()->back()->withErrors(['menu' => 'No existe la tabla fr_menu.']);
        }

        $rules = [
            'id' => 'nullable|string|max:10',
            'orden' => 'nullable|integer|min:1|max:9999',
            'texto_es' => 'required|string|max:120',
            'texto_en' => 'nullable|string|max:120',
            'tipo' => 'required|in:anchor,route,url',
            'destino' => 'nullable|string|max:600',
            // nuevo_tab se procesa como boolean (checkbox)
            'nuevo_tab' => 'nullable',
        ];
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) return redirect()->back()->withErrors($v)->withInput();

        $id = trim((string) ($request->input('id') ?? ''));
        $item = $id !== '' ? FrMenu::where('id', $id)->first() : null;
        if (!$item) {
            $item = new FrMenu();
            $item->id = IdGenerator::next('FR_MENU');
        }

        $item->orden = (int) ($request->input('orden') ?: 1);
        $item->texto_es = trim((string) $request->texto_es);
        $item->texto_en = $request->filled('texto_en') ? trim((string) $request->texto_en) : null;
        $item->tipo = trim((string) $request->tipo);
        $item->destino = $request->filled('destino') ? trim((string) $request->destino) : null;
        $item->nuevo_tab = $request->boolean('nuevo_tab') ? 'S' : 'N';
        $item->estado = null;
        $item->save();

        return redirect()->route('FrFrontedIndex')->with('success', 'Menú guardado.');
    }

    public function DeleteMenu($id)
    {
        $item = FrMenu::where('id', $id)->firstOrFail();
        $item->estado = 'X';
        $item->save();
        return redirect()->route('FrFrontedIndex')->with('success', 'Menú eliminado.');
    }

    public function SaveSeccion(Request $request)
    {
        if (!Schema::hasTable('fr_seccion')) {
            return redirect()->back()->withErrors(['seccion' => 'No existe la tabla fr_seccion.']);
        }

        $rules = [
            'id' => 'nullable|string|max:10',
            'codigo' => 'required|string|max:50',
            'orden' => 'nullable|integer|min:1|max:9999',
            // mostrar se procesa como boolean (checkbox)
            'mostrar' => 'nullable',
            'titulo_es' => 'nullable|string|max:255',
            'titulo_en' => 'nullable|string|max:255',
            'subtitulo_es' => 'nullable|string',
            'subtitulo_en' => 'nullable|string',
            'contenido_es' => 'nullable|string',
            'contenido_en' => 'nullable|string',
            'boton_texto_es' => 'nullable|string|max:120',
            'boton_texto_en' => 'nullable|string|max:120',
            'boton_url' => 'nullable|string|max:600',
            'clase_css' => 'nullable|string|max:255',
        ];

        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) return redirect()->back()->withErrors($v)->withInput();

        $id = trim((string) ($request->input('id') ?? ''));
        $sec = $id !== '' ? FrSeccion::where('id', $id)->first() : null;
        if (!$sec) {
            $sec = new FrSeccion();
            $sec->id = IdGenerator::next('FR_SECCION');
        }

        $sec->codigo = trim((string) $request->codigo);
        $sec->orden = (int) ($request->input('orden') ?: 1);
        $sec->mostrar = $request->boolean('mostrar') ? 'S' : 'N';
        $sec->titulo_es = $request->filled('titulo_es') ? trim((string) $request->titulo_es) : null;
        $sec->titulo_en = $request->filled('titulo_en') ? trim((string) $request->titulo_en) : null;
        $sec->subtitulo_es = $request->filled('subtitulo_es') ? $request->subtitulo_es : null;
        $sec->subtitulo_en = $request->filled('subtitulo_en') ? $request->subtitulo_en : null;
        $sec->contenido_es = $request->filled('contenido_es') ? $request->contenido_es : null;
        $sec->contenido_en = $request->filled('contenido_en') ? $request->contenido_en : null;
        $sec->boton_texto_es = $request->filled('boton_texto_es') ? trim((string) $request->boton_texto_es) : null;
        $sec->boton_texto_en = $request->filled('boton_texto_en') ? trim((string) $request->boton_texto_en) : null;
        $sec->boton_url = $request->filled('boton_url') ? trim((string) $request->boton_url) : null;
        $sec->clase_css = $request->filled('clase_css') ? trim((string) $request->clase_css) : null;
        $sec->estado = null;
        $sec->save();

        return redirect()->route('FrFrontedIndex')->with('success', 'Sección guardada.');
    }

    public function DeleteSeccion($id)
    {
        $sec = FrSeccion::where('id', $id)->firstOrFail();
        $sec->estado = 'X';
        $sec->save();
        return redirect()->route('FrFrontedIndex')->with('success', 'Sección eliminada.');
    }

    public function SaveServicio(Request $request)
    {
        if (!Schema::hasTable('fr_servicio')) {
            return redirect()->back()->withErrors(['servicio' => 'No existe la tabla fr_servicio.']);
        }

        $rules = [
            'id' => 'nullable|string|max:10',
            'orden' => 'nullable|integer|min:1|max:9999',
            'icono' => 'nullable|string|max:80',
            'titulo_es' => 'required|string|max:200',
            'titulo_en' => 'nullable|string|max:200',
            'descripcion_es' => 'nullable|string',
            'descripcion_en' => 'nullable|string',
        ];
        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) return redirect()->back()->withErrors($v)->withInput();

        $id = trim((string) ($request->input('id') ?? ''));
        $srv = $id !== '' ? FrServicio::where('id', $id)->first() : null;
        if (!$srv) {
            $srv = new FrServicio();
            $srv->id = IdGenerator::next('FR_SERVICIO');
        }

        $srv->orden = (int) ($request->input('orden') ?: 1);
        $srv->icono = $request->filled('icono') ? trim((string) $request->icono) : null;
        $srv->titulo_es = trim((string) $request->titulo_es);
        $srv->titulo_en = $request->filled('titulo_en') ? trim((string) $request->titulo_en) : null;
        $srv->descripcion_es = $request->filled('descripcion_es') ? $request->descripcion_es : null;
        $srv->descripcion_en = $request->filled('descripcion_en') ? $request->descripcion_en : null;
        $srv->estado = null;
        $srv->save();

        return redirect()->route('FrFrontedIndex')->with('success', 'Servicio guardado.');
    }

    public function DeleteServicio($id)
    {
        $srv = FrServicio::where('id', $id)->firstOrFail();
        $srv->estado = 'X';
        $srv->save();
        return redirect()->route('FrFrontedIndex')->with('success', 'Servicio eliminado.');
    }

    public function SavePortafolio(Request $request)
    {
        if (!Schema::hasTable('fr_portafolio')) {
            return redirect()->back()->withErrors(['portafolio' => 'No existe la tabla fr_portafolio.']);
        }

        $rules = [
            'id' => 'nullable|string|max:10',
            'orden' => 'nullable|integer|min:1|max:9999',
            'categoria_es' => 'nullable|string|max:200',
            'categoria_en' => 'nullable|string|max:200',
            'titulo_es' => 'nullable|string|max:200',
            'titulo_en' => 'nullable|string|max:200',
            'url' => 'nullable|string|max:600',
            'imagen' => 'nullable|file|max:10240',
        ];

        $v = Validator::make($request->all(), $rules);
        if ($v->fails()) return redirect()->back()->withErrors($v)->withInput();

        $id = trim((string) ($request->input('id') ?? ''));
        $p = $id !== '' ? FrPortafolio::where('id', $id)->first() : null;
        if (!$p) {
            $p = new FrPortafolio();
            $p->id = IdGenerator::next('FR_PORTAFOLIO');
        }

        $p->orden = (int) ($request->input('orden') ?: 1);
        $p->categoria_es = $request->filled('categoria_es') ? trim((string) $request->categoria_es) : null;
        $p->categoria_en = $request->filled('categoria_en') ? trim((string) $request->categoria_en) : null;
        $p->titulo_es = $request->filled('titulo_es') ? trim((string) $request->titulo_es) : null;
        $p->titulo_en = $request->filled('titulo_en') ? trim((string) $request->titulo_en) : null;
        $p->url = $request->filled('url') ? trim((string) $request->url) : null;

        if ($request->hasFile('imagen')) {
            $file = $request->file('imagen');
            if ($file && $file->isValid()) {
                $ext = strtolower($file->getClientOriginalExtension() ?: '');
                if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                    return redirect()->back()->withErrors(['imagen' => 'La imagen debe ser (jpg/png/webp).'])->withInput();
                }
                $idArchivo = ArchivoDigitalService::store($file, 'Imagen portafolio');
                if ($idArchivo) {
                    $p->imagen_archivo_id = $idArchivo;
                }
            }
        }

        $p->estado = null;
        $p->save();

        return redirect()->route('FrFrontedIndex')->with('success', 'Portafolio guardado.');
    }

    public function DeletePortafolio($id)
    {
        $p = FrPortafolio::where('id', $id)->firstOrFail();
        $p->estado = 'X';
        $p->save();
        return redirect()->route('FrFrontedIndex')->with('success', 'Portafolio eliminado.');
    }
}
