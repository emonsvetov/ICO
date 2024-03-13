<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIndexToJournalEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('journal_events', function (Blueprint $table) {
            $table->index(['v2_journal_event_id']);
            $table->index(['prime_account_holder_id']);
            $table->index(['event_xml_data_id']);
            $table->index(['parent_journal_event_id']);
        });
        Schema::table('event_xml_data', function (Blueprint $table) {
            $table->index(['v2_id']);
            $table->index(['name']);
            $table->index(['amount_override']);
        });
        Schema::table('postings', function (Blueprint $table) {
            $table->index(['v2_posting_id']);
            $table->index(['posting_amount']);
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
