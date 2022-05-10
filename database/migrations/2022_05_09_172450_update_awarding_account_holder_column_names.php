<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateAwardingAccountHolderColumnNames extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('event_xml_data', function (Blueprint $table) {
            $table->renameColumn('awarder_id', 'awarder_account_holder_id');
        });
        Schema::table('journal_events', function (Blueprint $table) {
            $table->renameColumn('awarder_id', 'prime_account_holder_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('event_xml_data', function (Blueprint $table) {
            $table->renameColumn('awarder_account_holder_id', 'awarder_id');
        });
        Schema::table('journal_events', function (Blueprint $table) {
            $table->renameColumn('prime_account_holder_id', 'awarder_id');
        });
    }
}
