<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class UpdatePrimaryIndexWithOrgInModelHasRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::disableForeignKeyConstraints();
        // $columnNames = config('permission.column_names');
        // Schema::table('model_has_roles', function (Blueprint $table) use ($columnNames) {
        //     $table->dropPrimary([PermissionRegistrar::$pivotRole, $columnNames['model_morph_key'], 'model_type', 'program_id']);
        //     $table->primary([PermissionRegistrar::$pivotRole, $columnNames['model_morph_key'], 'model_type', 'program_id', 'organization_id'], 'model_has_roles_role_model_type_primary');
        // });
        // Schema::enableForeignKeyConstraints();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::disableForeignKeyConstraints();
        // $columnNames = config('permission.column_names');
        // Schema::table('model_has_roles', function (Blueprint $table) use ($columnNames) {
        //     $table->dropPrimary([PermissionRegistrar::$pivotRole, $columnNames['model_morph_key'], 'model_type', 'program_id', 'organization_id']);
        //     $table->primary([PermissionRegistrar::$pivotRole, $columnNames['model_morph_key'], 'model_type', 'program_id'], 'model_has_roles_role_model_type_primary');
        // });
        // Schema::enableForeignKeyConstraints();
    }
}
