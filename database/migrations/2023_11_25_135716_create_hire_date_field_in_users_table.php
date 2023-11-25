<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHireDateFieldInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasColumn('users', 'hire_date'))
        {
            Schema::table('users', function (Blueprint $table)
            {
                $table->date('hire_date')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('users', 'hire_date'))
        {
            Schema::table('users', function (Blueprint $table)
            {
                $table->dropColumn('hire_date');
            });
        }
    }
}
