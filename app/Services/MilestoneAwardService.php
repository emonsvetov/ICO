<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Program;
use App\Models\Event;
use App\Models\User;

class MilestoneAwardService extends AwardService {

    public array $programUserCache = [];

    public function sendMilestoneAward() {
        DB::enableQueryLog();
        $events = Event::getActiveMilestoneAwardsWithProgram();
        if( $events->isNotEmpty() )  {
            $programService = resolve('App\Services\ProgramService');
            foreach($events as $event)   {
                $participants = $this->getMilestoneAwardeesByEvent( $event );
                if( $participants ) {
                    foreach( $participants as $participant )   {
                        if ( !$programService->canProgramPayForAwards($event->program, $event, [$participant->id], $event->max_awardable_amount) ) {
                            Log::info ("Program cannot pay for award. UserId:{$participant->id} ProgramID:{$event->program->id}" );
                            continue;
                        }
                        Log::info (sprintf("going to award %d to UserID:% " . $event->max_awardable_amount, $participant->id));

                        $this->award($event, $participant, $participant);
                    }
                }
            }
        }
        // pr(DB::getQueryLog());
    }

    private function getMilestoneAwardeesByEvent( Event $event )   {
        $milestoneYears = $event->milestone_award_frequency;
        $userStatus = User::getStatusByName(User::STATUS_DELETED);

        $program = $event->program;
        $query = User::whereHas('roles', function (Builder $query) use ($program) {
            $query->where('name', 'LIKE', config('roles.participant'))
                ->where('model_has_roles.program_id', $program->id);
        });
        $query->where('user_status_id', '!=', $userStatus->id);
        $query->where( function ($query) {
            $query->orWhereNotNull('work_anniversary');
            $query->orWhereNotNull('hire_date');
        });
        $participants = $query->get();
        $eligibleParticipants = [];
        if( $participants->isNotEmpty() )  {
            foreach($participants as $participant)   {
                $userMilestoneDate = $this->getAnniversaryDateForUser( $participant );
                if( $userMilestoneDate )    {
                    // pr($milestoneYears);
                    // pr($userMilestoneDate);
                    $dateObject = \Carbon\Carbon::parse($userMilestoneDate);
                    $dateObject->addYears($milestoneYears);
                    if($dateObject->isToday())  {
                        if ( ! $participant->canBeAwarded($event->program) ) {
                            // Log::info ("User cannot be rewarded. User Id: {$participant->id} at"  . date('Y-m-d h:i:s')  );
                            continue;
                        }
                        array_push($eligibleParticipants, $participant);
                    }
                }
            }
        }   else {
            // pr('No user');
        }
        return $eligibleParticipants;
    }

    private function getAnniversaryDateForUser( $user ) {
        $fields = ['hire_date']; //also "work_annivarsery" ?
        foreach ($fields as $field)  {
            if( empty($user->{$field}) || $user->{$field} == '0000-00-00' )    {
                continue;
            }
            $data = [$field => $user->{$field}];
            $rules = [$field => 'date_format:Y-m-d|nullable'];
            $validator = Validator::make($data, $rules);
            if( $validator->failed() )   {
                continue;
            }
            if( !empty($data[$field]) && $data[$field] != '0000-00-00' ) {
                return $data[$field];
            }
        }
    }
}
