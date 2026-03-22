<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplateTranslation extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    public const OBJETO_CONTROL = 'EMAIL_PLANTILLAS_TRADUCCION';
    protected $table = 'email_plantillas_traduccion';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'email_template_id',
        'idioma_id',
        'subject',
        'body',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function idioma(): BelongsTo
    {
        return $this->belongsTo(Idioma::class, 'idioma_id');
    }
}
