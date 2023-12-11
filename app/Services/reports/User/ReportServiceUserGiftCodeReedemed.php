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
            , upper(substring(MD5(RAND()), 1, 20)) as `code`
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
        $query->where('medium_info.redeemed_program_id', '=', $this->params[self::PROGRAM_ID]);
        return $query;
    }

}
