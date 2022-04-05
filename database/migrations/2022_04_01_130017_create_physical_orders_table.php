<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePhysicalOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('physical_orders', function (Blueprint $table) {
            $table->id();
            $table->string('ship_to_name', 128);
            $table->string('line_1', 512);
            $table->string('line_2', 512);
            $table->string('zip', 16);
            $table->string('city', 128);
            $table->integer('user_id');
            $table->integer('country_id');
            $table->integer('state_id');
            $table->integer('state_type_id');
            $table->integer('program_id');
            $table->integer('modified_by');
            $table->mediumText('notes');
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
        Schema::dropIfExists('physical_orders');
    }
}
