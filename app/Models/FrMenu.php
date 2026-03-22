<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrMenu extends Model
{
    protected $table = 'fr_menu';
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id','orden','texto_es','texto_en','tipo','destino','nuevo_tab','estado',
    ];

    public function t(): string
    {
        $lang = app()->getLocale();
        if ($lang === 'en' && !empty($this->texto_en)) {
            return (string) $this->texto_en;
        }
        return (string) $this->texto_es;
    }
}
