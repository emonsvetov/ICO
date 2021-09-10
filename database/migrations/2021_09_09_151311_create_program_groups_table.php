<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramGroupsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_groups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');

            $table->string('name');
            $table->timestamps();
        });

        Schema::create('program_program_group', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('program_group_id');
            
            $table->timestamps();

            $table->index(['program_id','program_group_id']);
            $table->unique(['program_id','program_group_id']);
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('program_groups');
    }
}
