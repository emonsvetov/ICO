<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class V2V3MigrationFieldsPostingsJe extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**************** v3 updates **************/
        Schema::table('journal_events', function (Blueprint $table) {
            if( !Schema::hasColumn('journal_events', 'v2_journal_event_id')) {
                $table->bigInteger('v2_journal_event_id')->nullable();
            }
            if( !Schema::hasColumn('journal_events', 'v2_prime_account_holder_id')) {
                $table->bigInteger('v2_prime_account_holder_id')->nullable();
            }
            if( !Schema::hasColumn('journal_events', 'v2_parent_journal_event_id')) {
                $table->bigInteger('v2_parent_journal_event_id')->nullable();
            }
        });
        Schema::table('postings', function (Blueprint $table) {
            if( !Schema::hasColumn('postings', 'v2_posting_id')) {
                $table->bigInteger('v2_posting_id')->nullable();
            }
        });
        Schema::table('accounts', function (Blueprint $table) {
            if( !Schema::hasColumn('accounts', 'v2_account_id')) {
                $table->bigInteger('v2_account_id')->nullable();
            }
        });

        /**************** v2 updates **************/
        Schema::connection('v2')->table('journal_events', function($table) {
            if (!Schema::connection('v2')->hasColumn('journal_events', 'v3_journal_event_id'))    {
                $table->bigInteger('v3_journal_event_id')->nullable();
            }
        });
        Schema::connection('v2')->table('postings', function($table) {
            if (!Schema::connection('v2')->hasColumn('postings', 'v3_posting_id'))    {
                $table->bigInteger('v3_posting_id')->nullable();
            }
        });
        Schema::connection('v2')->table('accounts', function($table) {
            if (!Schema::connection('v2')->hasColumn('accounts', 'v3_account_id'))    {
                $table->bigInteger('v3_account_id')->nullable();
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
        // /**************** v3 updates **************/
        // Schema::table('journal_events', function (Blueprint $table) {
        //     $table->dropColumn('v2_journal_event_id');
        //     $table->dropColumn('v2_prime_account_holder_id');
        //     $table->dropColumn('v2_parent_journal_event_id');
        // });
        // Schema::table('postings', function (Blueprint $table) {
        //     $table->dropColumn('v2_posting_id');
        // });
        // Schema::table('accounts', function (Blueprint $table) {
        //     $table->dropColumn('v2_account_id');
        // });

        // /**************** v2 updates **************/
        // Schema::connection('v2')->table('journal_events', function($table) {
        //     $table->dropColumn('v3_journal_event_id');
        // });
        // Schema::connection('v2')->table('postings', function($table) {
        //     $table->dropColumn('v3_posting_id');
        // });
        // Schema::connection('v2')->table('accounts', function($table) {
        //     $table->dropColumn('v3_account_id');
        // });
    }
}
