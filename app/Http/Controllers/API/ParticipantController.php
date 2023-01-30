<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use App\Services\AwardService;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

class ParticipantController extends Controller
{
    public function myPoints( 
        Organization $organization, 
        Program $program,
        User $user,  
        AwardService $awardService,
        AccountService $accountService
    )
    {
        try {

            $result = $awardService->readListExpireFuture($program, $user);

            // Check for existense of required information
            if( isset($result['expiration']) && sizeof($result['expiration']) > 0)
            {
                // Organize the point expirations for the view
                $points_expirations = [];
                foreach ( $result['expiration'] as $point_expiration ) {
                    // Convert to month, day, year, now so we can group points that expire on the same day
                    $expiration_date = date ( "F d, Y", strtotime ( $point_expiration->expiration ) );
                    // Group all of the point expirations together by date with their sum as the value
                    if (! isset ( $points_expirations [$expiration_date] )) {
                        $points_expirations[$expiration_date] = $point_expiration;
                    } else {
                        $points_expirations[$expiration_date]->amount += $point_expiration->amount;
                    }
                }
                $result['expiration'] = $points_expirations;
		    }

            if( !isset($result['points_redeemed']))
            {
                $result['points_redeemed'] = $accountService->readRedeemedTotalForParticipant ( $program, $user );;
            }

            if( !isset($result['points_expired']))
            {
                $result['points_expired'] = $accountService->readExpiredTotalForParticipant ( $program, $user );;
            }

            if( !isset($result['points_reclaimed']))
            {
                $result['points_reclaimed'] = $accountService->readReclaimedTotalForParticipant ( $program, $user );;
            }

            $points_history_count = $accountService->readEventHistoryCountByProgramByParticipant($program, $user);
            $result['points_history_count'] = $points_history_count;

            $points_history = $accountService->readEventHistoryByProgramByParticipant($program, $user);
            $result['points_history'] = $points_history;

            $points_summary = $accountService->readListEventAwardsForParticipant($program, $user);
            $result['points_summary'] = $points_summary;
            
            $points_summary_for_internal_store = $accountService->readListEventAwardsWithInternalStoreForParticipant($program, $user);
            $result['points_summary_for_internal_store'] = $points_summary_for_internal_store;

            return response($result);
        } catch (\Exception $e) {
            return response([
                'errors' => sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine())
            ], 500);
        }
    }
}
