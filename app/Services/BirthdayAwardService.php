<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\Program;
use App\Models\Event;
use App\Models\User;
use Exception;

class BirthdayAwardService extends AwardService {

    public array $programUserCache = [];

    public function sendBirthdayAward() {
        DB::enableQueryLog();
        $events = Event::getBirthdayAwardsWithProgram();
        // pr($events->toArray());
        // pr(toSql(DB::getQueryLog()));
        // exit;
        // pr($events->toArray());
        if( $events->isNotEmpty() )  {
            $programService = resolve(\App\Services\ProgramService::class);
            foreach($events as $event)   {
                DB::enableQueryLog();
                $participants = $this->getBirthdayAwardeesByEvent( $event );
                pr($participants->toArray());
                // pr(toSql(DB::getQueryLog()));
                if( $participants ) {
                    // pr($participants);
                    // foreach( $participants as $participant )   {
                    //     if ( !$programService->canProgramPayForAwards($event->program, $event, [$participant->id], $event->max_awardable_amount) ) {
                    //         cronlog ("Program cannot pay for award. UserId:{$participant->id} ProgramID:{$event->program->id}" );
                    //         continue;
                    //     }
                    //     cronlog (sprintf("going to award %d to UserID:%d",$event->max_awardable_amount, $participant->id));
                    //     $this->awardUser($event, $participant, $participant);
                    // }
                }
            }
        }
        // pr(DB::getQueryLog());
    }

    private function getBirthdayAwardeesByEvent( Event $event )   {
        $milestoneYears = $event->milestone_award_frequency;
        $userStatus = User::getStatusByName(User::STATUS_DELETED);

        $program = $event->program;
        $query = User::whereHas('roles', function (Builder $query) use ($program) {
            $query->where('name', 'LIKE', config('roles.participant'))
                ->where('model_has_roles.program_id', $program->id);
        });
        $query->where('user_status_id', '!=', $userStatus->id);
        $query->whereNotNull('dob');
        try{
            $participants = $query->get();
        }   catch (Exception $e) {
            throw new Exception("Error: ". $e->getMessage());
        }
        // pr(toSql(DB::getQueryLog()));
        $eligibleParticipants = collect([]);
        if( $participants->isNotEmpty() )  {
            foreach($participants as $participant)   {
                if ( ! $participant->canBeAwarded($event->program) ) {
                    // Log::info ("User cannot be rewarded. User Id: {$participant->id} at"  . date('Y-m-d h:i:s')  );
                    continue;
                }
                if( $participant->id != 11 ) continue;
                $userBirthDate = $this->getBirthdayDateForUser( $participant );
                if( $userBirthDate )    {
                    $dateObject = \Carbon\Carbon::parse($userBirthDate);
                    if($dateObject->isToday())  {
                        $eligibleParticipants->add($participant);
                    }
                }
            }
        }   else {
            // pr('No user');
        }
        return $eligibleParticipants;
    }

    private function getBirthdayDateForUser( $user ) {
        if( empty($user->dob) || $user->dob == '0000-00-00' )    {
            return;
        }
        $data = ['dob' => $user->dob];
        $rules = ['dob' => 'date_format:Y-m-d|nullable'];
        $validator = Validator::make($data, $rules);
        if( $validator->failed() )   {
            continue;
        }
        if( !empty($data['dob']) && $data['dob'] != '0000-00-00' ) {
            return $data['dob'];
        }
    }
}
