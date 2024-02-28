<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyAwardLevelsV2idNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('award_levels', function (Blueprint $table) {
            // Modify the column to make it nullable
            $table->integer('v2id')->nullable()->change();
            $table->integer('program_account_holder_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('award_levels', function (Blueprint $table) {
            // Revert the changes made in the up() method
            $table->integer('v2id')->change();
            $table->integer('program_account_holder_id')->change();
        });
    }
}
