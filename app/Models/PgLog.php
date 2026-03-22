<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use DateTimeInterface;

class PgLog extends Model
{
    protected $table = 'pg_log';

    protected $fillable = [
        'level',
        'channel',
        'message',
        'exception_class',
        'exception_code',
        'file',
        'line',
        'trace',
        'context',
        'url',
        'method',
        'ip',
        'user_agent',
        'usuario_id',
        'estado',
        'resolved_at',
        'resolved_by',
    ];

    protected $casts = [
        'context' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * Formato de fechas consistente en API/Datatables.
     */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id');
    }
}
