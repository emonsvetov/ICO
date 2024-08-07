<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainProgram extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'domain_program';

    public static function findByProgramId($programId){
        return self::where('program_id', $programId);
    }

    public static function findByProgramIdAndDomainId($programId, $domainId){
        return self::where('domain_id', $domainId)->where('program_id', $programId);
    }
}
