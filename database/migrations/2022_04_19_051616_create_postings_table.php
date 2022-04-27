<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePostingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('postings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('journal_event_id');
            $table->unsignedBigInteger('medium_info_id')->nullable();
            $table->unsignedBigInteger('account_id');
            $table->decimal('posting_amount', 11, 4);
            $table->decimal('qty', 9, 4)->default(1);
            $table->boolean('is_credit')->default(0);
            $table->timestamps();

            $table->index('journal_event_id');
            $table->index('user_id');
            $table->index('medium_info_id');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('postings');
    }
}
