<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ProgramsTransactionFee extends Model
{

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public static function getByProgramAndAmount(int $programId,  float $amount)
    {
        return self::where('program_id', $programId)
            ->where('tier_amount', '<=', $amount)
            ->orderBy('tier_amount', 'DESC')
            ->first();
    }
}
