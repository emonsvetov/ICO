<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use DB;

class UpdatePrimaryIndexInModelHasRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::disableForeignKeyConstraints();
        $columnNames = config('permission.column_names');
        Schema::table('model_has_roles', function (Blueprint $table) use ($columnNames) {
            $table->dropForeign('model_has_roles_role_id_foreign');
            $table->dropPrimary([PermissionRegistrar::$pivotRole, $columnNames['model_morph_key'], 'model_type']);
            // DB::unprepared('ALTER TABLE `model_has_roles` DROP PRIMARY KEY;');
            $table->primary([PermissionRegistrar::$pivotRole, $columnNames['model_morph_key'], 'model_type', 'program_id'], 'model_has_roles_role_model_type_program_primary');
            DB::statement('ALTER TABLE `model_has_roles` ADD CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE');
        });
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::disableForeignKeyConstraints();
        $columnNames = config('permission.column_names');
        Schema::table('model_has_roles', function (Blueprint $table) use ($columnNames) {
            $table->dropPrimary([PermissionRegistrar::$pivotRole, $columnNames['model_morph_key'], 'model_type', 'program_id']);
            // DB::unprepared('ALTER TABLE `model_has_roles` DROP PRIMARY KEY;');
            $table->primary([PermissionRegistrar::$pivotRole, $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
        });
        Schema::enableForeignKeyConstraints();
    }
}
