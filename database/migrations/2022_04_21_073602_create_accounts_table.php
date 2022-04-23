<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_holder_id'); //user_id
            $table->unsignedBigInteger('account_type_id');
            $table->unsignedBigInteger('finance_type_id');
            $table->unsignedBigInteger('medium_type_id');
            $table->unsignedBigInteger('currency_type_id');
            $table->unique([
                'account_holder_id',
                'account_type_id',
                'finance_type_id',
                'medium_type_id'
            ], 'unique');
            $table->index('account_holder_id');
            $table->index('account_type_id');
            $table->index('finance_type_id');
            $table->index('medium_type_id');
            $table->index('currency_type_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('accounts');
    }
}
