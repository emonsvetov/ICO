<?php

namespace App\Services;

use App\Http\Resources\SocialWallPostResource;
use App\Models\Organization;
use App\Models\Program;
use App\Models\SocialWallPost;
use App\Models\SocialWallPostType;
use App\Models\User;
use App\Repositories\interfaces\UserRepositoryInterface;
use App\Mail\templates\MentionUserEmail;
use Mail;

class SocialWallPostService
{
    private ProgramService $programService;
    private UserRepositoryInterface $userRepository;

    public function __construct(ProgramService $programService, UserRepositoryInterface $userRepository)
    {
        $this->programService = $programService;
        $this->userRepository = $userRepository;
    }

    public function create(array $data, Program $program ): ?SocialWallPost
    {
        $mentionedUsers = $data['mentions_user_ids'];
        unset($data['mentions_user_ids']);
        $resultObject = SocialWallPost::create($data);
        if(!empty($mentionedUsers)) {
            $template = $program->getTemplate();
            foreach($mentionedUsers as $user_id) {
                $user = User::where('id',$user_id)->get()->first();
                $message = new MentionUserEmail($user->name, $template, $data['comment']);
                Mail::to($user->email)->send($message);
            }
        }
        return $resultObject;
    }

    public function like(Organization $organization, Program $program, $user, array $request)
    {
        $post = SocialWallPost::find($request['id']);
        $like = json_decode($post->like);
        if ($like == null)
            $like = [];
        if (in_array($user->id, $like)) {
            $like = array_diff($like, [$user->id]);
        }
        else {
            $like[] = $user->id;
        }
        $post->like = $like;
        $post->likesCount = count($like);
        return $post->save();
    }

    public function getIndexData(Organization $organization, Program $program, $user, array $request): array
    {
        $uses_social_wall = (bool)$program->uses_social_wall;
        $can_view_hierarchy_social_wall = (bool)$program->can_view_hierarchy_social_wall;
        $social_wall_separation = (bool)$program->social_wall_separation;

        $hierarchy = [];
        if ($uses_social_wall){
            if ($social_wall_separation) {
                $hierarchy = $this->getOtherProgramIds($user);
            } else if ($can_view_hierarchy_social_wall) {
                $hierarchy = $this->getHierarchyAllowOtherProgramIds($program);
            } else {
                $hierarchy = $this->programService->getDescendents($program, true)->pluck('id')->toArray();
            }
        }
        // Removing condition - where('social_wall_post_type_id', SocialWallPostType::getEventTypeId()) - as there is no condition in v2
        $data = SocialWallPost::where('organization_id', $organization->id)
            ->select('social_wall_posts.*')
            ->whereIn('program_id', $hierarchy)
            ->whereNull('social_wall_post_id')
            ->orderBy('created_at', 'DESC')
            // ->with(['eventXmlData.event.eventIcon:id,name']) //can be used but limit the select fields first
            ->limit(10)
            ->get();

        return [
            'data' => SocialWallPostResource::collection($data),
            'total' => $data->count(),
        ];
    }

    /**
     * @param Program $program
     * @return array
     */
    public function getHierarchyAllowOtherProgramIds(Program $program): array
    {
        $where = [
            'uses_social_wall' => true,
            'allow_hierarchy_to_view_social_wall' => true,
        ];
        $hierarchy = $this->programService->getDescendentsWithCondition($program, $where);
        $hierarchy->push($program);
        return $hierarchy->pluck('id')->toArray();
    }

    /**
     * @param User $user
     * @return array
     */
    public function getOtherProgramIds(User $user): array
    {
        $resultProgramIds = [];

        $programs = $this->userRepository->getParticipantPrograms($user);
        foreach ($programs as $programItem){
            $uses_social_wall = (bool)$programItem->uses_social_wall;
            $can_view_hierarchy_social_wall = (bool)$programItem->can_view_hierarchy_social_wall;
            if ($uses_social_wall){
                $resultProgramIds[] = $programItem->id;
                if ($can_view_hierarchy_social_wall){
                    $hierarchy = $this->getHierarchyAllowOtherProgramIds($programItem);
                    $resultProgramIds = array_merge($resultProgramIds, $hierarchy);
                }
            }
        }

        $resultProgramIds = array_unique($resultProgramIds);

        return $resultProgramIds;
    }
}
