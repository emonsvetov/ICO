<?php

namespace App\Models;

use App\Models\Traits\WithOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CsvImportSettings extends Model
{
    use HasFactory;
    use WithOrganizationScope;

    protected $guarded = [];
    protected $casts = [
        'setups' => 'array',
        'field_mapping' => 'array',
    ];

    public static function getByOrgIdAndTypeId(int $organizationId, int $typeId)
    {
        $first = self::where('csv_import_type_id', $typeId)
            ->where('organization_id', $organizationId)
            ->first();
        return $first;
    }

    public static function getLastByOrg($organization)
    {
        $first = self::where('organization_id', $organization->id)
            ->orderBy('updated_at', 'DESC')
            ->orderBy('created_at', 'DESC')
            ->first();
        return $first;
    }

}
