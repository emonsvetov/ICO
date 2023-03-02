<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class GoalPlanType extends BaseModel
{
    use HasFactory;
    
    const GOAL_PLAN_TYPE_SALES = 'Sales Goal';//1;
    const GOAL_PLAN_TYPE_PERSONAL = 'Personal Goal';//2;
    const GOAL_PLAN_TYPE_RECOGNITION = 'Recognition Goal';//3;
    const GOAL_PLAN_TYPE_EVENTCOUNT = 'Event Count Goal';//4;
    
    public static function getIdByName( $name, $insert = false, $description = '') : int   {
        $row = self::where('name', $name)->first();
        $id = $row->id ?? null;

        if( !$id && $insert)    {
            $id = self::insertGetId([
                'name'=>$name,
                'description'=>$description
            ]);
        }
        return $id;
    }    
    public static function getIdByTypeSales( $insert = false)   {
        return self::getIdByName(self::GOAL_PLAN_TYPE_SALES, $insert);
    }    
    public static function getIdByTypePersonal( $insert = false)   {
        return self::getIdByName(self::GOAL_PLAN_TYPE_PERSONAL, $insert);
    }
    public static function getIdByTypeRecognition( $insert = false)   {
        return self::getIdByName(self::GOAL_PLAN_TYPE_RECOGNITION, $insert);
    }
    public static function getIdByTypeEventcount( $insert = false)   {
        return self::getIdByName(self::GOAL_PLAN_TYPE_EVENTCOUNT, $insert);
    }
}
