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
			$table->boolean('enable')->default(1);
            $table->string('name');
			$table->integer('type_id'); //dropdown pending
			$table->integer('event_icon_id')->nullable();
			$table->float('amount');
			$table->boolean('allow_amount_overriding')->default(0);
			$table->integer('email_template_id'); //dropdown pending
			$table->boolean('post_to_social_wall')->default(0);
			$table->text('message');
			$table->boolean('include_in_budget')->default(0); //pending to check
			$table->boolean('enable_schedule_award')->default(0);
			$table->boolean('is_birthday_award')->default(0);
			$table->boolean('is_anniversary_award')->default(0);
			$table->boolean('award_message_editable')->default(0);
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
