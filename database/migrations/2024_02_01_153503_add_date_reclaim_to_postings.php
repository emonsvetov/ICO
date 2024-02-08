<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDateReclaimToPostings extends Migration
{
    public function up()
    {
        Schema::table('postings', function (Blueprint $table) {
            $table->datetime('date_reclaim')->nullable()->default(null)->index();
        });
    }

    public function down()
    {
        Schema::table('postings', function (Blueprint $table) {
            $table->dropColumn('date_reclaim');
        });
    }
}
