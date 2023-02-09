<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use App\Models\ReferralNotificationRecipient;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ReferralNotificationRecipientPolicy
{
    use HandlesAuthorization;

    private function __authCheck($authUser, $organization, $program): bool
    {
        if( $organization->id != $authUser->organization_id ) return false;
        if( $organization->id != $program->organization_id) return false;
        return true;
    }
      /**
     * Determine whether the user can create the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @return mixed
     */
	 public function create(User $authUser, Organization $organization, Program $program)
    {
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('referral-create');
    }
     /**
     * Determine whether the user can view all records of the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @return mixed
     */
    public function viewAny(User $authUser, Organization $organization, Program $program)
    {
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('referral-list');
    }
        /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\ReferralNotificationRecipient  $referralnotificationrecipient
     * @return mixed
     */
    public function view(User $authUser, Organization $organization, Program $program)
    {
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('referral-view');
    }
       /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\ReferralNotificationRecipient  $referralnotificationrecipient
     * @return mixed
     */
    public function update(User $authUser, Organization $organization, Program $program, ReferralNotificationRecipient $referral_notification_recipient)
    {
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('referral-update');
    }
     /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $authuser
     * @param  \App\Models\Organization  $organization
     * @param  \App\Models\Program  $program
     * @param  \App\Models\ReferralNotificationRecipient  $referralnotificationrecipient
     * @return mixed
     */
    public function delete(User $authUser, Organization $organization, Program $program,ReferralNotificationRecipient $referralnotificationrecipient)
    {
        if ( !$this->__authCheck($authUser, $organization, $program ) )
        {
            return false;
        }
        
        if($authUser->isAdmin()) return true;

        return $authUser->isManagerToProgram( $program ) || $authUser->can('referral-delete');
    }
}
