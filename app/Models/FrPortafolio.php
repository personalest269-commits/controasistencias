<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrPortafolio extends Model
{
    protected $table = 'fr_portafolio';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id','orden',
        'titulo_es','titulo_en','categoria_es','categoria_en',
        'imagen_archivo_id','url','estado',
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
