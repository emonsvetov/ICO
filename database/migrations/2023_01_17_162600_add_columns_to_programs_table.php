<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->boolean('allow_award_peers_not_logged_into')->default(0);
            $table->boolean('allow_search_peers_not_logged_into')->default(0);
            $table->boolean('allow_view_leaderboards_not_logged_into')->default(0);
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
            $table->dropColumn('allow_award_peers_not_logged_into');
            $table->dropColumn('allow_search_peers_not_logged_into');
            $table->dropColumn('allow_view_leaderboards_not_logged_into');
        });
    }
}
