<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReferralNotificationRecipientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('referral_notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('program_id');
            $table->string('referral_notification_recipient_email', 50);
            $table->string('referral_notification_recipient_name', 50);
            $table->string('referral_notification_recipient_lastname', 50);
            $table->boolean('referral_notification_recipient_active')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('referral_notification_recipients');
        Schema::table('referral_notification_recipients', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}