<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\User;
use DateTime;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportAwardSummaryAwardsBudgetService extends ReportAwardSummaryAwardsService
{

    protected function setGroupBy(Builder $query): Builder
    {
        return $query;
    }

}
