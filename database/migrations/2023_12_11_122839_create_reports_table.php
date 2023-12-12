<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_list', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->timestamps();
        });

        DB::table('program_list')->insert([
            ['name' => 'Participant Account Summary', 'url' => '/manager/report/participant-account-summary'],
            ['name' => 'Participant Status Summary', 'url' => '/manager/report/participant-status-summary'],
            ['name' => 'Invoices', 'url' => '/manager/report/invoices'],
            ['name' => 'Annual Awards Summary', 'url' => '/manager/report/annual-awards-summary'],
            ['name' => 'Award Account Summary GL', 'url' => '/manager/report/award-account-summary-gl'],
            ['name' => 'Award Detail', 'url' => '/manager/report/award-detail'],
            ['name' => 'Award Summary', 'url' => '/manager/report/award-summary'],
            ['name' => 'Merchant Redemption', 'url' => '/manager/report/merchant-redemption'],
            ['name' => 'File Import', 'url' => '/manager/report/file-import'],
            ['name' => 'Engagement report', 'url' => '/manager/report/referral-participant'],
            ['name' => 'Quarterly Awards Summary', 'url' => '/manager/report/quarterly-awards-summary'],
            ['name' => 'Deposit Balance', 'url' => '/manager/report/deposit-balance'],
            ['name' => 'Deposit Transfers', 'url' => '/manager/report/deposit-transfers'],

        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('program_list');
    }
}