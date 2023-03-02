<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->boolean('initiate_award_to_award')->default(0);
            $table->boolean('amount_override')->default(0);
            $table->boolean('is_promotional')->default(0);
            $table->boolean('only_internal_redeemable')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('initiate_award_to_award');
            $table->dropColumn('amount_override');
            $table->dropColumn('is_promotional');
            $table->dropColumn('only_internal_redeemable');
        });
    }
}
