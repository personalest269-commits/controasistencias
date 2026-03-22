<?php

namespace App\Models;

use App\Models\Concerns\EstadoSoftDeletes;
use App\Models\Concerns\GeneraIdVarchar;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    use EstadoSoftDeletes, GeneraIdVarchar;

    public const OBJETO_CONTROL = 'EMAIL_PLANTILLAS';
    protected $table = 'email_plantillas';

    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'slug',
        'name',
        'from_name',
        'variables',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    public function translations(): HasMany
    {
        return $this->hasMany(EmailTemplateTranslation::class, 'email_template_id');
    }

    public function translationFor(string $locale): ?EmailTemplateTranslation
    {
        $locale = $locale ?: 'en';

        // If translations are already loaded, filter in-memory.
        if ($this->relationLoaded('translations')) {
            $this->translations->loadMissing('idioma');

            $match = $this->translations->first(function ($tr) use ($locale) {
                return $tr->idioma && $tr->idioma->codigo === $locale;
            });

            if ($match) {
                return $match;
            }

            $fallback = $this->translations->first(function ($tr) {
                return $tr->idioma && $tr->idioma->codigo === 'en';
            });

            return $fallback ?? $this->translations->first();
        }

        // Otherwise query.
        $match = $this->translations()
            ->whereHas('idioma', fn ($q) => $q->where('codigo', $locale))
            ->first();

        if ($match) {
            return $match;
        }

        $fallback = $this->translations()
            ->whereHas('idioma', fn ($q) => $q->where('codigo', 'en'))
            ->first();

        return $fallback ?? $this->translations()->first();
    }
}
