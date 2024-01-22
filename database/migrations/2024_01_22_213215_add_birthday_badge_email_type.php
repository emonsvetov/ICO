<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBirthdayBadgeEmailType extends Migration
{
    public function up()
    {
        if( !DB::table('email_template_types')->where('type', 'LIKE', 'Birthday Badge')->first() )  {
            DB::table('email_template_types')->insert(
                array(
                    'type' => 'Birthday Badge',
                )
            );
        }

        Schema::table('email_templates', function (Blueprint $table) {
            $table->dropForeign('email_templates_organization_id_foreign');
            $table->dropForeign('email_templates_program_id_foreign');
        });

        if( !DB::table('email_templates')->where('name', 'LIKE', 'birthdayBadgeLiveHigh5')->first() )  {
            DB::table('email_templates')->insert([
                'external_id' => 99999,
                'name' => 'birthdayBadgeLiveHigh5',
                'email_template_type_id' => 13,
                'type' => 'program_event',
                'program_id' => 4786,
                'is_default' => 1,
                'email_template_provider' => 1,
                'external_sendgrid_id' => 99999,
                'external_infusion_id' => 'Event',
                'content' => '-',
                'subject' => 'Happy Birthday'
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
    }
}
