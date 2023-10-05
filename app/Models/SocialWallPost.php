<?php

namespace App\Models;

use App\Http\Traits\CreatedUpdatedBy;
use Illuminate\Database\Eloquent\Builder;
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

    protected $attributes = [
        'like'=>['[]'],
    ];

    protected $casts = [
        'like' => 'json',
    ];

    public function program()
    {
        return $this->hasOne(Program::class, 'program_id', 'account_holder_id');
    }

    public function eventXmlData()
    {
        return $this->hasOne(EventXmlData::class, 'id', 'event_xml_data_id');
    }

    public function socialWallPostType()
    {
        return $this->belongsTo(SocialWallPostType::class);
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

    public function getContent()
    {
        $content = '';
        if( SocialWallPostType::isTypeEvent( $this->socialWallPostType->type ) )  {
            $content = $this->eventXmlData->notification_body;
        } else {
            $content = $this->comment;
        }
        return $content;
    }

    public function getIconImage()
    {
        if( $this->eventXmlData && $this->eventXmlData->event && $this->eventXmlData->event->exists() )    {
            $eventIcon = $this->eventXmlData->event->eventIcon->path;
            return $eventIcon;
        }
    }

    public function children()
    {
        return $this->hasMany(SocialWallPost::class, 'social_wall_post_id')->with(['children']);
    }

    public function comments( $parent_id = null)
    {
        $parent_id = $parent_id??$this->id;
        $comments = SocialWallPost::selectRaw(
            'social_wall_posts.*,
            concat(u.first_name, " ", u.last_name) as fromUser,
            DATE_FORMAT(social_wall_posts.created_at,"%m/%d/%Y %H:%i:%s") AS created_at_formated,
            u.avatar',
            )
            ->where('social_wall_post_id', $parent_id)
            ->join('users AS u', 'u.account_holder_id', '=', 'social_wall_posts.sender_user_account_holder_id')
            ->orderBy('social_wall_posts.created_at', 'DESC')
            ->get();

        if( $comments ) {
            foreach( $comments as &$comment )    {
                $comment->comments = $this->comments( $comment->id );
            }
        }
        return $comments;
    }

    public static function getAllByProgramsQuery(Organization $organization, array $programs)
    {
        return self::where('organization_id', $organization->id)
            ->whereIn('program_id', $programs);
    }

    public static function getAllByPrograms(Organization $organization, array $programs)
    {
        return self::getAllByProgramsQuery($organization, $programs)->get();
    }

    public static function getCountByPrograms(Organization $organization, array $programs)
    {
        return self::getAllByProgramsQuery($organization, $programs)->count();
    }

}
