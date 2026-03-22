<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
//included for entrust
use Shanmuga\LaravelEntrust\Traits\LaravelEntrustUserTrait;
use Laravel\Passport\HasApiTokens;
use DateTimeInterface;
use App\Models\PgPlantilla;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LaravelEntrustUserTrait, EstadoSoftDeletes;

    /**
     * Nombre real de la tabla en BD.
     */
    protected $table = 'pg_usuario';

    /**
     * La PK es VARCHAR(10) generada por trigger (no auto-increment).
     */
    public $incrementing = false;

    /**
     * Tipo de llave primaria.
     */
    protected $keyType = 'string';

    
     /**
    * Prepare a date for array / JSON serialization.
    *
    * @param  \DateTimeInterface  $date
    * @return string
    */
    protected function serializeDate(DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id_persona',
        'id_archivo',
        'id_plantillas',
        'name',
        'usuario',
        'email',
        'password',
        'image',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function persona()
    {
        return $this->belongsTo(PgPersona::class, 'id_persona', 'id');
    }

    public function archivoDigital()
    {
        return $this->belongsTo(AdArchivoDigital::class, 'id_archivo', 'id');
    }

    /**
     * Plantilla de interfaz asignada al usuario (AdminLTE / Gentelella).
     */
    public function plantilla()
    {
        return $this->belongsTo(PgPlantilla::class, 'id_plantillas', 'id');
    }

    /**
     * Envia el email de reseteo respetando el idioma actual del sistema.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify((new ResetPasswordNotification($token))->locale(app()->getLocale()));
    }
}
