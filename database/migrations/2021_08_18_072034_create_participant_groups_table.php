<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParticipantGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('participant_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');

            $table->string('name');

            $table->timestamps();

            $table->index(['id','organization_id']);
        });

        Schema::create('participant_group_user', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('participant_group_id');
            $table->unsignedBigInteger('user_id');
            
            $table->timestamps();

            $table->index(['participant_group_id','user_id']);
            $table->unique(['participant_group_id','user_id']);
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('participant_group_user');
        Schema::dropIfExists('participant_groups');
    }
}
