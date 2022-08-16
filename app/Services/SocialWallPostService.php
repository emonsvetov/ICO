<?php

namespace App\Services;

use App\Http\Resources\SocialWallPostResource;
use App\Models\Organization;
use App\Models\Program;
use App\Models\SocialWallPost;
use App\Models\SocialWallPostType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class SocialWallPostService
{
    private ProgramService $programService;
    private UserService $userService;

    public function __construct(ProgramService $programService, UserService $userService)
    {
        $this->programService = $programService;
        $this->userService = $userService;
    }

    public function create(array $data): ?SocialWallPost
    {
        $resultObject = SocialWallPost::create($data);

        return $resultObject;

    }

    public function getIndexData(Organization $organization, Program $program, User $user, array $request): array
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

        $data = SocialWallPost::where('social_wall_post_type_id', SocialWallPostType::getEventTypeId())
            ->where('organization_id', $organization->id)
            ->whereIn('program_id', $hierarchy)
            ->orderBy('created_at', 'DESC')
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

        $programs = $this->userService->getParticipantPrograms($user);
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
