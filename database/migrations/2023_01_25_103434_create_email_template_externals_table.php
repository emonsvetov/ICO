<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmailTemplateExternalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('email_template_externals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('email_template_id');
            $table->longText('content')->nullable();

            $table->string('external_name', 100)->nullable();
            $table->string('external_id', 100)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('email_template_id')->references('id')->on('email_templates')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('email_template_externals');
    }
}
