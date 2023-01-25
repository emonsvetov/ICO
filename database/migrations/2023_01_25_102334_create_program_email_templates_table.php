<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramEmailTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_email_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_template_type_id');
            $table->tinyInteger('email_template_provider_id')->default(1);
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->string('name', 150);
            $table->boolean('is_default')->default(1);
            $table->longText('content')->nullable();
            $table->string('class_path', 255)->nullable(); //to save "\App\Mail\templates\WelcomeEmail" etc.

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('email_template_type_id', 'email_tpl_id')->references('id')->on('email_template_types');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('program_email_templates');
    }
}
