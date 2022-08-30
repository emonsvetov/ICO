<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedFieldToTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            if(!Schema::hasColumn('events', 'deleted_at'))  {
                $table->timestamp('deleted_at')->nullable();
            }
        });
        Schema::table('invoices', function (Blueprint $table) {
            if(!Schema::hasColumn('invoices', 'deleted_at'))  {
                $table->timestamp('deleted_at')->nullable();
            }
        });
        Schema::table('users', function (Blueprint $table) {
            if(!Schema::hasColumn('users', 'deleted_at'))  {
                $table->timestamp('deleted_at')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
