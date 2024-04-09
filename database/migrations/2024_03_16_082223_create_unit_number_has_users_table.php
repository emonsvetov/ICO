<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUnitNumberHasUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if ( !Schema::hasTable('unit_number_has_users') ) {
            Schema::create('unit_number_has_users', function (Blueprint $table) {
                $table->unsignedBigInteger('unit_number_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unit_number_has_users');
    }
}
