<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS tr_pg_eventos_ai_crear_faltas_iniciales');

        DB::unprepared(<<<'SQL'
CREATE TRIGGER tr_pg_eventos_ai_crear_faltas_iniciales
AFTER INSERT ON pg_eventos
FOR EACH ROW
BEGIN
    DECLARE v_fecha DATE;
    DECLARE v_fecha_fin DATE;
    DECLARE v_evento_id VARCHAR(10);
    DECLARE v_persona_id VARCHAR(10);
    DECLARE v_valor BIGINT;
    DECLARE v_personas_json LONGTEXT;
    DECLARE v_departamentos_json LONGTEXT;
    DECLARE v_todos TINYINT(1) DEFAULT 0;
    DECLARE done INT DEFAULT 0;

    DECLARE v_row_id VARCHAR(10);
    DECLARE v_eventos LONGTEXT;
    DECLARE v_archivos LONGTEXT;
    DECLARE v_estados LONGTEXT;
    DECLARE v_observaciones LONGTEXT;

    DECLARE cur_personas CURSOR FOR
        SELECT p.id
          FROM pg_persona p
         WHERE (p.estado IS NULL OR p.estado <> 'X')
           AND (
                v_todos = 1
                OR JSON_CONTAINS(v_personas_json, JSON_QUOTE(p.id), '$')
                OR (
                    p.departamento_id IS NOT NULL
                    AND JSON_CONTAINS(v_departamentos_json, JSON_QUOTE(p.departamento_id), '$')
                )
           );

    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = 1;

    IF ((NEW.estado IS NULL OR NEW.estado <> 'X')
        AND NEW.fecha_inicio IS NOT NULL
        AND NEW.fecha_fin IS NOT NULL) THEN

        SET v_personas_json = COALESCE(NULLIF(TRIM(NEW.persona_id), ''), '[]');
        SET v_departamentos_json = COALESCE(NULLIF(TRIM(NEW.departamento_id), ''), '[]');

        IF JSON_VALID(v_personas_json) = 0 THEN
            SET v_personas_json = '[]';
        END IF;

        IF JSON_VALID(v_departamentos_json) = 0 THEN
            SET v_departamentos_json = '[]';
        END IF;

        IF v_personas_json = '[]' AND v_departamentos_json = '[]' THEN
            SET v_todos = 1;
        END IF;

        SET v_evento_id = NULLIF(TRIM(NEW.id), '');
        SET v_fecha = DATE(NEW.fecha_inicio);
        SET v_fecha_fin = DATE(NEW.fecha_fin);

        WHILE v_evento_id IS NOT NULL AND v_fecha <= v_fecha_fin DO
            SET done = 0;
            OPEN cur_personas;

            personas_loop: LOOP
                FETCH cur_personas INTO v_persona_id;
                IF done = 1 THEN
                    LEAVE personas_loop;
                END IF;

                SET v_row_id = (
                    SELECT pae.id
                      FROM pg_asistencia_evento pae
                     WHERE pae.persona_id = v_persona_id
                       AND pae.fecha = v_fecha
                       AND (pae.estado IS NULL OR pae.estado <> 'X')
                     ORDER BY pae.created_at ASC
                     LIMIT 1
                );

                IF v_row_id IS NULL THEN
                    CALL sp_f_ultimo('PG_ASISTENCIA_EVENTO', NULL, NULL, v_valor);

                    INSERT INTO pg_asistencia_evento (
                        id, evento_id, persona_id, fecha, id_archivo, asistencia_lote_id,
                        estado_asistencia, observacion, estado, created_at, updated_at
                    ) VALUES (
                        LPAD(v_valor, 10, '0'),
                        JSON_ARRAY(v_evento_id),
                        v_persona_id,
                        v_fecha,
                        JSON_ARRAY(''),
                        NULL,
                        JSON_ARRAY('F'),
                        JSON_ARRAY('Generado automáticamente al crear el evento'),
                        NULL,
                        NOW(),
                        NOW()
                    );
                ELSE
                    SELECT pae.evento_id, pae.id_archivo, pae.estado_asistencia, pae.observacion
                      INTO v_eventos, v_archivos, v_estados, v_observaciones
                      FROM pg_asistencia_evento pae
                     WHERE pae.id = v_row_id
                     LIMIT 1;

                    SET v_eventos = COALESCE(NULLIF(TRIM(v_eventos), ''), '[]');
                    SET v_archivos = COALESCE(NULLIF(TRIM(v_archivos), ''), '[]');
                    SET v_estados = COALESCE(NULLIF(TRIM(v_estados), ''), '[]');
                    SET v_observaciones = COALESCE(NULLIF(TRIM(v_observaciones), ''), '[]');

                    IF JSON_VALID(v_eventos) = 0 THEN SET v_eventos = '[]'; END IF;
                    IF JSON_VALID(v_archivos) = 0 THEN SET v_archivos = '[]'; END IF;
                    IF JSON_VALID(v_estados) = 0 THEN SET v_estados = '[]'; END IF;
                    IF JSON_VALID(v_observaciones) = 0 THEN SET v_observaciones = '[]'; END IF;

                    IF JSON_SEARCH(v_eventos, 'one', v_evento_id) IS NULL THEN
                        SET v_eventos = JSON_ARRAY_APPEND(v_eventos, '$', v_evento_id);
                        SET v_archivos = JSON_ARRAY_APPEND(v_archivos, '$', '');
                        SET v_estados = JSON_ARRAY_APPEND(v_estados, '$', 'F');
                        SET v_observaciones = JSON_ARRAY_APPEND(v_observaciones, '$', 'Generado automáticamente al crear el evento');

                        UPDATE pg_asistencia_evento
                           SET evento_id = v_eventos,
                               id_archivo = v_archivos,
                               estado_asistencia = v_estados,
                               observacion = v_observaciones,
                               estado = NULL,
                               updated_at = NOW()
                         WHERE id = v_row_id;
                    END IF;
                END IF;
            END LOOP;

            CLOSE cur_personas;
            SET v_fecha = DATE_ADD(v_fecha, INTERVAL 1 DAY);
        END WHILE;
    END IF;
END
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS tr_pg_eventos_ai_crear_faltas_iniciales');
    }
};
