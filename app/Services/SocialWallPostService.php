<?php

namespace App\Services;

use App\Http\Resources\SocialWallPostResource;
use App\Models\SocialWallPost;
use App\Models\SocialWallPostType;

class SocialWallPostService
{

    public function create(array $data): ?SocialWallPost
    {
        $resultObject = SocialWallPost::create($data);

        return $resultObject;

    }

    public function getIndexData(array $request): array
    {
        $data = SocialWallPost::where('social_wall_post_type_id', SocialWallPostType::getEventTypeId())
            ->orderBy('created_at', 'DESC')
            ->get();

        return [
            'data' => SocialWallPostResource::collection($data),
            'total' => $data->count(),
        ];
    }

}
