<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmailTemplateType;

class EmailTemplateTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EmailTemplateType::insert([
            [
                'id' => '1',
                'type' => 'Invite Participant'
            ],
            [
                'id' => '2',
                'type' => 'Invite Manager'
            ],
            [
                'id' => '3',
                'type' => 'Welcome'
            ],
            [
                'id' => '4',
                'type' => 'Password Reset'
            ],
            [
                'id' => '5',
                'type' => 'Activation Reminder'
            ],
            [
                'id' => '6',
                'type' => 'Award'
            ],
            [
                'id' => '7',
                'type' => 'Gift Code'
            ],
            [
                'id' => '8',
                'type' => 'Goal Progress'],
            [
                'id' => '9',
                'type' => 'Points Expiration'],
            [
                'id' => '10',
                'type' => 'Allocate Peer to Peer'],
            [
                'id' => '11',
                'type' => 'Award Badge'],
            [
                'id' => '12',
                'type' => 'Award Approved'],
            [
                'id' => '13',
                'type' => 'Award Approval'],
            [
                'id' => '14',
                'type' => 'Budget Approved'],
            [
                'id' => '15',
                'type' => 'Budget Approval'],
            [
                'id' => '16',
                'type' => 'Budget Denied'],
            [
                'id' => '17',
                'type' => 'Budget Approved Edited'
            ],
            [
                'id' => '18',
                'type' => 'Program Closing Notice'
            ]
        ]);
    }
}