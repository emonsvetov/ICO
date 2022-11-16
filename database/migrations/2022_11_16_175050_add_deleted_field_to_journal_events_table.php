<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedFieldToJournalEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('journal_events', function (Blueprint $table) {
            if(!Schema::hasColumn('journal_events', 'deleted_at'))  {
                $table->timestamp('deleted_at')->nullable();
            }
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
            if(Schema::hasColumn('journal_events', 'deleted_at'))  {
                $table->dropColumn('deleted_at');
            }
        });
    }
}
