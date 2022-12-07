<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProgramsEmailTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::create('programs_email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('external_id', 255);
            $table->string('name', 45);
            $table->integer('email_template_type_id');
            $table->integer('program_account_holder_id');
            $table->boolean('default'); //tinyInteger length 1
            $table->integer('email_template_provider'); //tinyint length 4
            $table->string('external_sendgrid_id', 255)->nullable();
            $table->string('external_infusion_id', 255)->nullable();
            //$table->unsignedBigInteger('program_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}