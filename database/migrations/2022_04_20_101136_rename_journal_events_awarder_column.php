<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameJournalEventsAwarderColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('journal_events', function (Blueprint $table) {
            $table->renameColumn('user_id', 'awarder_id');
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
            $table->renameColumn('awarder_id', 'user_id');
        });
    }
}
