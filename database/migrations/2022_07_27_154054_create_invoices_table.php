<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('key',45);
            $table->integer('seq');
            $table->unsignedBigInteger('program_id');
            $table->tinyInteger('invoice_type_id');
            $table->tinyInteger('payment_method_id')->default(0);
            $table->tinyInteger('participants')->default(0);
            $table->tinyInteger('new_participants')->default(0);
            $table->tinyInteger('managers')->default(0);
            $table->date('date_begin');
            $table->date('date_end');
            $table->date('date_due');
            $table->double('amount')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
