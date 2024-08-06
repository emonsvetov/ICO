<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\PositionLevel;
use App\Models\Program;

class UniqueTitle implements Rule
{
    protected $programId;

    public function __construct($programId)
    {
        $this->programId = $programId;
    }

    public function passes($attribute, $value)
    {
        $program = new Program();
        $program_id = $program->get_top_level_program_id($this->programId);
        return !PositionLevel::withTrashed()
            ->where('title', $value)
            ->where('program_id', $program_id)
            ->exists();
    }

    public function message()
    {
        return 'The title already exists.';
    }
}
