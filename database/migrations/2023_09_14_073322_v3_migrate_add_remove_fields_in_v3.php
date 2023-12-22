<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class V3MigrateAddRemoveFieldsInV3 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if( !Schema::hasColumn('users', 'v2_parent_program_id') ) {
            Schema::table('users', function (Blueprint $table) {
                $table->bigInteger('v2_parent_program_id')->nullable();
            });
        }
        if( !Schema::hasColumn('users', 'v2_account_holder_id') ) {
            Schema::table('users', function (Blueprint $table) {
                $table->bigInteger('v2_account_holder_id')->nullable();
            });
        }
        if( !Schema::hasColumn('programs', 'v2_account_holder_id') ) {
            Schema::table('programs', function (Blueprint $table) {
                $table->bigInteger('v2_account_holder_id')->nullable();
            });
        }
        if( !Schema::hasColumn('events', 'v2_event_id') ) {
            Schema::table('events', function (Blueprint $table) {
                $table->bigInteger('v2_event_id')->nullable();
            });
        }
        if( !Schema::hasColumn('leaderboards', 'v2_leaderboard_id') ) {
            Schema::table('leaderboards', function (Blueprint $table) {
                $table->integer('v2_leaderboard_id')->nullable();
            });
        }
        if( !Schema::hasColumn('domains', 'v2_domain_id') ) {
            Schema::table('domains', function (Blueprint $table) {
                $table->integer('v2_domain_id')->nullable();
            });
        }
        if( !Schema::hasColumn('invoices', 'v2_invoice_id') ) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->integer('v2_invoice_id')->nullable();
            });
        }
        if( !Schema::hasColumn('merchants', 'v2_account_holder_id') ) {
            Schema::table('merchants', function (Blueprint $table) {
                $table->integer('v2_account_holder_id')->nullable();
            });
        }
        if( !Schema::hasColumn('medium_info', 'v2_medium_info_id') ) {
            Schema::table('medium_info', function (Blueprint $table) {
                $table->integer('v2_medium_info_id')->nullable();
            });
        }
        if( !Schema::hasColumn('journal_events', 'v2_journal_event_id') ) {
            Schema::table('journal_events', function (Blueprint $table) {
                $table->bigInteger('v2_journal_event_id')->nullable();
            });
        }
        if( !Schema::hasColumn('journal_events', 'v2_prime_account_holder_id') ) {
            Schema::table('journal_events', function (Blueprint $table) {
                $table->bigInteger('v2_prime_account_holder_id')->nullable();
            });
        }
        if( !Schema::hasColumn('journal_events', 'v2_parent_journal_event_id') ) {
            Schema::table('journal_events', function (Blueprint $table) {
                $table->bigInteger('v2_parent_journal_event_id')->nullable();
            });
        }
        if( !Schema::hasColumn('postings', 'v2_posting_id') ) {
            Schema::table('postings', function (Blueprint $table) {
                $table->bigInteger('v2_posting_id')->nullable();
            });
        }
        if( !Schema::hasColumn('accounts', 'v2_account_id') ) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->bigInteger('v2_account_id')->nullable();
            });
        }
        if( !Schema::hasColumn('physical_orders', 'v2_id') ) {
            Schema::table('physical_orders', function (Blueprint $table) {
                $table->bigInteger('v2_id')->nullable();
            });
        }
        if( !Schema::hasColumn('social_wall_posts', 'v2_id') ) {
            Schema::table('social_wall_posts', function (Blueprint $table) {
                $table->bigInteger('v2_id')->nullable();
            });
        }
        if( !Schema::hasColumn('event_xml_data', 'v2_id') ) {
            Schema::table('event_xml_data', function (Blueprint $table) {
                $table->bigInteger('v2_id')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //VERY RISKY!! Uncomment ONLY IF YOU KNOW what you are trying to do!!

        // if( Schema::hasColumn('users', 'v2_parent_program_id') ) {
        //     Schema::table('users', function (Blueprint $table) {
        //         $table->dropColumn('v2_parent_program_id');
        //     });
        // }
        // if( Schema::hasColumn('users', 'v2_account_holder_id') ) {
        //     Schema::table('users', function (Blueprint $table) {
        //         $table->dropColumn('v2_account_holder_id');
        //     });
        // }
        // if( Schema::hasColumn('programs', 'v2_account_holder_id') ) {
        //     Schema::table('programs', function (Blueprint $table) {
        //         $table->dropColumn('v2_account_holder_id');
        //     });
        // }
        // if( Schema::hasColumn('events', 'v2_event_id') ) {
        //     Schema::table('events', function (Blueprint $table) {
        //         $table->dropColumn('v2_event_id');
        //     });
        // }
        // if( Schema::hasColumn('leaderboards', 'v2_leaderboard_id') ) {
        //     Schema::table('leaderboards', function (Blueprint $table) {
        //         $table->dropColumn('v2_leaderboard_id');
        //     });
        // }
        // if( Schema::hasColumn('domains', 'v2_domain_id') ) {
        //     Schema::table('domains', function (Blueprint $table) {
        //         $table->dropColumn('v2_domain_id');
        //     });
        // }
        // if( Schema::hasColumn('invoices', 'v2_invoice_id') ) {
        //     Schema::table('invoices', function (Blueprint $table) {
        //         $table->dropColumn('v2_invoice_id');
        //     });
        // }
        // if( Schema::hasColumn('merchants', 'v2_account_holder_id') ) {
        //     Schema::table('merchants', function (Blueprint $table) {
        //         $table->dropColumn('v2_account_holder_id');
        //     });
        // }
        // if( Schema::hasColumn('medium_info', 'v2_medium_info_id') ) {
        //     Schema::table('medium_info', function (Blueprint $table) {
        //         $table->dropColumn('v2_medium_info_id');
        //     });
        // }
        // if( Schema::hasColumn('journal_events', 'v2_journal_event_id') ) {
        //     Schema::table('journal_events', function (Blueprint $table) {
        //         $table->dropColumn('v2_journal_event_id');
        //     });
        // }
        // if( Schema::hasColumn('journal_events', 'v2_prime_account_holder_id') ) {
        //     Schema::table('journal_events', function (Blueprint $table) {
        //         $table->dropColumn('v2_prime_account_holder_id');
        //     });
        // }
        // if( Schema::hasColumn('journal_events', 'v2_parent_journal_event_id') ) {
        //     Schema::table('journal_events', function (Blueprint $table) {
        //         $table->dropColumn('v2_parent_journal_event_id');
        //     });
        // }
        // if( Schema::hasColumn('postings', 'v2_posting_id') ) {
        //     Schema::table('postings', function (Blueprint $table) {
        //         $table->dropColumn('v2_posting_id');
        //     });
        // }
        // if( Schema::hasColumn('accounts', 'v2_account_id') ) {
        //     Schema::table('accounts', function (Blueprint $table) {
        //         $table->dropColumn('v2_account_id');
        //     });
        // }
        // if( Schema::hasColumn('physical_orders', 'v2_id') ) {
        //     Schema::table('physical_orders', function (Blueprint $table) {
        //         $table->dropColumn('v2_id');
        //     });
        // }
        // if( Schema::hasColumn('social_wall_posts', 'v2_id') ) {
        //     Schema::table('social_wall_posts', function (Blueprint $table) {
        //         $table->dropColumn('v2_id');
        //     });
        // }
        // if( Schema::hasColumn('event_xml_data', 'v2_id') ) {
        //     Schema::table('event_xml_data', function (Blueprint $table) {
        //         $table->dropColumn('v2_id');
        //     });
        // }
    }
}
