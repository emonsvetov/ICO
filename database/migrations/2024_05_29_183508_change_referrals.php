<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeReferrals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('referrals', function (Blueprint $table) {
            // Modify columns
            $table->string('sender_first_name')->after('sender_id')->nullable();
            $table->string('sender_last_name')->after('sender_first_name')->nullable();
            $table->string('sender_email')->after('sender_last_name')->nullable();
            
            $table->integer('sender_id')->nullable()->change();
            $table->string('recipient_first_name')->nullable()->change();
            $table->string('recipient_last_name')->nullable()->change();
            $table->string('recipient_email')->nullable()->change();
            $table->string('recipient_area_code')->nullable()->change();
            $table->string('recipient_phone', 50)->nullable()->change();
            $table->mediumText('message')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('referrals', function (Blueprint $table) {
            // Revert changes
            $table->dropColumn('sender_first_name');
            $table->dropColumn('sender_last_name');
            $table->dropColumn('sender_email');

            $table->integer('sender_id')->change();
            $table->string('recipient_first_name')->change();
            $table->string('recipient_last_name')->change();
            $table->string('recipient_email')->change();
            $table->string('recipient_area_code')->change();
            $table->string('recipient_phone', 50)->change();
            $table->mediumText('message')->nullable()->change();
        });
    }
}
