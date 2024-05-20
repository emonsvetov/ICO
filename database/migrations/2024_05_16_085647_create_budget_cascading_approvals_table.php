<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBudgetCascadingApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('budget_cascading_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('budgets_cascading_id');
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('awarder_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('requestor_id');
            $table->unsignedBigInteger('manager_id');
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('award_id');
            $table->unsignedBigInteger('program_approval_id');
            $table->double('amount', 15, 4);
            $table->smallInteger('approved');
            $table->string('award_data');
            $table->integer('transaction_id');
            $table->integer('include_in_budget');
            $table->integer('action_by');
            $table->string('rejection_note');
            $table->dateTime('scheduled_date');
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
        Schema::dropIfExists('budget_cascading_approvals');
    }
}
