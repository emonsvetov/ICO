<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AwardLevel extends Model
{
    use HasFactory;

    protected $fillable = ['program_id','program_account_holder_id', 'name'];

    public function programAccountHolder()
    {
        return $this->belongsTo(ProgramAccountHolder::class);
    }

    public static function readAllAwardLevelsByEvent($programId, $eventId)
    {
        return self::selectRaw('award_levels.*, event_award_level.amount')
            ->join('event_award_level', 'event_award_level.award_level_id', '=', 'award_levels.id')
            ->where('award_levels.program_id', $programId)
            ->where('event_award_level.event_id', $eventId)
            ->get();
    }

}
