<?php

use App\Models\PgConfiguracion;

if (!function_exists('pg_formato_fecha')) {
    function pg_formato_fecha(): string
    {
        return PgConfiguracion::formatoFecha();
    }
}

if (!function_exists('pg_formato_fecha_solo')) {
    function pg_formato_fecha_solo(): string
    {
        return PgConfiguracion::formatoFechaSolo();
    }
}

if (!function_exists('pg_fecha')) {
    function pg_fecha($date): string
    {
        return PgConfiguracion::formatFecha($date);
    }
}

if (!function_exists('pg_fecha_solo')) {
    function pg_fecha_solo($date): string
    {
        return PgConfiguracion::formatFechaSolo($date);
    }
}

if (!function_exists('pg_placeholder_fecha_solo')) {
    function pg_placeholder_fecha_solo(): string
    {
        return PgConfiguracion::placeholderFechaSolo();
    }
}
