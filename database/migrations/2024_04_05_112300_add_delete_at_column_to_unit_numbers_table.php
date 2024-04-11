<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeleteAtColumnToUnitNumbersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('unit_numbers', function (Blueprint $table) {
            $hasColumn = Schema::hasColumn('unit_numbers', 'deleted_at');
            if( !$hasColumn )   {
                $table->softDeletes('deleted_at');
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
        Schema::table('unit_numbers', function (Blueprint $table) {
            $hasColumn = Schema::hasColumn('unit_numbers', 'deleted_at');
            if( $hasColumn )   {
                $table->dropColumn('deleted_at');
            }
        });
    }
}
