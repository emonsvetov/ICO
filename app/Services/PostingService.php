<?php

namespace App\Services;

use App\Http\Requests\PostingRequest;
use App\Models\Account;
use App\Models\Currency;
use App\Models\Event;
use App\Models\EventType;
use App\Models\FinanceType;
use App\Models\Giftcode;
use App\Models\JournalEventType;
use App\Models\MediumType;
use App\Models\Posting;
use App\Models\Program;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class PostingService
{

    /**
     * @param array $data
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws Exception
     */
    public function create(array $data): array
    {
        $formRequest = new PostingRequest;

        $validator = Validator::make($data, $formRequest->rules());

        if ($validator->fails()) {
            throw new Exception($validator->errors()->toJson());
        }

        $validated = (object)$validator->validated();

        $debitAccount = Account::find($validated->debit_account_id);
        $mediumTypeGiftCode = MediumType::getTypeGiftCodes();

        $validated->medium_info_id = null;
        if ($debitAccount && $debitAccount->medium_type_id == $mediumTypeGiftCode) {
            $mediumInfoId = null;

            if (is_object($validated->medium_info) && ! empty($validated->medium_info)) {
                $mediumInfoId = Giftcode::create($validated->medium_info)->id ?? null;
            } elseif ($data['medium_info_id']) {
                $mediumInfoId = Giftcode::find($data['medium_info_id'])->id ?? null;
            }
            if ($mediumInfoId){
                $validated->medium_info_id = $mediumInfoId;
            } else {
                throw new Exception('Medium info does not exist');
            }
        }

        $debitPosting = Posting::create([
            'journal_event_id' => $validated->journal_event_id,
            'account_id' => $validated->debit_account_id,
            'posting_amount' => $validated->posting_amount,
            'is_credit' => 0,
            'qty' => $validated->qty,
            'medium_info_id' => $validated->medium_info_id,
            'created_at' => now()
        ]);

        $creditPosting = Posting::create([
            'journal_event_id' => $validated->journal_event_id,
            'account_id' => $validated->credit_account_id,
            'posting_amount' => $validated->posting_amount,
            'is_credit' => 1,
            'qty' => $validated->qty,
            'medium_info_id' => $validated->medium_info_id,
            'created_at' => now()
        ]);

        return [
            'debit' => $debitPosting,
            'credit' => $creditPosting,
        ];
    }

}
