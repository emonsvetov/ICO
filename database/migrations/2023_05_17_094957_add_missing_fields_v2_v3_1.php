<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingFieldsV2V31 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**************** v3 updates **************/
        Schema::table('users', function (Blueprint $table) {
            $table->bigInteger('v2_parent_program_id')->nullable();
            $table->bigInteger('v2_account_holder_id')->nullable();
            $table->tinyInteger('previous_user_status_id')->default(1);
            $table->date('hire_date')->nullable();
        });
        Schema::table('programs', function (Blueprint $table) {
            $table->bigInteger('v2_account_holder_id')->nullable(); //This is account holder id in v2
        });
        Schema::table('events', function (Blueprint $table) {
            $table->integer('v2_event_id')->nullable();
            $table->string('icon', 64)->nullable();
            $table->boolean('is_team_award')->default(0);
            $table->renameColumn('is_anniversary_award', 'is_work_anniversary_award');
        });
        Schema::table('leaderboards', function (Blueprint $table) {
            $table->integer('v2_leaderboard_id')->nullable();
        });
        Schema::table('domains', function (Blueprint $table) {
            $table->integer('v2_domain_id')->nullable();
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->integer('v2_invoice_id')->nullable();
        });
        Schema::table('merchants', function (Blueprint $table) {
            $table->integer('v2_account_holder_id')->nullable();
        });

        /**************** v2 updates **************/
        Schema::connection('v2')->table('users', function($table) {
            $table->bigInteger('v3_user_id')->nullable();
            $table->bigInteger('v3_organization_id')->nullable();
        });
        Schema::connection('v2')->table('programs', function($table) {
            $table->bigInteger('v3_program_id')->nullable();
            $table->bigInteger('v3_organization_id')->nullable();
        });
        Schema::connection('v2')->table('event_templates', function($table) {
            $table->integer('v3_event_id')->nullable();
        });
        Schema::connection('v2')->table('leaderboards', function($table) {
            $table->integer('v3_leaderboard_id')->nullable();
        });
        Schema::connection('v2')->table('domains', function($table) {
            $table->integer('v3_domain_id')->nullable();
        });
        Schema::connection('v2')->table('invoices', function($table) {
            $table->integer('v3_invoice_id')->nullable();
        });
        Schema::connection('v2')->table('merchants', function($table) {
            $table->integer('v3_merchant_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /**************** V3 updates **************/

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('v2_parent_program_id');
            $table->dropColumn('v2_account_holder_id');
            $table->dropColumn('previous_user_status_id');
            $table->dropColumn('hire_date');
        });
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('v2_account_holder_id'); //This is account holder id in v2
        });

        //Events
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('v2_event_id');
            $table->dropColumn('icon');
            $table->dropColumn('is_team_award');
            $table->renameColumn('is_work_anniversary_award', 'is_anniversary_award');
        });
        Schema::table('leaderboards', function (Blueprint $table) {
            $table->dropColumn('v2_leaderboard_id');
        });
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('v2_domain_id');
        });
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('v2_invoice_id');
        });
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('v2_account_holder_id');
        });

        /**************** V2 updates **************/

        Schema::connection('v2')->table('users', function($table) {
            $table->dropColumn('v3_user_id');
            $table->dropColumn('v3_organization_id');
        });
        Schema::connection('v2')->table('programs', function($table) {
            $table->dropColumn('v3_program_id');
            $table->dropColumn('v3_organization_id');
        });
        Schema::connection('v2')->table('event_templates', function($table) {
            $table->dropColumn('v3_event_id');
        });
        Schema::connection('v2')->table('leaderboards', function($table) {
            $table->dropColumn('v3_leaderboard_id');
        });
        Schema::connection('v2')->table('domains', function($table) {
            $table->dropColumn('v3_domain_id');
        });
        Schema::connection('v2')->table('invoices', function($table) {
            $table->dropColumn('v3_invoice_id');
        });
        Schema::connection('v2')->table('merchants', function($table) {
            $table->dropColumn('v3_merchant_id');
        });
    }
}
