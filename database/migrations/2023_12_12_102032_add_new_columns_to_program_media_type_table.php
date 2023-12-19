<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNewColumnsToProgramMediaTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('program_media_type', function (Blueprint $table) {
            $table->integer('is_menu_item')->default(0)->nullable();
            $table->string('menu_link')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('program_media_type', function (Blueprint $table) {
            $table->dropColumn('is_menu_item');
            $table->dropColumn('menu_link');
        });
    }
}
