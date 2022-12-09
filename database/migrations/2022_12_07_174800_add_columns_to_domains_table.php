<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToDomainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->string('host', 255)->after('name')->nullable();
            $table->string('scheme', 6)->after('host')->default('http')->nullable();
            $table->smallInteger('port')->after('scheme')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('domains', function (Blueprint $table) {
            $table->dropColumn('host');
            $table->dropColumn('scheme');
            $table->dropColumn('port');
        });
    }
}
