<?php

namespace App\Services;

use App\Models\Organization;
use App\Notifications\FeelingSurveyNotification;
use App\Models\FeelingSurvey;
use App\Models\Program;

class FeelingSurveyService
{
    public function submit(Organization $organization, Program $program, $data )  {
        $feelingSurvey = FeelingSurvey::create($data);
        $notification =  [
            'first_name' => $feelingSurvey->first_name,
            'last_name' => $feelingSurvey->last_name,
            'feeling' => $feelingSurvey->feeling,
            'email' => $feelingSurvey->email,
            'comment' => $feelingSurvey->comment,
            'program' => $program
        ];

        if( $program->getManagers()->isNotEmpty() )   {
            foreach( $program->getManagers() as $manager )  {
                $manager->notify(new FeelingSurveyNotification((object)( $notification )));
            }
        }
        else if($program->parent()->exists()){
            $parent = $program->parent()->first();
            if($parent -> getManagers()->isNotEmpty()){
                foreach( $parent->getManagers() as $manager )  {
                    $manager->notify(new FeelingSurveyNotification((object)( $notification )));
                }
            }
        }
        return $feelingSurvey;
    }
}

