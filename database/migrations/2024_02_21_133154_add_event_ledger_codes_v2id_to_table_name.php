<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEventLedgerCodesV2idToTableName extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_ledger_codes', function (Blueprint $table) {
            $table->integer('event_ledger_codes_v2id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_ledger_codes', function (Blueprint $table) {
            $table->dropColumn('event_ledger_codes_v2id');
        });
    }
}
