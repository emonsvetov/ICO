<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMoreFieldsToEmailTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->string('external_id', 255)->after('id');
            // $table->dropColumn('type');
            $table->unsignedBigInteger('email_template_type_id')->after('name');
            $table->tinyInteger('email_template_provider')->after('is_default')->default(1);
            $table->string('external_sendgrid_id', 255)->after('email_template_provider')->nullable();
            $table->string('external_infusion_id', 255)->after('external_sendgrid_id')->nullable();
            $table->string('last_import_hash', 255)->after('external_infusion_id')->nullable();
            $table->integer('email_template_sender_id')->after('last_import_hash')->nullable();
            $table->integer('email_template_sendgrid_user_id')->after('email_template_sender_id')->nullable();
            $table->string('infusion_sender', 255)->after('email_template_sendgrid_user_id')->nullable();

            // indexes
            // $table->foreign('email_template_type_id')->references('id')->on('email_template_types');
            $table->index(['name','program_id']);
            $table->index(['program_id', 'email_template_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn('external_id');
            $table->string('type', 50)->after('name');
            $table->dropColumn('email_template_type_id');
            $table->dropColumn('email_template_provider');
            $table->dropColumn('external_sendgrid_id');
            $table->dropColumn('external_infusion_id');
            $table->dropColumn('last_import_hash');
            $table->dropColumn('email_template_sender_id');
            $table->dropColumn('email_template_sendgrid_user_id');
            $table->dropColumn('infusion_sender');
        });
    }
}
