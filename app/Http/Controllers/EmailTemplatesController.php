<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateTranslation;
use App\Models\Idioma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;

class EmailTemplatesController extends Controller
{
    public $Response;

    public function __construct()
    {
        parent::__construct();
        $this->Response = new ResponseController();
    }

    public function index()
    {
        if (!Schema::hasTable('email_plantillas')) {
            return $this->Response->prepareResult(500, [], [], null, 'view', 'errors.500', 'Missing table email_plantillas. Please run migrations.');
        }
        $templates = EmailTemplate::query()->orderBy('name')->get();
        return $this->Response->prepareResult(200, ['templates' => $templates], [], [], 'view', 'emailtemplates.index');
    }

    public function edit(string $slug)
    {
        if (!Schema::hasTable('email_plantillas') || !Schema::hasTable('email_plantillas_traduccion')) {
            return $this->Response->prepareResult(500, [], [], null, 'view', 'errors.500', 'Missing email template tables. Please run migrations.');
        }
        $template = EmailTemplate::with('translations.idioma')->where('slug', $slug)->firstOrFail();

        // Only two languages (English + Spanish), stored in table "pg_idiomas".
        $locales = [];
        try {
            if (Schema::hasTable('pg_idiomas')) {
                $idiomas = Idioma::query()->where('activo', 1)->orderBy('por_defecto', 'desc')->orderBy('nombre')->get();
                foreach ($idiomas as $i) {
                    $locales[$i->codigo] = $i->nombre;
                }
            }
        } catch (\Throwable $e) {
            // ignore
        }
        if (empty($locales)) {
            $locales = ['en' => 'English', 'es' => 'Español'];
        }

        // Map codigo => idioma_id (id puede ser VARCHAR(10) tipo 0000000001)
        $codigoToId = [];
        try {
            if (Schema::hasTable('pg_idiomas')) {
                $codigoToId = Idioma::query()->pluck('id', 'codigo')->mapWithKeys(function ($id, $codigo) {
                    return [(string) $codigo => (string) $id];
                })->all();
            }
        } catch (\Throwable $e) {
            $codigoToId = [];
        }

        $translations = [];
        foreach (array_keys($locales) as $locale) {
            $idiomaId = $codigoToId[$locale] ?? null;
            $tr = $idiomaId ? $template->translations->firstWhere('idioma_id', $idiomaId) : null;
            $translations[$locale] = [
                'subject' => $tr?->subject ?? '',
                'body' => $tr?->body ?? '',
            ];
        }

        return $this->Response->prepareResult(200, [
            'template' => $template,
            'locales' => $locales,
            'translations' => $translations,
        ], [], [], 'view', 'emailtemplates.edit');
    }

    public function update(Request $request, string $slug)
    {
        if (!Schema::hasTable('email_plantillas') || !Schema::hasTable('email_plantillas_traduccion')) {
            return $this->Response->prepareResult(500, [], [], null, 'ajax', null, 'Missing email template tables. Please run migrations.');
        }
        $template = EmailTemplate::where('slug', $slug)->firstOrFail();

        // Allowed locales (only English + Spanish).
        $allowedLocales = ['en', 'es'];
        try {
            if (Schema::hasTable('pg_idiomas')) {
                $allowedLocales = Idioma::query()->where('activo', 1)->pluck('codigo')->map(fn ($c) => (string) $c)->all();
                $allowedLocales = array_values(array_unique(array_filter($allowedLocales)));
                if (empty($allowedLocales)) {
                    $allowedLocales = ['en', 'es'];
                }
            }
        } catch (\Throwable $e) {
            $allowedLocales = ['en', 'es'];
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'from_name' => 'nullable|string|max:255',
            'subject' => 'required|array',
            'body' => 'required|array',
        ]);

        if ($validator->fails()) {
            return $this->Response->prepareResult(422, [], $validator, null, 'ajax', null, 'Validation error');
        }

        $template->name = $request->input('name');
        $template->from_name = $request->input('from_name');
        $template->save();

        $subjects = $request->input('subject', []);
        $bodies = $request->input('body', []);

        foreach ($subjects as $locale => $subject) {
            // Safety: only allow locales registered in table "pg_idiomas".
            if (!in_array($locale, $allowedLocales, true)) {
                continue;
            }
            $body = $bodies[$locale] ?? '';

            $idiomaId = null;
            try {
                $idiomaId = Idioma::query()->where('codigo', $locale)->value('id');
            } catch (\Throwable $e) {
                $idiomaId = null;
            }

            if (!$idiomaId) {
                continue;
            }

            $tr = EmailTemplateTranslation::query()->firstOrNew([
                'email_template_id' => $template->id,
                'idioma_id' => (string) $idiomaId,
            ]);
            $tr->subject = (string) $subject;
            $tr->body = (string) $body;
            $tr->save();
        }

        return $this->Response->prepareResult(200, [], [], 'Template saved successfully', 'ajax');
    }
}
