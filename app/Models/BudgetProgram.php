<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetProgram extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamp = true;
    protected $appends = ['cascading_data'];

    private $cascadingData = [];

    // Accessor for cascading data
    public function getCascadingDataAttribute()
    {
        return $this->cascadingData;
    }

    // Mutator for cascading data
    public function setCascadingDataAttribute($value)
    {
        $this->cascadingData = $value;
    }
    public function budget_types()
    {
        return $this->belongsTo(BudgetType::class, 'budget_type_id');
    }

    public function budget_cascading()
    {
        return $this->hasMany(BudgetCascading::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
