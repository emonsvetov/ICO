<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramReports extends Model
{
    protected $table = 'program_reports';

    protected $fillable = [
        'program_id', 'report_id',
    ];
    public function report()
    {
        return $this->belongsTo(ProgramList::class, 'report_id', 'id');
    }
    
    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_reports', 'program_id', 'report_id');
    }
}
