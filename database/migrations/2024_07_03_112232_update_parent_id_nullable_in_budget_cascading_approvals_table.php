<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateParentIdNullableInBudgetCascadingApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('budget_cascading_approvals', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('budget_cascading_approvals', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable(false)->change();
        });
    }
}
