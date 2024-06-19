<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRestrictionsToJournalEvents extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('journal_events', function (Blueprint $table) {
            $table->mediumText('restrictions')->after('notes')->nullable();
        });

        Schema::table('event_xml_data', function (Blueprint $table) {
            $table->mediumText('restrictions')->after('notes')->nullable();
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
            $table->dropColumn(['restrictions']);
        });

        Schema::table('event_xml_data', function (Blueprint $table) {
            $table->dropColumn(['restrictions']);
        });
    }
}
