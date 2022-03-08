<?php

namespace App\Services;

use Illuminate\Support\Collection;
use App\Models\Merchant;

class ReportService {

    const TYPE_INVENTORY = 'inventory';
    const TYPE_PROGRAM_BUDGET = 'program_budget';

    const FIELD_ID = "id";
	const FIELD_ON_HAND = "on_hand";
	const FIELD_OPTIMAL_VALUES = "optimal_values";
	const FIELD_PERCENT_REMAINING = "percent_remaining";
	const FIELD_COST_BASIS = "cost_basis";

    protected $table = [];
    protected $merchant;

    public function __construct(Merchant $merchant)
    {
        $this->merchant = $merchant;
    }

    public function getReport( $type = '' )
    {
        $type = $type ? $type : request('type');

        if( !$type )    {
            return response(['errors' => 'Invalid Report Request'], 422);
        }

        switch ( $type ) {
            case self::TYPE_INVENTORY:
                $merchants = $this->merchant->getAll( request('merchant_id') );
                
                foreach ( $merchants as $row ) {
                    $this->table [$row->{self::FIELD_ID}] = $row;
                }

                return $this->table;
            break;
            case self::TYPE_PROGRAM_BUDGET:
            case 3:
                $program_ids = explode(',', request( 'program_id' ));
                $year = request( 'year' );
                $result = ProgramBudget::join("months", "program_budget.month_id", "=", "months.id")
                    ->select("budget", "program_id", "months.name AS month")
                    ->whereIn('program_id', $program_ids)
                    ->where('is_notified', "=", 1)
                    ->get()
                    ->groupBy('program_id');
                return response($result);
            break;
        }
    }
}