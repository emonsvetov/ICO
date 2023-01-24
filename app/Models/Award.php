<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\JournalEventType;
use App\Models\EventXmlData;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\AccountType;
use App\Models\MediumType;
use App\Models\EventType;
use App\Models\Currency;
use App\Models\Program;
use App\Models\Event;
use App\Models\User;
use mysql_xdevapi\Exception;

class Award extends Model
{
    use HasFactory;

    protected $table = null;

    public static function create( $award, $program, $awarder )
    {
        $event = Event::where('id', $award->event_id)->first();
        $eventType = EventType::where('id', $event->event_type_id)->first();
        $peer2peer = null;
        $referrer = isset($award->referrer) ? $award->referrer : null;
        $lease_number = isset($award->lease_number) ? $award->lease_number : null;

        $event_amount_override = $award->override_cash_value > 0;
        $award_amount = $event_amount_override ? $award->override_cash_value : $event->max_awardable_amount;

        $awardUniqId = generate_unique_id();
        $token = uniqid();
        $event_id = $event->id;
        $event_type_id = $event->event_type_id;
        $eventName = $event->name;
        $awarder_account_holder_id = $awarder->account_holder_id; //user_id
        $notificationBody = $award->message; //TODO
        $notes = $award->notes ?? '';
        $quantity = 1;
        $medium_info_id = 0;

        if( $program->program_is_invoice_for_awards() )  {
            $journal_event_type = 'Award points to recipient';
		} else {
			$journal_event_type = 'Award monies to recipient';
		}

        $escrow_credit_account = $escrow_account = "";

        // echo config('global.account_type_peer2peer_points');
        // return;

        if ( $eventType->isEventTypePeer2Peer() ) {
            $peer2peer = true;
		}
        if ( $eventType->isEventTypePeer2PeerBadge() ) {
            $peer2peer = true;
            $award_amount = 0;
		}
        if ($peer2peer){
            $escrow_account = config('global.account_type_peer2peer_points'); // .", -- escrow account type name
        }


        // echo $escrow_account_type_name;

        $journal_event_type_id = JournalEventType::getIdByType( $journal_event_type );

        $liability = FinanceType::getIdByName('Liability');
        $asset = FinanceType::getIdByName('Asset', true);
        $points = MediumType::getIdByName('Points', true);
        $monies = MediumType::getIdByName('Monies', true);
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);

        //TODO - find appropriate fields for calculating "escrow_credit_account"
        // $program_extra = $this->programs_model->read_extra_program_info ( $receiver_program_id );
		// if ($event_template->only_internal_redeemable && $program_extra->show_internal_store) {
		// 	$journal_event_type = 'Redeemable by Internal Store';
		// 	$escrow_credit_account = $this->write_db->escape ('Award Internal Store Points');
		// } else if ($isPromotional) {
		// 	$journal_event_type = 'Promotional Award Points';
		// 	$escrow_credit_account = $this->write_db->escape ('Award Promotional Points');
		// }

		if($escrow_credit_account != '')    {
			$credit_account_type_name = $escrow_credit_account;
        } else  {
            $credit_account_type_name = 'Points Available';
        }

        $result = null;

        // DB::beginTransaction(); //TODO, Use for rollback on error

        try {

            $users = User::whereIn('id', $award->user_id)->select(['id', 'account_holder_id'])->get();

            foreach( $users as $user)    {
                // print_r( $userId );
                $userId = $user->id;
                $userAccountHolderId = $user->account_holder_id;
                // continue;
                $event_xml_data_id = EventXmlData::insertGetId([
                    'awarder_account_holder_id' => $awarder_account_holder_id,
                    'name' => $eventName,
                    'award_level_name' => 'default', //TODO
                    'amount_override' => $event_amount_override,
                    'notification_body' => $notificationBody,
                    'notes' => $notes,
                    'referrer' => $referrer,
                    'lease_number' => $lease_number,
                    'token' => $token,
                    'email_template_id' => $award->email_template_id,
                    'event_type_id' => $event_type_id,
                    'icon' => 'Award', //TODO
                    'event_template_id' => $event_id, //Event > id
                    'award_transaction_id' => $awardUniqId,
                    'created_at' => now()
                ]);

                $result[$userId]['event_xml_data_id'] = $event_xml_data_id;
                $result[$userId]['userAccountHolderId'] = $userAccountHolderId;

                $journal_event_id = JournalEvent::insertGetId([
                    'journal_event_type_id' => $journal_event_type_id,
                    'event_xml_data_id' => $event_xml_data_id,
                    'notes' => $notes,
                    'prime_account_holder_id' => $awarder_account_holder_id,
                    'created_at' => now()
                ]);//9816692516

                if( $escrow_account != "")    {

                    // pr('Run > escrow_postings');

                    $result[$userId]['escrow_postings'] = Account::postings(
                        $awarder_account_holder_id,
                        $escrow_account,
                        $liability,
                        $points,
                        $program->account_holder_id,
                        $escrow_credit_account,
                        $liability,
                        $points,
                        $journal_event_id,
                        $award_amount,
                        1, //qty
                        null, // medium_info
                        null, // medium_info_id
                        $currency_id
                    );
                }

                if( $escrow_credit_account != '')   {
                    $credit_account_type_name = $escrow_credit_account;
                } else {
                    $credit_account_type_name = 'Points Available';
                }

                // pr('Run > awarder_postings');

                $result[$userId]['awarder_postings'] = Account::postings(
                    $program->account_holder_id,
                    'Monies Due to Owner',
                    $asset,
                    $monies,
                    $program->account_holder_id,
                    $credit_account_type_name,
                    $liability,
                    $points,
                    $journal_event_id,
                    $award_amount,
                    1, //qty
                    null, // medium_info
                    null, // medium_info_id
                    $currency_id
                );


                if( $escrow_credit_account != '') {
                    $credit_account_type_name = $escrow_credit_account;
                }   else {
                    $credit_account_type_name = 'Points Awarded';
                }

                // pr('Run > recepient_postings');

                $result[$userId]['recepient_postings'] = Account::postings(
                    $program->account_holder_id,
                    'Points Available',
                    $liability,
                    $points,
                    $userAccountHolderId,
                    $credit_account_type_name,
                    $liability,
                    $points,
                    $journal_event_id,
                    $award_amount,
                    1, //qty
                    null, // medium_info
                    null, // medium_info_id
                    $currency_id
                );

                // print_r( $userId );
            }
            // return $award->user_id;

            // print_r( $journal_event_type );

            // TODO
            // // Read the award levels assigned to the event
            // $assigned_award_levels = $this->event_templates_model->read_list_of_event_award_level_by_event ( $receiver_program_id, $event_template_id, 0, 99999 );
            // if (! is_array ( $assigned_award_levels ) || count ( $assigned_award_levels ) < 1) {
            // 	throw new RuntimeException ( "This event template cannot be used because it does not have any award levels assigned" );
            // }
            // DB::commit();
        } catch (Exception $e) {
            $result['error'] = "Error while processing awarding. Error:{$e->getMessage()} in line {$e->getLine()}";
            // DB::rollBack();
        }

        return $result;
    }
}
