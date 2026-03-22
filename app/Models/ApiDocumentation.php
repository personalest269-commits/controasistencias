<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ApiDocumentation extends Model
{
    use EstadoSoftDeletes;
    protected $table='api_documentation';
}
