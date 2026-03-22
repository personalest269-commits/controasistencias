<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrServicio extends Model
{
    protected $table = 'fr_servicio';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id','orden','icono',
        'titulo_es','titulo_en','descripcion_es','descripcion_en',
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
