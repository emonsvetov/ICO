<?php
namespace App\Services\Program;

use App\Models\JournalEvent;
use App\Models\Posting;

class JournalBackdatePostingService
{
    public function backdatePosting( $journal_event_id, $posting_date)   {
        $updated = Posting::where('journal_event_id', $journal_event_id)->update(['created_at' => $posting_date]);
        JournalEvent::find($journal_event_id)->update(['created_at' => $posting_date]);
    }
}