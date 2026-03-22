<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrSeccion extends Model
{
    protected $table = 'fr_seccion';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id','codigo','orden','mostrar',
        'titulo_es','titulo_en','subtitulo_es','subtitulo_en',
        'contenido_es','contenido_en',
        'boton_texto_es','boton_texto_en','boton_url','clase_css',
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
