<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToProgramTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('program_templates', function (Blueprint $table) {
            $table->string('hero_banner', 100)->nullable()->after('big_logo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('program_templates', function (Blueprint $table) {
            $table->dropColumn('hero_banner');
        });
    }
}
