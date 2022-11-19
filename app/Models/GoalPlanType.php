<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoalPlanType extends BaseModel
{
    use HasFactory;
    /*public static function getNameById( $id ) {
        $first = self::where('id', $id)->first();
        if( $first) return $first->name;
        return;
    }*/
    const GOAL_PLAN_TYPE_SALES = 'Sales Goal';//1;
    const GOAL_PLAN_TYPE_PERSONAL = 'Personal Goal';//2;
    const GOAL_PLAN_TYPE_RECOGNITION = 'Recognition Goal';//3;
    const GOAL_PLAN_TYPE_EVENTCOUNT = 'Event Count Goal';//4;
    
    public static function getIdByName( $name, $insert = false, $description = '')   {
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
