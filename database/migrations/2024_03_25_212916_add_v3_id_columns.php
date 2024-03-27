<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddV3IdColumns extends Migration
{
    public function up()
    {
        /**************** v2 updates **************/
        Schema::connection('v2')->table('program_budget', function($table) {
            if (!Schema::connection('v2')->hasColumn('program_budget', 'v3_id'))    {
                $table->bigInteger('v3_id')->nullable();
            }
        });

        Schema::table('program_budget', function (Blueprint $table) {
            if( !Schema::hasColumn('program_budget', 'v2_id')) {
                $table->dropForeign('program_budget_month_id_foreign');
                $table->renameColumn('month_id', 'month');
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
        /**************** v2 updates **************/
        Schema::connection('v2')->table('program_budget', function($table) {
            $table->dropColumn('v3_id');
        });

        Schema::table('program_budget', function (Blueprint $table) {
            if( !Schema::hasColumn('program_budget', 'v2_id')) {
                $table->renameColumn('month', 'month_id');
            }
        });
    }
}
