<?php
namespace App\Models\Traits;

trait WithOrganizationScope
{
    public function scopeWithOrganization( $query, $organization, $force = false )    {
        if( $organization->id != 1 || $force )  { //may be more checks later
            $query->where('organization_id', $organization->id);
        }
    }
}