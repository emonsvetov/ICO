<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaderboardJournalEventTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaderboard_journal_event', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leaderboard_id');
            $table->unsignedBigInteger('journal_event_id');
            $table->timestamps();

            $table->index('leaderboard_id');
            $table->index('journal_event_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leaderboard_journal_event');
    }
}
