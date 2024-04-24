<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PositionLevel extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];
    public $timestamp = true;

    public function program() {
        $this->belongsTo(Program::class);
    }

    public function users() 
    {
        return $this->belongsToMany(User::class, 'position_level_has_users')
        ->withTimestamps();
    }

}
