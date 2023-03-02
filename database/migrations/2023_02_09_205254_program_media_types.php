<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProgramMediaTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_media_type', function (Blueprint $table) {
            $table->id('program_media_type_id');
            $table->string('name', 100);
            $table->boolean('deleted')->default(0);
            $table->timestamps();
        });

        Schema::table('program_media', function (Blueprint $table) {
            $table->integer('program_media_type_id')->after('id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('program_media', function (Blueprint $table){
            $table->dropColumn('program_media_type_id');
        });
        Schema::dropIfExists('program_media_type');
    }
}
