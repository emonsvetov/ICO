<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangePrimeAccountHolderIdFieldInJournalEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('journal_events', function (Blueprint $table) {
            $table->unsignedBigInteger('prime_account_holder_id')->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('journal_events', function (Blueprint $table) {
            $table->integer('prime_account_holder_id')->default(NULL)->change();
        });
    }
}
