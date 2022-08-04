<?php

namespace App\Models;

use App\Http\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\WithOrganizationScope;
use App\Models\BaseModel;

class SocialWallPost extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use CreatedUpdatedBy;
    use WithOrganizationScope;

    protected $guarded = [];

    protected static function boot() {
        parent::boot();

        static::deleting(function ($socialWallPost) {
            $socialWallPost->comments()->each->delete();
        });
    }

    public function program()
    {
        return $this->hasOne(Program::class, 'program_id', 'account_holder_id');
    }

    public function eventXmlData()
    {
        return $this->hasOne(EventXmlData::class, 'id', 'event_xml_data_id');
    }

    public function receiver()
    {
        return $this->hasOne(User::class, 'account_holder_id', 'receiver_user_account_holder_id');
    }

    public function sender()
    {
        return $this->hasOne(User::class, 'account_holder_id', 'sender_user_account_holder_id');
    }

    public function getFullTitle()
    {
        $title = '';
        if ($this->eventXmlData) {
            $pretext = mb_strtolower($this->eventXmlData->name)[0] == 'a' ? 'an' : 'a';
            $title = $this->receiver->first_name . ' ' . $this->receiver->last_name . ' has earned ' . $pretext . ' '
                . $this->eventXmlData->name . ' Award';
        }
        return $title;
    }

    public function getFullSender()
    {
        return $this->sender->first_name . ' ' . $this->sender->last_name;
    }

    public function getIconImage()
    {
        $iconImage = '';
        if ($this->eventXmlData) {
            switch ($this->eventXmlData->icon) {
                case 'Award':
                    $iconImage = 'StarIcon';
                    break;
                default:
                    $iconImage = 'StarThreePoints';
                    break;
            }
        }
        return $iconImage;
    }

    public function comments()
    {
        $comments = SocialWallPost::selectRaw(
            'social_wall_posts.*,
            concat(u.first_name, " ", u.last_name) as fromUser,
            DATE_FORMAT(social_wall_posts.created_at,"%m/%d/%Y %H:%i:%s") AS created_at_formated'
            )
            ->where('social_wall_post_id', $this->id)
            ->join('users AS u', 'u.account_holder_id', '=', 'social_wall_posts.sender_user_account_holder_id')
            ->orderBy('social_wall_posts.created_at', 'DESC')
            ->get();
        return $comments;
    }

}
