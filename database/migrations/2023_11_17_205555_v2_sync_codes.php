<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class V2SyncCodes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 0 - sync is not required
        // 1 - sync is required
        // 2 - sync is in progress
        // 3 - sync is in error state
        // 5 - sync is successfully done

        Schema::table('medium_info', function (Blueprint $table) {
            $table->tinyInteger('v2_sync_status')
                ->nullable(false)
                ->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('medium_info', function (Blueprint $table) {
            $table->dropColumn('v2_sync_status');
        });
    }
}
