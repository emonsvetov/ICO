<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramAccountHolder extends Model
{
    use HasFactory;
    protected $table = 'award_level';
    protected $fillable = ['program_account_holder_id', 'name'];
}
