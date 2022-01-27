<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramMerchantTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_merchant', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('program_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('program_merchant', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropForeign(['merchant_id']);
        });
        Schema::dropIfExists('program_merchant');
    }
}
