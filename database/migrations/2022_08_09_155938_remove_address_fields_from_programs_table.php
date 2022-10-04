<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveAddressFieldsFromProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('address');
            $table->dropColumn('address_ext');
            $table->dropColumn('city');
            $table->dropColumn('state');
            $table->dropColumn('country');
            $table->dropColumn('zip');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('address',45)->nullable(); 
			$table->string('address_ext',45)->nullable(); 
			$table->string('city',45)->nullable(); 
			$table->string('state',2)->nullable(); 
			$table->string('country',56)->nullable();
            $table->string('zip',45)->nullable(); 
        });
    }
}
