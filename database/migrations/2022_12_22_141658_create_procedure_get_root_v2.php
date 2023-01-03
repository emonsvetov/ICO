<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateProcedureGetRootV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS `getProgramRoot`');
        DB::unprepared('DROP FUNCTION IF EXISTS `getProgramRoot`');
        DB::unprepared(
            '
             CREATE PROCEDURE `getProgramRoot`(
                    IN `uid` BIGINT,
                    OUT `resultId` BIGINT
                ) NOT DETERMINISTIC CONTAINS SQL
                BEGIN
                    WITH RECURSIVE tree AS (
                    SELECT id, name, parent_id, 0 AS level, CONCAT(id, "", "") AS path FROM programs WHERE parent_id IS NULL
                    UNION ALL
                    SELECT child.id, child.name, child.parent_id, level+1, CONCAT(tree.path, ",", child.id)
                    FROM programs child JOIN tree ON tree.id = child.parent_id
                    )
                    SELECT SUBSTRING_INDEX(path, ",", 1) as root_id FROM tree WHERE id = uid INTO resultId;
                END
        ');
        DB::unprepared(
            "
            CREATE FUNCTION `getProgramRoot`(`uid` BIGINT)
            RETURNS INT DETERMINISTIC CONTAINS SQL
            BEGIN
                DECLARE resultId INTEGER;
                CALL getProgramRoot(uid, resultId);
                RETURN resultId;
            END
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::unprepared('DROP PROCEDURE IF EXISTS `getProgramRoot`');
        DB::unprepared('DROP FUNCTION IF EXISTS `getProgramRoot`');
    }
}
