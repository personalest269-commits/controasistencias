<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * NOTE:
     * - Some older versions of the project used a typo table name: email_plantillas_tranduccion.
     * - The correct Spanish name is: email_plantillas_traduccion.
     * This migration is defensive and supports both names.
     */
    private function resolveTranslationsTable(): ?string
    {
        if (Schema::hasTable('email_plantillas_traduccion')) {
            return 'email_plantillas_traduccion';
        }
        if (Schema::hasTable('email_plantillas_tranduccion')) {
            return 'email_plantillas_tranduccion';
        }
        return null;
    }

    public function up(): void
    {
        $table = $this->resolveTranslationsTable();
        if (!$table || !Schema::hasTable('pg_idiomas')) {
            return;
        }

        // 1) Add idioma_id column (nullable first so we can backfill safely).
        if (!Schema::hasColumn($table, 'idioma_id')) {
            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('idioma_id')->nullable()->after('email_template_id');
                $t->index('idioma_id');
            });
        }

        // 2) Backfill idioma_id using the existing `locale` column (if it exists).
        $idiomas = DB::table('pg_idiomas')->select('id', 'codigo')->get();
        $map = [];
        foreach ($idiomas as $i) {
            $map[(string) $i->codigo] = (int) $i->id;
        }

        $defaultIdiomaId = $map['es'] ?? ($map['en'] ?? null);

        if (Schema::hasColumn($table, 'locale')) {
            $rows = DB::table($table)
                ->select('id', 'locale')
                ->whereNull('idioma_id')
                ->get();

            foreach ($rows as $r) {
                $codigo = (string) $r->locale;
                $idiomaId = $map[$codigo] ?? $defaultIdiomaId;
                if ($idiomaId) {
                    DB::table($table)
                        ->where('id', (int) $r->id)
                        ->update(['idioma_id' => $idiomaId]);
                }
            }
        }

        // Ensure there are no NULLs left (fallback to default language).
        if ($defaultIdiomaId) {
            DB::table($table)
                ->whereNull('idioma_id')
                ->update(['idioma_id' => $defaultIdiomaId]);
        }

        // 3) Add FK constraint to idiomas (best-effort).
        $fkName = $table . '_idioma_id_fk';
        try {
            Schema::table($table, function (Blueprint $t) use ($fkName) {
                $t->foreign('idioma_id', $fkName)
                    ->references('id')->on('pg_idiomas')
                    ->cascadeOnUpdate()
                    ->restrictOnDelete();
            });
        } catch (Throwable $e) {
            // ignore
        }

        // 4) Drop old unique(email_template_id, locale) if present.
        // The original index name (created when the table was called "email_template_translations"):
        try {
            Schema::table($table, function (Blueprint $t) {
                $t->dropUnique('email_template_translations_email_template_id_locale_unique');
            });
        } catch (Throwable $e) {
            // ignore
        }

        // Some environments may have an auto-generated index name after table rename.
        try {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique($table . '_email_template_id_locale_unique');
            });
        } catch (Throwable $e) {
            // ignore
        }

        // Drop legacy locale column (only if it exists).
        if (Schema::hasColumn($table, 'locale')) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('locale');
            });
        }

        // Add the new unique constraint (email_template_id, idioma_id)
        try {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->unique(['email_template_id', 'idioma_id'], $table . '_template_idioma_unique');
            });
        } catch (Throwable $e) {
            // ignore
        }
    }

    public function down(): void
    {
        $table = $this->resolveTranslationsTable();
        if (!$table) {
            return;
        }

        // Recreate locale column (best-effort) and remove idioma FK.
        if (!Schema::hasColumn($table, 'locale')) {
            Schema::table($table, function (Blueprint $t) {
                $t->string('locale', 10)->nullable()->after('email_template_id');
            });
        }

        if (Schema::hasTable('pg_idiomas')) {
            $idiomas = DB::table('pg_idiomas')->select('id', 'codigo')->get();
            $idToCode = [];
            foreach ($idiomas as $i) {
                $idToCode[(int) $i->id] = (string) $i->codigo;
            }

            if (Schema::hasColumn($table, 'idioma_id')) {
                $rows = DB::table($table)->select('id', 'idioma_id')->get();
                foreach ($rows as $r) {
                    $code = $idToCode[(int) $r->idioma_id] ?? 'es';
                    DB::table($table)->where('id', (int) $r->id)->update(['locale' => $code]);
                }
            }
        }

        // Drop new unique and FK (best-effort)
        try {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropUnique($table . '_template_idioma_unique');
            });
        } catch (Throwable $e) {
            // ignore
        }

        try {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropForeign($table . '_idioma_id_fk');
            });
        } catch (Throwable $e) {
            // ignore
        }

        if (Schema::hasColumn($table, 'idioma_id')) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('idioma_id');
            });
        }

        // Restore original uniqueness (best-effort)
        try {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->unique(['email_template_id', 'locale'], $table . '_template_locale_unique');
            });
        } catch (Throwable $e) {
            // ignore
        }
    }
};
