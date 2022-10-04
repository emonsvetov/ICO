<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddButtonColumnsToProgramTemplates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('program_templates', function (Blueprint $table) {
            $table->string('button_color', 100)->default('#fff');
            $table->string('button_bg_color', 100)->default('red');
            $table->string('button_corner', 100)->default(0);
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
            $table->dropColumn('button_color');
            $table->dropColumn('button_bg_color');
            $table->dropColumn('button_corner');
        });
    }
}
