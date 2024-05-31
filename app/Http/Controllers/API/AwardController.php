<?php

namespace App\Http\Controllers\API;

use App\Events\ProgramCreated;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProgramRequest;
use App\Http\Requests\ReclaimPeerPointsRequest;
use App\Http\Requests\AwardRequest;
use App\Models\AwardLevel;
use App\Models\AwardLevelHasUser;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use App\Services\AwardService;
use App\Services\RabbitMQService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use function PHPUnit\Framework\isEmpty;

class AwardController extends Controller
{
    public function store(
        AwardRequest $request,
        Organization $organization,
        Program $program,
        AwardService $awardService
    )
    {
        try {
            /** @var User $awarder */
            $awarder = auth()->user();
            $newAward = $awardService->awardMany($program, $organization, $awarder, $request->validated());
            return response($newAward);
        } catch (\Exception $e) {
            return response(['errors' => 'Award creation failed', 'e' => $e->getMessage(), 'verbose' => sprintf('Error occurred in line %d of file "%s" ', $e->getLine(), $e->getFile())], 422);
        }
    }

    public function readListReclaimablePeerPoints(
        Organization $organization,
        Program $program,
        User $user,
        AwardService $awardService
    ) {
        $limit=9999999;
        $offset=0;
        return response($awardService->readListReclaimablePeerPointsByProgramAndUser(
            $program,
            $user,
            $limit,
            $offset
        ));
    }
    public function ReclaimPeerPoints(
        ReclaimPeerPointsRequest $request,
        Organization $organization,
        Program $program,
        User $user,
        AwardService $awardService
    ) {
        try{
            $response = $awardService->reclaimPeerPoints($program, $user, $request->validated()['reclaim']);
            return response($response);
        } catch (\Exception $e) {
            return response(['errors' => 'Reclaim failed', 'e' => $e->getMessage()], 422);
        }
    }

    public function storeRaw(Request $request, AwardService $awardService)
    {
        DB::beginTransaction();
        try {
            $organization = Organization::where('id', $request->get('organization_id'))->first();
            $program = Program::where('id', $request->get('program_id'))->first();
            $awarder = User::where('email', $request->get('manager_email'))->first();
            $event = Event::where('name', $request->get('event_name'))->where('program_id', $request->get('program_id'))->first();
            $user = User::where('email', $request->get('user_email'))->first();

            $awardRequest = AwardRequest::createFrom($request);
            $validator = Validator::make(
                array_merge($awardRequest->all(),
                    [
                        'event_id' => $event->id,
                        'user_id' => [$user->id]
                    ]),
                $awardRequest->rules()
            );
            $awardRequest->setValidator($validator);
            if ($validator->fails()) {
                throw new \Exception(print_r($validator->errors(), true));
            }
            $newAward = $awardService->awardMany($program, $organization, $awarder,$awardRequest->validated());
            if (empty($newAward)){
                throw new \Exception('User is not found');
            }
            DB::commit();
            return response(['message' => 'User successfully awarded']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['errors' => 'Award creation failed', 'e' => $e->getMessage()], 422);
        }
    }

    public function programAwardLevels($organization, $program, AwardService $awardService)
    {
        $awardLevels = AwardLevel::select('award_levels.*')
            ->selectRaw('COUNT(award_levels_has_users.users_id) as number_of_participants')
            ->leftJoin('award_levels_has_users', 'award_levels.id', '=', 'award_levels_has_users.award_levels_id')
            ->where('award_levels.program_id', $program)
            ->groupBy('award_levels.id')
            ->get();

        return response($awardLevels, 200);
    }

    public function createAwardLevel(Request $request, $organization, $program, AwardService $awardService)
    {
        if ($request->id){
            $awardLevel = AwardLevel::find($request->id);
        }else{
            $awardLevel = new AwardLevel();
            $awardLevel->program_id = $request->program_id;
            $awardLevel->program_account_holder_id = null;
        }
        $awardLevel->name = $request->name;

        return response([
            'success' => $awardLevel->save()
        ], 200);
    }

    public function awardLevelParticipants(Request $request, $organization, $program, AwardService $awardService)
    {
        $results = AwardLevelHasUser::select('award_levels_has_users.*', 'users.*')
            ->leftJoin('users', 'award_levels_has_users.users_id', '=', 'users.id')
            ->where('award_levels_has_users.award_levels_id', $request->id)
            ->get();

        return response([
            'data' => $results
        ], 200);
    }
}
