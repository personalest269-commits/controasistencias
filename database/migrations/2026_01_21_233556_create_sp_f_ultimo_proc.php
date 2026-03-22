<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared("CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_f_ultimo`(
    IN p_objeto VARCHAR(60),
    IN p_grupo1 VARCHAR(60),
    IN p_grupo2 VARCHAR(60),
    OUT p_new BIGINT
)
BEGIN
    DECLARE v_g1 VARCHAR(60);
    DECLARE v_g2 VARCHAR(60);

    SET v_g1 = IFNULL(NULLIF(TRIM(p_grupo1), ''), '__');
    SET v_g2 = IFNULL(NULLIF(TRIM(p_grupo2), ''), '______');

    INSERT INTO pg_control (objeto, grupo1, grupo2, ultimo)
    VALUES (p_objeto, v_g1, v_g2, LAST_INSERT_ID(1))
    ON DUPLICATE KEY UPDATE ultimo = LAST_INSERT_ID(ultimo + 1);

    SET p_new = LAST_INSERT_ID();
END");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared("DROP PROCEDURE IF EXISTS sp_f_ultimo");
    }
};
