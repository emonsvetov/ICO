<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRoles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if( !DB::table('roles')->where('name', 'LIKE', 'Limited Manager')->first() )  {
            DB::table('roles')->insert(
                [
                    'name' => 'Limited Manager',
                    'guard_name' => 'api',
                    'is_program_role' => 1,
                    'is_backend_role' => 0,
                ],
            );
        }
        if( !DB::table('roles')->where('name', 'LIKE', 'Read Only Manager')->first() )  {
            DB::table('roles')->insert(
                [
                    'name' => 'Read Only Manager',
                    'guard_name' => 'api',
                    'is_program_role' => 1,
                    'is_backend_role' => 0,
                ],
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
