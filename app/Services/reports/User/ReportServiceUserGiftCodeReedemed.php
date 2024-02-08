<?php
namespace App\Services\reports\User;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\Program;
use App\Services\reports\ReportServiceAbstract as ReportServiceAbstractBase;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportServiceUserGiftCodeReedemed extends ReportServiceAbstractBase
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $query = DB::table('medium_info');
        $query->join('merchants', 'merchants.id', '=', 'medium_info.redeemed_merchant_id');

        $query->selectRaw("
            `medium_info`.*
            , `merchants`.name
            , `medium_info`.sku_value as amount
            , `medium_info`.code as code
        ");
        //  / `medium_info`.factor_valuation
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->where('medium_info.redeemed_user_id', '=', $this->params[self::USER_ID]);

        if (blank($this->params[self::PROGRAMS])) {
            $programs = blank($this->params[self::PROGRAM_ID]) ? [] : [$this->params[self::PROGRAM_ID]];
        }
        else {
            $programIDs = explode(',', $this->params[self::PROGRAMS]);
            $programs = Program::whereIn('account_holder_id', $programIDs)->get()->pluck('id')->toArray();
        }

        $query->whereIn('medium_info.redeemed_program_id', $programs);
        return $query;
    }

    protected function setDefaultParams() {
        parent::setDefaultParams ();
        $this->params[self::PROGRAMS] = request()->get('programs');
    }

}
