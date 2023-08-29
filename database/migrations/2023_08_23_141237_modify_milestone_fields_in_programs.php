<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyMilestoneFieldsInPrograms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->renameColumn('milestone_award', 'allow_milestone_award');
            $table->dropColumn('milestone_award_frequency'); //mananged in events table
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->renameColumn('allow_milestone_award', 'milestone_award');
            $table->smallInteger('milestone_award_frequency')->nullable();
        });
    }
}
