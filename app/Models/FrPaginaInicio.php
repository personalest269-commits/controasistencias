<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrPaginaInicio extends Model
{
    protected $table = 'fr_pagina_inicio';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'nombre_sitio_es','nombre_sitio_en','logo_archivo_id',
        'hero_titulo_es','hero_titulo_en','hero_subtitulo_es','hero_subtitulo_en',
        'hero_boton_texto_es','hero_boton_texto_en','hero_boton_url','hero_fondo_archivo_id',
        'contacto_telefono','contacto_email','contacto_direccion_es','contacto_direccion_en',
        'cookies_activo','cookies_texto_es','cookies_texto_en',
        'cookies_btn_aceptar_es','cookies_btn_aceptar_en','cookies_btn_rechazar_es','cookies_btn_rechazar_en',
        'estado',
    ];

    public function t(string $base): string
    {
        $lang = app()->getLocale();
        $en = $base . '_en';
        $es = $base . '_es';
        if ($lang === 'en') {
            $v = (string) ($this->{$en} ?? '');
            if (trim($v) !== '') return $v;
        }
        return (string) ($this->{$es} ?? '');
    }
}
