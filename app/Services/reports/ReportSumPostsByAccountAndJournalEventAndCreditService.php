<?php
namespace App\Services\reports;

class ReportSumPostsByAccountAndJournalEventAndCreditService extends ReportServiceAbstract
{

	protected $is_credit = 1;

	public function __construct($params) {
		parent::__construct ( $params );
		if (isset ( $params [self::IS_CREDIT] )) {
			$this->is_credit = $params [self::IS_CREDIT];
		}
	}

	/** setup default parameters */
	protected function setDefaultParams() {
		$this->params [self::IS_CREDIT] = $this->is_credit;
		if (! isset ( $this->params [self::SQL_GROUP_BY] ) || ! is_array ( $this->params [self::SQL_GROUP_BY] ) || count ( $this->params [self::SQL_GROUP_BY] ) < 1) {
			$this->params [self::SQL_GROUP_BY] = array (
                'a.account_holder_id',
                'atypes.id',
                'jet.id'
			);
		}
	}

	/** Calculate data by date range (timestampFrom|To) */
	protected function getDataDateRange() {
		$data = $this->calcByDateRange ( $this->getParams () );

        $this->table = [];
		if (in_array ( self::FIELD_MONTH, $this->params [self::SQL_GROUP_BY] )) {
			foreach ( $data as $row ) {
				$this->table [$row->{$this::FIELD_ID}] [$row->{self::FIELD_ACCOUNT_TYPE}] [$row->{self::FIELD_JOURNAL_EVENT_TYPE}] [$row->{self::FIELD_MONTH}] = $row->{self::FIELD_VALUE};
			}
		} else {
			// Organize the data table so it is easier to look stuff up later
			foreach ( $data as $row ) {
				$this->table [$row->{$this::FIELD_ID}] [$row->{self::FIELD_ACCOUNT_TYPE}] [$row->{self::FIELD_JOURNAL_EVENT_TYPE}] = $row->{self::FIELD_VALUE};
			}
            pr($this->table);
		}
        return $this->table;
	}

	/** basic sql without any filters */
	protected function getBaseSql(): string
    {
		$sql = "
				SELECT
					COALESCE(SUM(posts.posting_amount * posts.qty), 0) AS " . self::FIELD_VALUE . ",
					jet.type AS " . self::FIELD_JOURNAL_EVENT_TYPE . ",
					a.account_holder_id AS " . self::FIELD_ID . ",
					atypes.name AS " . self::FIELD_ACCOUNT_TYPE . ",
					MONTH(`posts`.created_at) as " . self::FIELD_MONTH . "
				FROM
					accounts a
					INNER JOIN account_types atypes ON atypes.id = a.account_type_id
					INNER JOIN postings posts ON posts.account_id = a.id
					INNER JOIN journal_events je ON je.id = posts.journal_event_id
					INNER JOIN journal_event_types jet ON jet.id = je.journal_event_type_id";
		return $sql;

	}

    // protected function getBaseQuery(): Builder
    // {
    //     $sql = $this->getBaseSql();
    //     dd($sql);
    //     return DB::select( $this->getBaseSql() );
    // }

	/** get sql where filter
	 *
	 * @return array */
	protected function getWhereFilters() {
		$where = array ();
		if (isset ( $this->params [self::DATE_FROM] ) && $this->params [self::DATE_FROM] != '') {
			$where [] = "posts.created_at >= '{$this->params[self::DATE_FROM]}'";
		}
		if (isset ( $this->params [self::DATE_TO] ) && $this->params [self::DATE_TO] != '') {
			$where [] = "posts.created_at <= '{$this->params[self::DATE_TO]}'";
		}
        // dd($this->params);
		$where [] = "a.account_holder_id IN (" . implode ( ',', $this->params [self::PROGRAM_ACCOUNT_HOLDER_IDS] ) . ")";
		$where [] = "posts.is_credit = {$this->is_credit}";
		if (isset ( $this->params [self::ACCOUNT_TYPES] ) && count ( $this->params [self::ACCOUNT_TYPES] ) > 0) {
			$where [] = "atypes.name IN ('" . implode ( "','", $this->params [self::ACCOUNT_TYPES] ) . "')";
		}
		if (isset ( $this->params [self::JOURNAL_EVENT_TYPES] ) && count ( $this->params [self::JOURNAL_EVENT_TYPES] ) > 0) {
			$where [] = "jet.type IN ('" . implode ( "','", $this->params [self::JOURNAL_EVENT_TYPES] ) . "')";
		}
		return $where;

	}
}
