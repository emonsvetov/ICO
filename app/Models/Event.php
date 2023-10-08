<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function eventIcon()
    {
        return $this->belongsTo(EventIcon::class, 'event_icon_id');
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class, 'event_type_id');
    }

    public function participant_groups()
    {
        return $this->belongsToMany(ParticipantGroup::class);
    }

    public static function getActiveMilestoneAwardsWithProgram()    {
        return self::select(
            //'id', 'name', 'max_awardable_amount', 'event_icon_id', 'post_to_social_wall', 'message', 'amount_override', 'program_id', 'enable'
        )
        ->whereHas('eventType', function ($query) {
            $query->where( function ($query) {
                $query->orWhere('type', EventType::EVENT_TYPE_MILESTONE_AWARD);
                $query->orWhere('type', EventType::EVENT_TYPE_MILESTONE_BADGE);
            });
        })
        ->where('enable', true)
        ->with(['program' => function($query) {
            // $query->select('id', 'name', 'organization_id');
            $query->where('allow_milestone_award', true);
        }, 'eventIcon'])
        ->get();
    }

    /**
     * @param Organization $organization
     * @param Program $program
     * @param array $params
     * @return mixed
     */
    public static function getIndexData(Organization $organization, Program $program, array $params)
    {
        $query = self::where('organization_id', $organization->id)
            ->where('program_id', $program->id);

        $include_disabled = request()->get('disabled', false);

        if( !$include_disabled )
        {
            $query->where('enable', true);
        }

        if (isset($params['type'])){
            $types = explode(',', $params['type']);
            $typeIds = [];
            foreach ($types as $type){
                $typeIds[] = EventType::getIdByType($type);
            }
            $query->whereIn('event_type_id', $typeIds);
        }
        if (isset($params['except_type'])){
            $types = explode(',', $params['except_type']);
            $typeIds = [];
            foreach ($types as $type){
                $typeIds[] = EventType::getIdByType($type);
            }
            $query->whereNotIn('event_type_id', $typeIds);
        }

        return $query->orderBy('name')
            ->with(['eventIcon', 'eventType'])
            ->get();
    }

    public static function getEvent($id)
    {
        $event = Event::find($id);
        return $event;
    }

    public static function getAllByPrograms(Organization $organization, array $programs)
    {
        return self::where('organization_id', $organization->id)
            ->whereIn('program_id', $programs)
            ->get();
    }

    public static function getCountByPrograms(Organization $organization, array $programs)
    {
        return self::where('organization_id', $organization->id)
            ->whereIn('program_id', $programs)
            ->count();
    }
    public static function read($program_id, $id)
    {
        $goalPlan = Event::where(['program_id'=> $program_id, 'id'=>$id])->first();
        return $goalPlan;
    }

}
