<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramMerchant extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $fillable = ['featured', 'cost_to_program'];
    protected $table = 'program_merchant';
    public $timestamps = true;

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
