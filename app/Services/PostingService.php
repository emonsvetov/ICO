<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Http\Requests\PostingRequest;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Giftcode;
use App\Models\MediumType;
use App\Models\Posting;
use App\Models\Program;

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

	private static function read_list_postings_for_account_between($account_holder_id = 0, $account_type_name = '', $start_date = '', $end_date = '') {

        $limit = request('limit', 15);
        $direction = request('direction', 'asc');

        $query = DB::table('accounts AS a');
        $query->addSelect(
            DB::raw(
                "posts.*,
                posts.posting_amount * posts.qty as total_posting_amount,
                jet.type as journal_event_type,
                exml.name as event_name"
            )
        );

        $query->join('account_types AS at', 'at.id', '=', 'a.account_type_id');
        $query->join('postings AS posts', 'posts.account_id', '=', 'a.id');
        $query->join('journal_events AS je', 'je.id', '=', 'posts.journal_event_id');
        $query->join('journal_event_types AS jet', 'jet.id', '=', 'je.journal_event_type_id');
        $query->leftJoin('event_xml_data AS exml', 'exml.id', '=', 'je.event_xml_data_id');
        $query->where('a.account_holder_id', '=', $account_holder_id);
        $query->where('at.name', '=', $account_type_name);
        $query->where('posts.created_at', '>=', $start_date);
        $query->where('posts.created_at', '<=', $end_date);
        $query->where('posts.posting_amount', '>', 0);
        $result = $query->orderByRaw("posts.id $direction")->paginate( $limit );
		return $result;
	}

    public static function getMoniesAvailablePostings( Program $program  )    {
        $validator = Validator::make(request()->all(), [
            'start_date'=> 'nullable|date_format:Y-m-d',
            'end_date'=> 'nullable|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            throw new \Exception($validator->errors()->toJson());
        }

        $validated = (object)$validator->validated();

        $start_date = !empty($validated->start_date) ? $validated->start_date : date ( 'Y-m-01' );
        $end_date = !empty($validated->end_date) ? $validated->end_date : date ( 'Y-m-t' );

        return self::read_list_postings_for_account_between($program->account_holder_id, AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE, $start_date, $end_date);
    }
}
