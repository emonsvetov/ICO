<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\JournalEventType;
use App\Models\EventXmlData;
use App\Models\JournalEvent;
use App\Models\Program;
use App\Models\Event;

function generate_unique_id($char = 12)
{
    $rand = strtoupper(substr(uniqid(sha1(time())),0,$char));
    return date("ymds") .'-'. $rand;
}

class Award extends Model
{
    use HasFactory;

    protected $table = null;

    public function create( $award, $program, $awarder )
    {
        // return $awarder;
        $event = Event::where('id', $award->event_id)->first();
        $event_amount_override = $award->override_cash_value > 0;
        $award_amount = $event_amount_override ? $award->override_cash_value : $event->max_awardable_amount;

        $awardUniqId = generate_unique_id();
        $token = uniqid();
        $event_id = $event->id;
        $event_type_id = $event->event_type_id;
        $eventName = $event->name;
        $awarder_id = $awarder->id; //user_id
        $notificationBody = $award->message; //TODO
        $notes = $award->notes;

        if( $program->program_is_invoice_for_awards() )  {
            $journal_event_type = 'Award points to recipient';
		} else {
			$journal_event_type = 'Award monies to recipient';
		}

        $journal_event_type_id = JournalEventType::getIdByType( $journal_event_type );

        foreach( $award->user_id as $userId)    {
            print_r( $userId );
            continue;
            $event_xml_data_id = EventXmlData::insertGetId([
                'awarder_id' => $awarder_id,
                'name' => $event_name,
                'award_level_name' => 'default', //TODO
                'amount_override' => $event_amount_override,
                'notification_body' => $notificationBody,
                'notes' => $notes,
                'referrer' => $award->referrer,
                'lease_number' => $award->lease_number,
                'token' => $token,
                'email_template_id' => $award->email_template_id,
                'event_type_id' => $event_type_id,
                'icon' => 'Award', //TODO
                'event_template_id' => $event_id, //Event > id
                'award_transaction_id' => $awardUniqId,
            ]);

            $journal_event_id = JournalEvent::insertGetId([
                'journal_event_type_id' => $journal_event_type_id,
                'event_xml_data_id' => $event_xml_data_id,
                'notes' => $notes,
                'awarder_id' => $awarder_id,

            ]);
            // print_r( $userId );
        }
        return $award->user_id;
        exit;

        // print_r( $journal_event_type );

        // TODO
		// // Read the award levels assigned to the event
		// $assigned_award_levels = $this->event_templates_model->read_list_of_event_award_level_by_event ( $receiver_program_id, $event_template_id, 0, 99999 );
		// if (! is_array ( $assigned_award_levels ) || count ( $assigned_award_levels ) < 1) {
		// 	throw new RuntimeException ( "This event template cannot be used because it does not have any award levels assigned" );
		// }

        return $award_amount;
        // return $request;
    }
}
