<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetType extends Model
{
    use HasFactory;
	protected $guarded = [];
    public $timestamp = true;

     /**
     * Get a list of all budget types.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function budgetTypeList()
    {
        return self::all();
    }
    
}
