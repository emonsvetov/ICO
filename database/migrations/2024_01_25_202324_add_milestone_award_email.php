<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMilestoneAwardEmail extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if( !DB::table('email_template_types')->where('type', 'LIKE', 'Milestone Award')->first() )  {
            DB::table('email_template_types')->insert(
                array(
                    'type' => 'Milestone Award',
                )
            );
        }

        if( !DB::table('email_templates')->where('name', 'LIKE', 'milestoneAwardLiveHigh5')->first() )  {
            DB::table('email_templates')->insert([
                'external_id' => 99999,
                'name' => 'awardLiveHigh5',
                'email_template_type_id' => 2,
                'type' => 'program_event',
                'program_id' => 4786,
                'is_default' => 1,
                'email_template_provider' => 1,
                'external_sendgrid_id' => 99999,
                'external_infusion_id' => 'Event',
                'content' => '-',
                'subject' => 'You have a new reward!'
            ]);
        }
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
