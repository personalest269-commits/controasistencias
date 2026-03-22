<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PgApiConfig extends Model
{
    protected $table = 'pg_api_config';

    protected $fillable = [
        'clave',
        'api_url',
        'auth_type',
        'auth_user',
        'auth_pass',
        'auth_token',
        'query_params',
    ];

    protected $casts = [
        'query_params' => 'array',
    ];
}
