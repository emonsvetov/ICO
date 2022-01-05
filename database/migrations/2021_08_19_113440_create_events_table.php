<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('program_id');
			
			$table->boolean('enable')->nullable();
            $table->string('name');
			$table->integer('type_id'); //dropdown pending
			$table->string('icon');
			$table->float('amount');
			$table->boolean('allow_amount_overriding');
			$table->integer('email_template_id'); //dropdown pending
			$table->boolean('post_to_social_wall');
			$table->text('message');
			$table->boolean('include_in_budget')->nullable(); //pending to check
			$table->boolean('enable_schedule_award')->nullable();
			$table->boolean('is_birthday_award')->nullable();
			$table->boolean('is_anniversary_award')->nullable();
			$table->integer('ledger_code')->nullable();
		

            $table->timestamps();

            $table->index(['id','organization_id','program_id']);
        });

        Schema::create('event_participant_group', function (Blueprint $table) {
            $table->id();
            
            $table->unsignedBigInteger('event_id');
            $table->unsignedBigInteger('participant_group_id');
            
            $table->timestamps();

            $table->index( ['event_id','participant_group_id']);
            $table->unique(['event_id','participant_group_id']);
            
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_participant_group');
        Schema::dropIfExists('events');
    }
}
