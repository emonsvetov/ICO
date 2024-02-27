<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAwardLevelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('award_levels')) {
            Schema::create('award_levels', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('program_account_holder_id');
                $table->integer('program_id')->index();
                $table->string('name', 45);
                $table->integer('v2id')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('award_levels');
    }
}

