<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToReferralsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->unsignedTinyInteger('more_info')->after('sender_id')->default(0);
            $table->unsignedTinyInteger('category_referral')->after('more_info')->default(0);
            $table->unsignedTinyInteger('category_feedback')->after('category_referral')->default(0);
            $table->unsignedTinyInteger('category_lead')->after('category_feedback')->default(0);
            $table->unsignedTinyInteger('category_reward')->after('category_lead')->default(0);
            $table->float('reward_amount')->after('category_reward')->default(0.0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropColumn('more_info');
            $table->dropColumn('category_referral');
            $table->dropColumn('category_feedback');
            $table->dropColumn('category_lead');
            $table->dropColumn('category_reward');
            $table->dropColumn('reward_amount');
        });
    }
}
