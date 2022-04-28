<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Program extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function children()
    {
        return $this->hasMany(Program::class, 'program_id')->with('children');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function merchants()
    {
        return $this->belongsToMany(Merchant::class, 'program_merchant')
        ->withPivot('featured', 'cost_to_program')->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'program_user')
        // ->withPivot('featured', 'cost_to_program')
        ->withTimestamps();
    }

    public function program_is_invoice_for_awards() {
		if ($this->invoice_for_awards == 1) {
			return true;
		}
        if ( $this->factor_valuation != 1 ) {
            return true;
        }
		return false;
	}
}
