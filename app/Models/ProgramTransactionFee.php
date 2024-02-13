<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramTransactionFee extends Model
{
    use HasFactory;

    protected $table = 'programs_transaction_fees';
    protected $fillable = [
        "program_id",
        "tier_amount",
        "transaction_fee",
        "updated_at",
    ];

    public function updateTransactionFees($programId, $data)
    {
        ProgramTransactionFee::where('program_id', $programId)->delete();
        foreach ($data as $item) {
            ProgramTransactionFee::create([
                'program_id' => $programId,
                'tier_amount' => $item->tier_amount,
                'transaction_fee' => $item->transaction_fee,
            ]);
        }
    }
}
