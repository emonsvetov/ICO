<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\Event;
use App\Models\User;
use Exception;

class MilestoneAwardService extends AwardService {

    public array $programUserCache = [];
    public array $userStatus = [];

    public function sendMilestoneAward() {
        // DB::enableQueryLog();
        // pr(toSql(DB::getQueryLog()));
        $events = Event::getActiveMilestoneAwardsWithProgram();
        if( $events->isNotEmpty() )  {
            $this->userStatus[] = User::getIdStatusActive();
            $this->userStatus[] = User::getIdStatusPendingActivation();
            $programService = resolve(\App\Services\ProgramService::class);
            foreach($events as $event)   {
                $participants = $this->getMilestoneAwardeesByEvent( $event );
                if( $participants ) {
                    foreach( $participants as $participant )   {
                        if ( !$programService->canProgramPayForAwards($event->program, $event, [$participant->id], $event->max_awardable_amount) ) {
                            cronlog ("Program cannot pay for award. UserId:{$participant->id} ProgramID:{$event->program->id}" );
                            continue;
                        }
                        cronlog (sprintf("going to award %d to UserID:%d",$event->max_awardable_amount, $participant->id));

                        $data = [
                            'event_id' => $event->id,
                            'message' => $event->message
                        ];

                        $managers = $event->program->getManagers();
                        $manager = $managers[0] ?? null;
                        $manager = $manager ?? $participant;

                        $this->awardUser($event, $participant, $manager, (object)$data);
                    }
                }
            }
        }
    }

    private function getMilestoneAwardeesByEvent( Event $event )   {
        $milestoneYears = $event->milestone_award_frequency;

        $program = $event->program;
        $query = User::whereHas('roles', function (Builder $query) use ($program) {
            $query->where('name', 'LIKE', config('roles.participant'))
                ->where('model_has_roles.program_id', $program->id);
        });
        $query->whereIn('user_status_id', $this->userStatus);
        $query->whereNotNull('work_anniversary');
        try{
            $participants = $query->get();
        }   catch (Exception $e) {
            throw new Exception("Error: ". $e->getMessage());
        }
        $eligibleParticipants = collect([]);
        if( $participants->isNotEmpty() )  {
            foreach($participants as $participant)   {
                if ( ! $participant->canBeAwarded($event->program) ) {
                    // Log::info ("User cannot be rewarded. User Id: {$participant->id} at"  . date('Y-m-d h:i:s')  );
                    continue;
                }
                $userMilestoneDate = $this->getAnniversaryDateForUser( $participant );
                if( $userMilestoneDate )    {
                    $dateObject = \Carbon\Carbon::parse($userMilestoneDate);
                    $dateObject->addYears($milestoneYears);
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

    private function getAnniversaryDateForUser( $user ) {
        if( empty($user->work_anniversary) || $user->work_anniversary == '0000-00-00' )    {
            return;
        }
        $data = ['work_anniversary' => $user->work_anniversary];
        $rules = ['work_anniversary' => 'date_format:Y-m-d|nullable'];
        $validator = Validator::make($data, $rules);
        if( $validator->failed() )   {
            return;
        }
        if( !empty($data['work_anniversary']) && $data['work_anniversary'] != '0000-00-00' ) {
            return $data['work_anniversary'];
        }
    }
}
