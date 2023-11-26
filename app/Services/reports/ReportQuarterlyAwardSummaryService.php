<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class ReportQuarterlyAwardSummaryService extends ReportServiceAbstract
{
    	/** setup default parameters */
	protected function setDefaultParams() {
		parent::setDefaultParams ();
		$this->params [self::SQL_GROUP_BY] = array (
				'`p`.`account_holder_id`',
				'`event_xml_data`.`name`',
				'year(`posts`.`created_at`)' 
		);
	
	}

    public function getTable(): array
    {
        if (empty($this->table)) {
            $this->calc();
        }
        // pr($this->table);
        // pr($this->params[self::PAGINATE]);
        if( $this->params[self::PAGINATE] )
        {
            if( isset($this->table['data']) && isset($this->table['total']))    {
                return $this->table; //Already paginated in child class
            }   else {
                return [
                    'data' => $this->table,
                    'total' => $this->query instanceof Builder ? $this->query->count() : count($this->table),
                    'filter' => array("year"=> $this->year)
                ];
            }
        }
        return $this->table;
    }

    protected function getBaseSql(): string
    {
        $sql = "select 
        coalesce(sum((case
                    when (quarter(`posts`.`created_at`) = 1) then `posts`.`posting_amount`
                end)),
                0) AS `Q1_value`,
        coalesce(sum((case
                    when (quarter(`posts`.`created_at`) = 2) then `posts`.`posting_amount`
                end)),
                0) AS `Q2_value`,
        coalesce(sum((case
                    when (quarter(`posts`.`created_at`) = 3) then `posts`.`posting_amount`
                end)),
                0) AS `Q3_value`,
        coalesce(sum((case
                    when (quarter(`posts`.`created_at`) = 4) then `posts`.`posting_amount`
                end)),
                0) AS `Q4_value`,
        coalesce(count((case
                    when (quarter(`posts`.`created_at`) = 1) then `posts`.`posting_amount`
                end)),
                0) AS `Q1_count`,
        coalesce(count((case
                    when (quarter(`posts`.`created_at`) = 2) then `posts`.`posting_amount`
                end)),
                0) AS `Q2_count`,
        coalesce(count((case
                    when (quarter(`posts`.`created_at`) = 3) then `posts`.`posting_amount`
                end)),
                0) AS `Q3_count`,
        coalesce(count((case
                    when (quarter(`posts`.`created_at`) = 4) then `posts`.`posting_amount`
                end)),
                0) AS `Q4_count`,
        coalesce(sum(`posts`.`posting_amount`),
                0) AS `YTD_value`,
        coalesce(count(`posts`.`posting_amount`),
                0) AS `YTD_count`,
        `p`.`account_holder_id` AS `program_id`,
        `p`.`name` AS `program_name`,
        `event_xml_data`.`name` AS `event_name`,
        year(`posts`.`created_at`) AS `year`
        from
                (((((((((((`users` `recipient`
                join `accounts` `a` ON ((`a`.`account_holder_id` = `recipient`.`account_holder_id`)))
                join `account_types` `atypes` ON ((`atypes`.`id` = `a`.`account_type_id`)))
                join `postings` `posts` ON ((`posts`.`account_id` = `a`.`id`)))
                join `journal_events` `je` ON ((`je`.`id` = `posts`.`journal_event_id`)))
                join `journal_event_types` `jet` ON ((`jet`.`id` = `je`.`journal_event_type_id`)))
                join `postings` `program_posting` ON ((`program_posting`.`journal_event_id` = `je`.`id`)))
                join `accounts` `program_accounts` ON ((`program_accounts`.`id` = `program_posting`.`account_id`)))
                join `account_types` `program_account_types` ON (((`program_account_types`.`id` = `program_accounts`.`account_type_id`)
                    and (`program_account_types`.`name` = 'Monies Fees'))))
                join `programs` `p` ON ((`p`.`account_holder_id` = `program_accounts`.`account_holder_id`)))
                left join `event_xml_data` ON ((`event_xml_data`.`id` = `je`.`event_xml_data_id`)))
                left join `users` `awarder` ON ((`awarder`.`account_holder_id` = `event_xml_data`.`awarder_account_holder_id`)))";
        return $sql;
    }


	/** get sql where filter
	 * 
	 * @return array */
	protected function getWhereFilters() {
        $this->year = ( int ) date ( "Y" );
        $this->month = ( int ) date ( "m" );
        if ( $this->params [self::YEAR] )
           $this->year = $this->params [self::YEAR];
		$where = array ();
		$where [] = "(`atypes`.`name` = 'Points Awarded' OR `atypes`.`name` = 'Monies Awarded')";
		$where [] = "(`jet`.`type` = 'Award points to recipient' OR `jet`.`type` = 'Award monies to recipient')";
		$where [] = "`posts`.`is_credit` = 1";
		$where [] = "`p`.account_holder_id IN (" . implode ( ',', $this->params [self::PROGRAMS] ) . ")";
		$where [] = "year(`posts`.`created_at`) = {$this->year}";
		return $where;
	}

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data['data'] = $this->getTable();

        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Q1 value',
                'key' => 'Q1_value'
            ],
            [
                'label' => 'Q1 count',
                'key' => 'Q1_count'
            ],
            [
                'label' => 'Q2 value',
                'key' => 'Q2_value'
            ],
            [
                'label' => 'Q2 count',
                'key' => 'Q2_count'
            ],
            [
                'label' => 'Q3 value',
                'key' => 'Q3_value'
            ],
            [
                'label' => 'Q3 count',
                'key' => 'Q3_count'
            ],
            [
                'label' => 'Q4 value',
                'key' => 'Q4_value'
            ],
            [
                'label' => 'Q4 count',
                'key' => 'Q4_count'
            ],
            [
                'label' => 'YTD value',
                'key' => 'YTD_value'
            ],
            [
                'label' => 'YTD count',
                'key' => 'YTD_count'
            ],
        ];
    }
}