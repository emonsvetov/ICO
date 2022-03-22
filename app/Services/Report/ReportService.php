<?php

namespace App\Services\Report;

use App;
// use App\Models\Merchant;
// use App\Models\GiftCode;

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

    public function __construct()
    {
        // Models injected and attached to service class
        // call it like $this->model->{modelName}
        $this->model = (object) [
            'Merchant' => App::make('App\Models\Merchant'),
            'GiftCode' => App::make('App\Models\GiftCode')
        ];
        //Request parameters loaded and attached to service class
        $this->params = (object) [
            'merchant_ids' => request('merchant_ids'),
            'end_date' => request('end_date'),
            'report_type' => request('report_type'),
        ];
    }

    public function getReport( )
    {
        
    }
}