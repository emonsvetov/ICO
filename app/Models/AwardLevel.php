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
}
