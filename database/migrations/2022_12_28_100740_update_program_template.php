<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateProgramTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('program_templates', function (Blueprint $table) {
            $table->string('button_color', 100)->default('#fff')->nullable()->change();
            $table->string('button_bg_color', 100)->default('red')->nullable()->change();
            $table->string('button_corner', 100)->default(0)->nullable()->change();
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
            $table->string('button_color', 100)->default('#fff')->change();
            $table->string('button_bg_color', 100)->default('red')->change();
            $table->string('button_corner', 100)->default(0)->change();
        });
    }
}
