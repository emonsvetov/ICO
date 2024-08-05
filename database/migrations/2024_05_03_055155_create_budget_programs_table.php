<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budget_programs', function (Blueprint $table) {
            $table->id(); 
            $table->unsignedBigInteger('budget_type_id');
            $table->unsignedBigInteger('program_id');
            $table->double('budget_amount', 8, 2)->default(0);
            $table->double('remaining_amount', 8, 2)->default(0);            
            $table->date('budget_start_date');
            $table->date('budget_end_date');
            $table->boolean('status')->default(1);
            $table->index(['id','program_id']);
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
        Schema::dropIfExists('budget_programs');
    }
}
