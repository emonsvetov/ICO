<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class MigrateUsersService extends MigrationService
{
    public function migrate()  {
        // \Log::info("MigrationService::migrateUsers()");
        $users = $this->v2db->select("select * from users where birth_month > 0 limit 10");
        foreach( $users as $v2User) {
            $v3User = User::where('email', $v2User->email)->first();
            if( !$v3User ) {
                pr($v2User);
                pr("Empty:" . $v2User->email);
                $v3User = [
                    'first_name' => $v2User->first_name,
                    'last_name' => $v2User->last_name,
                    'email' => $v2User->email,
                    'password' => $v2User->password,
                    'organization_id' => $organization_id, //TODO
                    'user_status_id' => $v2User->user_state_id,
                    'phone' => $v2User->phone,
                    'employee_number' => $v2User->employee_number,
                    'division' => $v2User->division_name,
                    'office_location' => $v2User->office_geo_location,
                    'position_title' => $v2User->position_title,
                    'position_grade_level' => $v2User->position_grade,
                    'supervisor_employee_number' => $v2User->supervisor_employee_number,
                    'last_location' => $v2User->last_location,
                    'update_id' => $v2User->update_id,
                    'created_at' => $v2User->created,
                    'updated_at' => $v2User->updated,
                    'activated' => $v2User->activated,
                    'deactivated' => $v2User->deactivated,
                    'last_login' => $v2User->last_login,
                ];
            }
        }
        echo "Migrate users start!";
    }
}
