<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class InsertHardcodeEmailTemplate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('email_templates')->insert([
            'external_id' => 99999,
            'name' => 'livhigh5',
            'email_template_type_id' => 1,
            'type' => 'program_event',
//            'program_id' => 4786,
            'is_default' => 1,
            'email_template_provider' => 1,
            'external_sendgrid_id' => 99999,
            'external_infusion_id' => 'Event',
            'content' => '-'
        ]);
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
