<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class V3MigrateAddRemoveFieldsInV2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::connection('v2')->hasColumn('users', 'v3_user_id'))    {
            Schema::connection('v2')->table('users', function($table) {
                $table->bigInteger('v3_user_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('users', 'v3_organization_id'))    {
            Schema::connection('v2')->table('users', function($table) {
                $table->bigInteger('v3_organization_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('programs', 'v3_program_id'))    {
            Schema::connection('v2')->table('programs', function($table) {
                $table->bigInteger('v3_program_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('programs', 'v3_organization_id'))    {
            Schema::connection('v2')->table('programs', function($table) {
                $table->bigInteger('v3_organization_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('event_templates', 'v3_event_id'))    {
            Schema::connection('v2')->table('event_templates', function($table) {
                $table->integer('v3_event_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('leaderboards', 'v3_leaderboard_id'))    {
            Schema::connection('v2')->table('leaderboards', function($table) {
                $table->integer('v3_leaderboard_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('domains', 'v3_domain_id'))    {
            Schema::connection('v2')->table('domains', function($table) {
                $table->integer('v3_domain_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('invoices', 'v3_invoice_id'))    {
            Schema::connection('v2')->table('invoices', function($table) {
                $table->integer('v3_invoice_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('merchants', 'v3_merchant_id'))    {
            Schema::connection('v2')->table('merchants', function($table) {
                $table->integer('v3_merchant_id')->nullable();
                $table->string('logo')->nullable()->default(null)->change();
                $table->string('icon')->nullable()->default(null)->change();
            });
        }
        if (!Schema::connection('v2')->hasColumn('medium_info', 'purchased_by_v3'))    {
            Schema::connection('v2')->table('medium_info', function($table) {
                $table->boolean('purchased_by_v3')->default(0);
            });
        }
        if (!Schema::connection('v2')->hasColumn('medium_info', 'v3_medium_info_id'))    {
            Schema::connection('v2')->table('medium_info', function($table) {
                $table->integer('v3_medium_info_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('journal_events', 'v3_journal_event_id'))    {
            Schema::connection('v2')->table('journal_events', function($table) {
                $table->bigInteger('v3_journal_event_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('postings', 'v3_posting_id'))    {
            Schema::connection('v2')->table('postings', function($table) {
                $table->bigInteger('v3_posting_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('accounts', 'v3_account_id'))    {
            Schema::connection('v2')->table('accounts', function($table) {
                $table->bigInteger('v3_account_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('physical_orders', 'v3_id'))    {
            Schema::connection('v2')->table('physical_orders', function($table) {
                $table->bigInteger('v3_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('social_wall_posts', 'v3_id'))    {
            Schema::connection('v2')->table('social_wall_posts', function($table) {
                $table->bigInteger('v3_id')->nullable();
            });
        }
        if (!Schema::connection('v2')->hasColumn('event_xml_data', 'v3_id'))    {
            Schema::connection('v2')->table('event_xml_data', function($table) {
                $table->bigInteger('v3_id')->nullable();
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

        // if (Schema::connection('v2')->hasColumn('users', 'v3_user_id'))    {
        //     Schema::connection('v2')->table('users', function($table) {
        //         $table->dropColumn('v3_user_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('users', 'v3_organization_id'))    {
        //     Schema::connection('v2')->table('users', function($table) {
        //         $table->dropColumn('v3_organization_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('programs', 'v3_program_id'))    {
        //     Schema::connection('v2')->table('programs', function($table) {
        //         $table->dropColumn('v3_program_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('programs', 'v3_organization_id'))    {
        //     Schema::connection('v2')->table('programs', function($table) {
        //         $table->dropColumn('v3_organization_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('event_templates', 'v3_event_id'))    {
        //     Schema::connection('v2')->table('event_templates', function($table) {
        //         $table->dropColumn('v3_event_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('leaderboards', 'v3_leaderboard_id'))    {
        //     Schema::connection('v2')->table('leaderboards', function($table) {
        //         $table->dropColumn('v3_leaderboard_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('domains', 'v3_domain_id'))    {
        //     Schema::connection('v2')->table('domains', function($table) {
        //         $table->dropColumn('v3_domain_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('invoices', 'v3_invoice_id'))    {
        //     Schema::connection('v2')->table('invoices', function($table) {
        //         $table->dropColumn('v3_invoice_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('merchants', 'v3_merchant_id'))    {
        //     Schema::connection('v2')->table('merchants', function($table) {
        //         $table->dropColumn('v3_merchant_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('medium_info', 'purchased_by_v3'))    {
        //     Schema::connection('v2')->table('medium_info', function($table) {
        //         $table->dropColumn('purchased_by_v3');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('medium_info', 'v3_medium_info_id'))    {
        //     Schema::connection('v2')->table('medium_info', function($table) {
        //         $table->dropColumn('v3_medium_info_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('journal_events', 'v3_journal_event_id'))    {
        //     Schema::connection('v2')->table('journal_events', function($table) {
        //         $table->dropColumn('v3_journal_event_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('postings', 'v3_posting_id'))    {
        //     Schema::connection('v2')->table('postings', function($table) {
        //         $table->dropColumn('v3_posting_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('accounts', 'v3_account_id'))    {
        //     Schema::connection('v2')->table('accounts', function($table) {
        //         $table->dropColumn('v3_account_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('physical_orders', 'v3_id'))    {
        //     Schema::connection('v2')->table('physical_orders', function($table) {
        //         $table->dropColumn('v3_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('social_wall_posts', 'v3_id'))    {
        //     Schema::connection('v2')->table('social_wall_posts', function($table) {
        //         $table->dropColumn('v3_id');
        //     });
        // }
        // if (Schema::connection('v2')->hasColumn('event_xml_data', 'v3_id'))    {
        //     Schema::connection('v2')->table('event_xml_data', function($table) {
        //         $table->dropColumn('v3_id');
        //     });
        // }
    }
}
