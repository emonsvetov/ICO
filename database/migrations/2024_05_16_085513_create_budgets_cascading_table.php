<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetsCascadingTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budgets_cascading', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('budget_program_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('parent_program_id');
            $table->string('program_external_id')->nullable();
            $table->integer('employee_count')->nullable();
            $table->decimal('budget_percentage', 10, 2)->nullable();
            $table->decimal('budget_amount', 10, 2)->nullable();
            $table->decimal('budget_awaiting_approval', 10, 2)->nullable();
            $table->decimal('budget_amount_remaining', 10, 2)->nullable();
            $table->date('budget_start_date')->nullable();
            $table->date('budget_end_date')->nullable();
            $table->smallInteger('flag')->unsigned()->nullable()->default(null);
            $table->boolean('status')->default(1);
            $table->string('reason_for_budget_change')->nullable();
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
        Schema::dropIfExists('budgets_cascading');
    }
}
