<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('program_id');

            $table->string('name');

            $table->timestamps();

            $table->index(['id','organization_id','program_id']);
        });

        Schema::create('event_participant_group', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('participant_group_id');
            
            $table->timestamps();

            $table->index( ['event_id','participant_group_id']);
            $table->unique(['event_id','participant_group_id']);
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_participant_group');
        Schema::dropIfExists('events');
    }
}
