<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHmiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hmi', function (Blueprint $table) {
            $table->id();
            $table->string('hmi_name');
            $table->string('hmi_username');
            $table->string('hmi_password');
            $table->string('hmi_url');
            $table->boolean('hmi_is_test')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hmi');
    }
}
