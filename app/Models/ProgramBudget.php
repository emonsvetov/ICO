<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramBudget extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'program_budget';


    public static function getAllByProgramsQuery(array $programs)
    {
        return self::whereIn('program_id', $programs);
    }

    public static function getAllByPrograms(array $programs)
    {
        return self::getAllByProgramsQuery($programs)->get();
    }

    public static function getSumByPrograms(array $programs)
    {
        return self::getAllByProgramsQuery($programs)->sum('budget');
    }

}
