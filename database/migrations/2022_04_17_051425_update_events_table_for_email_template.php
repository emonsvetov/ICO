<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateEventsTableForEmailTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropColumn('model');
            $table->dropColumn('foreign_key');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->integer('email_template_id')->nullable()->after('event_type_id');
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
            $table->string('model', 100)->nullable();
            $table->integer('foreign_key')->nullable();
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('email_template_id');
        });
    }
}
