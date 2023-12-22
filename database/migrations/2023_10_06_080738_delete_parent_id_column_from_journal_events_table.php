<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DeleteParentIdColumnFromJournalEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('journal_events', function (Blueprint $table) {
            //Before deleting parent_id column we need to move all entries in this colum to "parent_journal_event_id" column
            $jes = \App\Models\JournalEvent::whereNotNull('parent_id')->get();
            if( $jes ) {
                foreach( $jes as $je ) {
                    $je->parent_journal_event_id = $je->parent_id;
                    $je->save();
                }
            }
            $table->dropColumn('parent_id');
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
            $table->unsignedBigInteger('parent_id')->nullable();
        });
    }
}
