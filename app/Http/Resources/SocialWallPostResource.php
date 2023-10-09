<?php

namespace App\Http\Resources;

use App\Models\SocialWallPost;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialWallPostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var $this SocialWallPost */
        return [
            'id' => $this->id,
            'social_wall_post_type_id' => $this->social_wall_post_type_id,
            'title' => $this->getFullTitle(),
            'from' => $this->getFullSender(),
            'content' => $this->getContent(),
            'icon' => $this->getIconImage(),
            'receiver_user_account_holder_id' => $this->receiver_user_account_holder_id,
            'comments' => $this->comments(),
            'like' => $this->like,
            'like_count' => $this->likesCount,
            // 'children' => $this->children,
            'created_at' => $this->created_at->format('m/d/Y H:i:s'),
            'updated_at' => $this->created_at->format('m/d/Y'),
            'avatar' => $this->sender->avatar ?: $this->avatar,
        ];
    }
}
