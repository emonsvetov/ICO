<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramEmailTemplateExternalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_email_template_externals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_email_template_id');
            $table->longText('content')->nullable();

            $table->string('external_name', 100)->nullable();
            $table->string('external_id', 100)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('program_email_template_id', 'prog_email_tpl_id')->references('id')->on('program_email_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('program_email_template_externals');
    }
}
