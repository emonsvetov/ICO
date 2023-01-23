<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Team extends BaseModel
{
    const IMAGE_FIELDS = ['photo'];
    const IMAGE_PATH = 'teams';

    protected $guarded = [];
    use HasFactory;
    use SoftDeletes;
    //protected $table = 'team';
    
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
    /**
     * @param Organization $organization
     * @param Program $program
     * @param array $params
     * @return mixed
     */
    public static function getIndexData(Organization $organization, Program $program, array $params)
    {
        $query = self::where('organization_id', $organization->id)
            ->where('program_id', $program->id);
        return $query->orderBy('name')
            ->get();
    }
    /**
     * @inheritDoc
     */
    public function getImageFields(): array
    {
        return self::IMAGE_FIELDS;
    }
    /**
     * @inheritDoc
     */
    public function getImagePath(): string
    {
        return self::IMAGE_PATH;
    }

}