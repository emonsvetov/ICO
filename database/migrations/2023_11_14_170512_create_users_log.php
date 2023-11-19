<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersLog extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_account_holder_id');
            $table->unsignedBigInteger('parent_program_id')->nullable();
            $table->string('email');
            $table->string('first_name',64)->nullable();
            $table->string('last_name', 64)->nullable();
            $table->string('type', 30);
            $table->integer('old_user_status_id')->nullable();
            $table->integer('new_user_status_id')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('technical_reason_id')->nullable();
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
        Schema::dropIfExists('users_log');
    }
}
