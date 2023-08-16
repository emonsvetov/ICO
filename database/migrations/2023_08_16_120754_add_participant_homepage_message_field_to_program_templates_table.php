<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParticipantHomepageMessageFieldToProgramTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('program_templates', function (Blueprint $table) {
            $table->text('participant_homepage_message')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('program_templates', function (Blueprint $table) {
            $table->dropColumn('participant_homepage_message');
        });
    }
}
