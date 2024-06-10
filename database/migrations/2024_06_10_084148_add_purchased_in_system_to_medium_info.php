<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPurchasedInSystemToMediumInfo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('medium_info', function (Blueprint $table) {
            $table->string('purchased_in_system')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('medium_info', function (Blueprint $table) {
            $table->dropColumn('purchased_in_system');
        });
    }
}
