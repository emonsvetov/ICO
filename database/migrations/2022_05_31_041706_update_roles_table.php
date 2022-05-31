<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;

class UpdateRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('is_program_role')->default(0);
        });
        foreach( Role::all() as $role ) {
            if($role->name == 'Program Admin')  {
                $role->name = 'Admin';
            }
            if($role->name == 'Program Manager')  {
                $role->name = 'Manager';
            }
            if( $role->name != 'Super Admin' && $role->name != 'Admin') {
                $role->is_program_role = 1;
            }
            $role->save();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('is_program_role');
        });
        foreach( Role::all() as $role ) {
            if($role->name == 'Admin')  {
                $role->name = 'Program Admin';
                $role->save();
            }
            if($role->name == 'Manager')  {
                $role->name = 'Program Manager';
                $role->save();
            }
        }
    }
}
