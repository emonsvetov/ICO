<?php
namespace App\Models\Traits;

trait WithOrganizationScope
{
    public function scopeWithOrganization( $query, $organization )    {
        if( $organization->id != 1 )  { //may be more checks later
            $query->where('organization_id', $organization->id);
        }
    }
}