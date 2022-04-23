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
            $table->bigIncrements('id');
            $table->unsignedBigInteger('awarder_id');
            $table->string('name', 45);
            $table->string('award_level_name', 45);
            $table->integer('amount_override')->default(0);
            $table->mediumText('notification_body')->nullable();
            $table->mediumText('notes')->nullable();
            $table->mediumText('referrer')->nullable();
            $table->integer('email_template_id')->nullable();
            $table->integer('event_type_id')->default(0);
            $table->integer('event_template_id')->default(0);
            $table->string('icon', 64)->nullable();
            $table->binary('xml')->nullable();
            $table->string('award_transaction_id', 100)->nullable();
            $table->string('lease_number')->nullable();
            $table->string('token', 120);

            $table->timestamps();

            $table->index( ['awarder_id']);
            $table->index( ['event_template_id']);
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
