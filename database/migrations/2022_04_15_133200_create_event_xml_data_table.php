<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventXmlDataTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('event_xml_data', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('awarder_id');
            $table->string('name', 45);
            $table->integer('award_level_id');
            $table->integer('amount_override');
            $table->mediumText('notification_body')->nullable();
            $table->mediumText('notes')->nullable();
            $table->mediumText('referrer')->nullable();
            $table->integer('email_template_id')->nullable();
            $table->integer('event_id')->nullable();
            $table->string('icon', 64)->nullable();
            $table->integer('award_transaction_id')->nullable();
            $table->integer('lease_number')->nullable();
            $table->integer('token');

            $table->timestamps();

            $table->index( ['awarder_id']);
            $table->index( ['event_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('event_xml_data');
    }
}
