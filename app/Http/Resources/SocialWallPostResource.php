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
            'title' => $this->getFullTitle(),
            'from' => $this->getFullSender(),
            'content' => $this->eventXmlData->notification_body ?? null,
            'icon' => $this->getIconImage(),
            'receiver_user_account_holder_id' => $this->receiver_user_account_holder_id,
            'comments' => $this->comments(),
            'created_at' => $this->created_at->format('m/d/Y H:i:s'),
            'updated_at' => $this->created_at->format('m/d/Y'),
        ];
    }
}
