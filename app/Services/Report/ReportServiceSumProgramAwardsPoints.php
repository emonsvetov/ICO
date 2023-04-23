<?php
namespace App\Services\Report;

use Illuminate\Support\Facades\DB;
use App\Services\reports\ReportServiceAbstract as ReportServiceAbstractBase;

class ReportServiceSumProgramAwardsPoints extends ReportServiceAbstractBase
{
	/** setup default parameters */
	protected function setDefaultParams() {
        DB::statement("SET SQL_MODE=''");
		parent::setDefaultParams ();
		if (! isset ( $this->params [self::SQL_GROUP_BY] ) || ! is_array ( $this->params [self::SQL_GROUP_BY] ) || count ( $this->params [self::SQL_GROUP_BY] ) < 1) {
			$this->params [self::SQL_GROUP_BY] = array (
					'p.account_holder_id',
					'atypes.name',
					'jet.type'
			);
		}

	}

	/** Calculate data by date range (timestampFrom|To) */
	protected function getDataDateRange() {
		$data = $this->calcByDateRange ( $this->getParams () );
		// Organize the data table so it is easier to look stuff up later
		if (in_array ( self::FIELD_MONTH, $this->params [self::SQL_GROUP_BY] )) {
			foreach ( $data as $i => $row ) {
				$this->table [$row->{$this::FIELD_ID}] [$row->{self::FIELD_ACCOUNT_TYPE}] [$row->{self::FIELD_JOURNAL_EVENT_TYPE}] [$row->{self::FIELD_MONTH}] = $row->{self::FIELD_VALUE};
                unset($this->table[$i]);
			}
		} else {
			foreach ( $data as $i => $row ) {
				$this->table [$row->{$this::FIELD_ID}] [$row->{self::FIELD_ACCOUNT_TYPE}] [$row->{self::FIELD_JOURNAL_EVENT_TYPE}] = $row->{self::FIELD_VALUE};
                unset($this->table[$i]);
			}
		}

	}

	/** basic sql without any filters */
	protected function getBaseSql(): string
    {
		$sql = "
                SELECT
                    COALESCE(SUM(posts.posting_amount), 0) AS " . self::FIELD_VALUE . ",
                    jet.type AS " . self::FIELD_JOURNAL_EVENT_TYPE . ",
                    p.account_holder_id AS " . self::FIELD_ID . ",
                    atypes.name AS " . self::FIELD_ACCOUNT_TYPE . ",
                    MONTH(`posts`.created_at) as " . self::FIELD_MONTH . "
                from
                    (((((((((`users` `recipient`
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
                    ";
		return $sql;

	}

	/** get sql where filter
	 *
	 * @return array */
	protected function getWhereFilters() {
		$where = array ();
		$where [] = "(`atypes`.`name` = 'Points Awarded')";
		$where [] = "(`jet`.`type` = 'Award points to recipient')";
		$where [] = "`posts`.`is_credit` = 1";
		if (isset ( $this->params [self::DATE_BEGIN] ) && $this->params [self::DATE_BEGIN] != '') {
			$where [] = "posts.created_at >= '{$this->params[self::DATE_BEGIN]}'";
		}
		if (isset ( $this->params [self::DATE_END] ) && $this->params [self::DATE_END] != '') {
			$where [] = "posts.created_at <= '{$this->params[self::DATE_END]}'";
		}
		if (is_array ( $this->params [self::PROGRAMS] ) && count ( $this->params [self::PROGRAMS] ) > 0) {
			$where [] = "p.account_holder_id IN (" . implode ( ',', $this->params [self::PROGRAMS] ) . ")";
		}
		return $where;

	}
}
