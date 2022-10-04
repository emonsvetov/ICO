<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class JournalEventType extends Model
{
    use HasFactory;
    protected $guarded = [];

    const JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT = 'Award points to recipient';
    const JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT = 'Award monies to recipient';
    const JOURNAL_EVENT_TYPES_REFUND_PROGRAM_FOR_MONIES_TRANSACTION_FEE = 'Refund program for monies transaction fee';
    const JOURNAL_EVENT_TYPES_REFUND_PROGRAM_FOR_POINTS_TRANSACTION_FEE = 'Refund program for points transaction fee';
    const JOURNAL_EVENT_TYPES_CHARGE_FIXED_FEE = 'Charge program for fixed fee';
    const JOURNAL_EVENT_TYPES_CHARGE_SETUP_FEE = 'Charge setup fee to program';
    const JOURNAL_EVENT_TYPES_CHARGE_MONTHLY_USAGE_FEE = 'Charge program for monthly usage fee';
    const JOURNAL_EVENT_TYPES_CHARGE_ADMIN_FEE = 'Charge program for admin fee';
    const JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_CONVENIENCE_FEE = 'Program pays for convenience fee';
    const JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES = 'Redeem points for gift codes';
    const JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING = 'Redeem points for international shopping';
    const JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES = 'Redeem monies for gift codes';
    const JOURNAL_EVENT_TYPES_EXPIRE_POINTS = 'Expire points';
    const JOURNAL_EVENT_TYPES_EXPIRE_MONIES = 'Expire monies';
    const JOURNAL_EVENT_TYPES_DEACTIVATE_POINTS = 'Deactivate points';
    const JOURNAL_EVENT_TYPES_DEACTIVATE_MONIES = 'Deactivate monies';
    const JOURNAL_EVENT_TYPES_RECLAIM_POINTS = 'Reclaim points';
    const JOURNAL_EVENT_TYPES_RECLAIM_MONIES = 'Reclaim monies';
    const JOURNAL_EVENT_TYPES_PROGRAM_TOTAL_SPEND_REBATE = 'Program total spend rebate';
    const JOURNAL_EVENT_TYPES_REDEEMABLE_ON_INTERNAL_STORE = 'Redeemable by Internal Store';
    const JOURNAL_EVENT_TYPES_PROMOTIONAL_AWARD = 'Promotional Award Points';
    const JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_POINTS = 'Reversal program pays for points';
    const JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING = 'Reversal program pays for monies pending';
    const JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS = 'Program pays for points';
    const JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS_TRANSACTION_FEE = 'Program pays for points transaction fee';
    const JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_ADMIN_FEE = 'Program pays for admin fee';
    const JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONTHLY_USAGE_FEE = 'Program pays for monthly usage fee';
    const JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_SETUP_FEE = 'Program pays for setup fee';
    const JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_FIXED_FEE = 'Program pays for fixed fee';
    const JOURNAL_EVENT_TYPES_CHARGE_MONIES_PENDING = 'Charge program for monies pending';
    const JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING = 'Program pays for monies pending';
    const JOURNAL_EVENT_TYPES_CHARGE_DEPOSIT_FEE = 'Charge program for deposit fee';
    const JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE = 'Program pays for deposit fee';
    const JOURNAL_EVENT_TYPES_REFUND_PROGRAM_FOR_MONIES_PENDING = 'Program refunds for monies pending';
    const JOURNAL_EVENT_TYPES_CHARGE_CONVENIENCE_FEE = 'Charge program for convenience fee';


    public static function getIdByType( $type, $insert = false ) {

        $first = self::where('type', $type)->first();
        if( $first )    {
            return $first->id;
        }
        if( $insert )    {
            return self::insertGetId([
                'type'=>$type
            ]);
        }
    }
}