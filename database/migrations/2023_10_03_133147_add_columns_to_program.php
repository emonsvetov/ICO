<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToProgram extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->boolean('send_balance_threshold_notification')->nullable();
            $table->integer('balance_threshold', false, true)->nullable();
            $table->string('low_balance_email')->nullable();
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
            $table->dropColumn(['send_balance_threshold_notification']);
            $table->dropColumn(['balance_threshold']);
            $table->dropColumn(['low_balance_email']);
        });
    }
}
