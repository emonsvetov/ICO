<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaderboardsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaderboards', function (Blueprint $table) {
            $table->id();
            $table->string('name', 165);
            $table->integer('leaderboard_type_id');
            $table->integer('status_id');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('program_id');
            $table->boolean('visible')->default(1);
            $table->boolean('one_leaderboard')->default(0);

            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            $table->index('name');
            $table->index('status_id');
            $table->index('program_id');
            $table->index('leaderboard_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leaderboards');
    }
}
