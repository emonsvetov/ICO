<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateExternalCallbacksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('external_callbacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_holder_id');
            $table->string('name', 45);
            $table->string('access_key', 45)->nullable();
            $table->string('secret_key', 40);
            $table->string('callback_type_id', 45);
            $table->enum('method', ['GET', 'POST', 'PUT', 'DELETE', 'HEAD'])->default('POST');
            $table->string('protocol', 16);
            $table->string('hostname', 45);
            $table->integer('port')->default(80);
            $table->mediumText('uri');
            $table->mediumText('query_string');
            $table->string('content_type', 128);
            $table->mediumText('content')->nullable();
            $table->string('handler', 128);
            $table->tinyInteger('include_user_authentication_token')->nullable()->default(0);
            $table->string('function', 45)->nullable();
            $table->tinyInteger('retry_limit')->default(3);
            $table->boolean('is_synchronous')->default(1);
            $table->timestamps();

            $table->index( ['account_holder_id', 'callback_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('external_callbacks');
    }
}
