<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ChangeColumnInModelHasRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::table('model_has_roles', function (Blueprint $table) {
        //     $table->unsignedBigInteger('program_id')->nullable()->default(NULL)->change();
        // });
        // DB::statement('UPDATE `model_has_roles` SET `program_id`=NULL WHERE `program_id`=0');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('model_has_roles', function (Blueprint $table) {
        //     $table->unsignedBigInteger('program_id')->nullable(false)->default(0)->change();
        // });
        // DB::statement('UPDATE `model_has_roles` SET `program_id`=0 WHERE `program_id` IS NULL');
    }
}
