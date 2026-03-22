<?php

namespace App\Services;

use App\Models\FrMenu;
use App\Models\FrPaginaInicio;
use App\Models\FrPortafolio;
use App\Models\FrSeccion;
use App\Models\FrServicio;
use Illuminate\Support\Facades\Schema;

class FrFrontedService
{
    /**
     * Obtiene toda la parametrización necesaria para renderizar el frontend.
     * Si las tablas no existen, retorna estructura por defecto.
     */
    public static function get(): array
    {
        if (!Schema::hasTable('fr_pagina_inicio')) {
            return [
                'pagina' => null,
                'menu' => [],
                'secciones' => [],
                'servicios' => [],
                'portafolio' => [],
            ];
        }

        $pagina = FrPaginaInicio::query()->whereNull('estado')->orderBy('id')->first();
        $menu = Schema::hasTable('fr_menu') ? FrMenu::query()->whereNull('estado')->orderBy('orden')->get() : collect();
        $secciones = Schema::hasTable('fr_seccion') ? FrSeccion::query()->whereNull('estado')->orderBy('orden')->get() : collect();
        $servicios = Schema::hasTable('fr_servicio') ? FrServicio::query()->whereNull('estado')->orderBy('orden')->get() : collect();
        $portafolio = Schema::hasTable('fr_portafolio') ? FrPortafolio::query()->whereNull('estado')->orderBy('orden')->get() : collect();

        // Index secciones por código
        $secByCode = [];
        foreach ($secciones as $s) {
            $secByCode[(string) $s->codigo] = $s;
        }

        return [
            'pagina' => $pagina,
            'menu' => $menu,
            'secciones' => $secciones,
            'seccion' => $secByCode,
            'servicios' => $servicios,
            'portafolio' => $portafolio,
        ];
    }
}
