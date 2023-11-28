<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTechnicalReasons extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('technical_reasons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('context');
            $table->timestamps();
        });

        DB::table('technical_reasons')->insert([
            'name' => 'Program Deactivation impact on Users',
            'context' => 'Users - Status Change'
        ]);
        DB::table('technical_reasons')->insert([
            'name' => 'Program Deactivation Revoke impact on Users',
            'context' => 'Users - Status Change Revert'
        ]);
        DB::table('technical_reasons')->insert([
            'name' => 'Program Deactivation Initiated',
            'context' => 'Programs - Program Deactivation Initiated'
        ]);
        DB::table('technical_reasons')->insert([
            'name' => 'Program Deactivation Revoke Initiated',
            'context' => 'Programs - Program Deactivation Revoke Initiated'
        ]);
        DB::table('technical_reasons')->insert([
            'name' => 'Program Deactivation Revoked',
            'context' => 'Programs - Program Deactivation Revoked'
        ]);
        DB::table('technical_reasons')->insert([
            'name' => 'Program Deactivation Compeleted By Cron',
            'context' => 'Programs - Program Deactivation Compeleted'
        ]);
        DB::table('technical_reasons')->insert([
            'name' => 'Account Deactivation Duplicated Account',
            'context' => 'Users - Status Change'
        ]);
        DB::table('technical_reasons')->insert([
            'name' => 'Account Deactivation Audit',
            'context' => 'Users - Status Change'
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('technical_reasons');
    }
}
