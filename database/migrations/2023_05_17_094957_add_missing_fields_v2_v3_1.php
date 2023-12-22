<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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
            $hasColumn = Schema::hasColumn('users', 'v2_parent_program_id');
            if( !$hasColumn ) {
                $table->bigInteger('v2_parent_program_id')->nullable();
            }
            $hasColumn = Schema::hasColumn('users', 'v2_account_holder_id');
            if( !$hasColumn ) {
                $table->bigInteger('v2_account_holder_id')->nullable();
            }
            $hasColumn = Schema::hasColumn('users', 'previous_user_status_id');
            if( !$hasColumn ) {
                $table->tinyInteger('previous_user_status_id')->default(1);
            }
            $hasColumn = Schema::hasColumn('users', 'hire_date');
            if( !$hasColumn ) {
                $table->date('hire_date')->nullable();
            }
        });
        Schema::table('programs', function (Blueprint $table) {
            $hasColumn = Schema::hasColumn('programs', 'v2_account_holder_id');
            if( !$hasColumn ) {
                $table->bigInteger('v2_account_holder_id')->nullable(); //This is account holder id in v2
            }
        });
        Schema::table('events', function (Blueprint $table) {
            $hasColumn = Schema::hasColumn('events', 'v2_event_id');
            if( !$hasColumn ) {
                $table->integer('v2_event_id')->nullable();
            }
            $hasColumn = Schema::hasColumn('events', 'icon');
            if( !$hasColumn ) {
                $table->string('icon', 64)->nullable();
            }
            $hasColumn = Schema::hasColumn('events', 'is_team_award');
            if( !$hasColumn ) {
                $table->boolean('is_team_award')->default(0);
            }
            $hasColumn = Schema::hasColumn('events', 'is_anniversary_award');
            if( $hasColumn ) {
                $table->renameColumn('is_anniversary_award', 'is_work_anniversary_award');
            }
        });
        Schema::table('leaderboards', function (Blueprint $table) {
            $hasColumn = Schema::hasColumn('leaderboards', 'v2_leaderboard_id');
            if( !$hasColumn ) {
                $table->integer('v2_leaderboard_id')->nullable();
            }
        });
        Schema::table('domains', function (Blueprint $table) {
            $hasColumn = Schema::hasColumn('domains', 'v2_domain_id');
            if( !$hasColumn ) {
                $table->integer('v2_domain_id')->nullable();
            }
        });
        Schema::table('invoices', function (Blueprint $table) {
            $hasColumn = Schema::hasColumn('invoices', 'v2_invoice_id');
            if( !$hasColumn ) {
                $table->integer('v2_invoice_id')->nullable();
            }
        });
        Schema::table('merchants', function (Blueprint $table) {
            $hasColumn = Schema::hasColumn('merchants', 'v2_account_holder_id');
            if( !$hasColumn ) {
                $table->integer('v2_account_holder_id')->nullable();
            }
            $hasColumn = Schema::hasColumn('merchants', 'logo');
            if( !$hasColumn ) {
                $table->string('logo')->nullable();
            }   else {
                $table->string('logo')->nullable()->change();
            }
            $hasColumn = Schema::hasColumn('merchants', 'icon');
            if( !$hasColumn ) {
                $table->string('icon')->nullable();
            }   else {
                $table->string('icon')->nullable()->change();
            }
        });

        /**************** v2 updates **************/
        Schema::connection('v2')->table('users', function($table) {
            if (!Schema::connection('v2')->hasColumn('users', 'v3_user_id'))    {
                $table->bigInteger('v3_user_id')->nullable();
            }
            if (!Schema::connection('v2')->hasColumn('users', 'v3_organization_id'))    {
                $table->bigInteger('v3_organization_id')->nullable();
            }
        });
        Schema::connection('v2')->table('programs', function($table) {
            if (!Schema::connection('v2')->hasColumn('programs', 'v3_program_id'))    {
                $table->bigInteger('v3_program_id')->nullable();
            }
            if (!Schema::connection('v2')->hasColumn('programs', 'v3_organization_id'))    {
                $table->bigInteger('v3_organization_id')->nullable();
            }
        });
        Schema::connection('v2')->table('event_templates', function($table) {
            if (!Schema::connection('v2')->hasColumn('event_templates', 'v3_event_id'))    {
                $table->integer('v3_event_id')->nullable();
            }
        });
        Schema::connection('v2')->table('leaderboards', function($table) {
            if (!Schema::connection('v2')->hasColumn('leaderboards', 'v3_leaderboard_id'))    {
                $table->integer('v3_leaderboard_id')->nullable();
            }
        });
        Schema::connection('v2')->table('domains', function($table) {
            if (!Schema::connection('v2')->hasColumn('domains', 'v3_domain_id'))    {
                $table->integer('v3_domain_id')->nullable();
            }
        });
        Schema::connection('v2')->table('invoices', function($table) {
            if (!Schema::connection('v2')->hasColumn('invoices', 'v3_invoice_id'))    {
                $table->integer('v3_invoice_id')->nullable();
            }
        });
        Schema::connection('v2')->table('merchants', function($table) {
            if (!Schema::connection('v2')->hasColumn('merchants', 'v3_merchant_id'))    {
                $table->integer('v3_merchant_id')->nullable();
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
        // It is VERY RISKY to drop columns!!!
        // Run it manually, see bottom of this page
        // /**************** V3 updates **************/
        // Schema::table('users', function (Blueprint $table) {
        //     $table->dropColumn('v2_parent_program_id');
        //     $table->dropColumn('v2_account_holder_id');
        //     $table->dropColumn('previous_user_status_id');
        //     $table->dropColumn('hire_date');
        // });
        // Schema::table('programs', function (Blueprint $table) {
        //     $table->dropColumn('v2_account_holder_id'); //This is account holder id in v2
        // });

        // //Events
        // Schema::table('events', function (Blueprint $table) {
        //     $table->dropColumn('v2_event_id');
        //     $table->dropColumn('icon');
        //     $table->dropColumn('is_team_award');
        //     $table->renameColumn('is_work_anniversary_award', 'is_anniversary_award');
        // });
        // Schema::table('leaderboards', function (Blueprint $table) {
        //     $table->dropColumn('v2_leaderboard_id');
        // });
        // Schema::table('domains', function (Blueprint $table) {
        //     $table->dropColumn('v2_domain_id');
        // });
        // Schema::table('invoices', function (Blueprint $table) {
        //     $table->dropColumn('v2_invoice_id');
        // });
        // Schema::table('merchants', function (Blueprint $table) {
        //     $table->integer('v2_merchant_id')->nullable()->default(0);
        //     $table->dropColumn('v2_account_holder_id');
        //     $table->string('logo')->nullable(false)->change();
        //     $table->string('icon')->nullable(false)->change();
        // });

        // /**************** V2 updates **************/

        // Schema::connection('v2')->table('users', function($table) {
        //     $table->dropColumn('v3_user_id');
        //     $table->dropColumn('v3_organization_id');
        // });
        // Schema::connection('v2')->table('programs', function($table) {
        //     $table->dropColumn('v3_program_id');
        //     $table->dropColumn('v3_organization_id');
        // });
        // Schema::connection('v2')->table('event_templates', function($table) {
        //     $table->dropColumn('v3_event_id');
        // });
        // Schema::connection('v2')->table('leaderboards', function($table) {
        //     $table->dropColumn('v3_leaderboard_id');
        // });
        // Schema::connection('v2')->table('domains', function($table) {
        //     $table->dropColumn('v3_domain_id');
        // });
        // Schema::connection('v2')->table('invoices', function($table) {
        //     $table->dropColumn('v3_invoice_id');
        // });
        // Schema::connection('v2')->table('merchants', function($table) {
        //     $table->dropColumn('v3_merchant_id');
        // });
    }
}

/***
-- V3 Updaets --
ALTER TABLE `users`
  DROP `v2_parent_program_id`,
  DROP `v2_account_holder_id`,
  DROP `previous_user_status_id`,
  DROP `hire_date`;
ALTER TABLE `programs`
  DROP `v2_account_holder_id`;
ALTER TABLE `events`
  DROP `v2_event_id`,
  DROP `icon`,
  DROP `is_team_award`;
ALTER TABLE `events` CHANGE `is_work_anniversary_award` `is_anniversary_award` TINYINT(1) NOT NULL DEFAULT '0';
ALTER TABLE `leaderboards`
  DROP `v2_leaderboard_id`;
ALTER TABLE `domains`
  DROP `v2_domain_id`;
ALTER TABLE `invoices`
  DROP `v2_invoice_id`;
ALTER TABLE `merchants`
  DROP `v2_account_holder_id`;

-- V2 Updaets --
ALTER TABLE `users`
  DROP `v3_user_id`,
  DROP `v3_organization_id`;
ALTER TABLE `programs`
  DROP `v3_program_id`,
  DROP `v3_organization_id`;
ALTER TABLE `event_templates`
  DROP `v3_event_id`;
ALTER TABLE `leaderboards`
  DROP `v3_leaderboard_id`;
ALTER TABLE `domains`
  DROP `v3_domain_id`;
ALTER TABLE `invoices`
  DROP `v3_invoice_id`;
ALTER TABLE `merchants`
  DROP `v3_merchant_id`;
 */
