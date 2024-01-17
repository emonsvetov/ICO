<?php
namespace App\Services\reports;

class ReportSumProgramCostOfGiftCodesRedeemedService extends ReportServiceAbstract
{
	const FIELD_COST_BASIS = "cost_basis";

	const FIELD_PREMIUM = "premium";

	/** setup default parameters */
	protected function setDefaultParams() {
		parent::setDefaultParams ();
		$this->params [self::SQL_GROUP_BY] = array (
				'a.account_holder_id',
				'atypes.id',
				'jet.id',
				'jet.type',
				'atypes.name',
		);

	}

	/** Calculate data by date range (timestampFrom|To) */
	protected function getDataDateRange() {
		$data = $this->calcByDateRange ( $this->getParams () );
		// Organize the data table so it is easier to look stuff up later
		foreach ( $data as $i => $row ) {
			$this->table [$row->{$this::FIELD_ID}] [$row->{self::FIELD_ACCOUNT_TYPE}] [$row->{self::FIELD_JOURNAL_EVENT_TYPE}] [self::FIELD_COST_BASIS] = $row->{self::FIELD_COST_BASIS};
			$this->table [$row->{$this::FIELD_ID}] [$row->{self::FIELD_ACCOUNT_TYPE}] [$row->{self::FIELD_JOURNAL_EVENT_TYPE}] [self::FIELD_PREMIUM] = $row->{self::FIELD_PREMIUM};
            unset($this->table[$i]);
		}
	}

	/** basic sql without any filters */
	protected function getBaseSql(): string {
		$sql = "
                SELECT
                    COALESCE(SUM(mi.cost_basis), 0) AS " . self::FIELD_COST_BASIS . ",
                    COALESCE(SUM(mi.redemption_value - mi.sku_value), 0) AS " . self::FIELD_PREMIUM . ",
                                jet.type AS " . self::FIELD_JOURNAL_EVENT_TYPE . ",
                                a.account_holder_id AS " . self::FIELD_ID . ",
                                atypes.name AS " . self::FIELD_ACCOUNT_TYPE . "
                    FROM
                        postings AS posts
                            LEFT JOIN
                        accounts a ON posts.account_id = a.id
                            LEFT JOIN
                        account_types atypes ON atypes.id = a.account_type_id
                            INNER JOIN
                        journal_events je ON je.id = posts.journal_event_id
                            INNER JOIN
						journal_event_types jet ON jet.id = je.journal_event_type_id
                            INNER JOIN
                        postings merchant_posts ON merchant_posts.journal_event_id = je.id
                            INNER JOIN
                        medium_info mi ON mi.id = merchant_posts.medium_info_id
                            INNER JOIN
                        accounts merchant_account ON merchant_account.id = merchant_posts.account_id
                            INNER JOIN
                        merchants m ON m.account_holder_id = merchant_account.account_holder_id";
		return $sql;

	}

	/** get sql where filter
	 *
	 * @return array */
	protected function getWhereFilters() {
		$where = array ();
		$where [] = "posts.is_credit = 1";
		if (isset ( $this->params [self::ACCOUNT_TYPES] ) && count ( $this->params [self::ACCOUNT_TYPES] ) > 0) {
			$where [] = "atypes.name IN ('" . implode ( "','", $this->params [self::ACCOUNT_TYPES] ) . "')";
		}
		if (isset ( $this->params [self::JOURNAL_EVENT_TYPES] ) && count ( $this->params [self::JOURNAL_EVENT_TYPES] ) > 0) {
			$where [] = "jet.type IN ('" . implode ( "','", $this->params [self::JOURNAL_EVENT_TYPES] ) . "')";
		}
		if (isset ( $this->params [self::PROGRAMS] ) && count ( $this->params [self::PROGRAMS] ) > 0) {
			$where [] = "a.account_holder_id IN ('" . implode ( "','", $this->params [self::PROGRAMS] ) . "')";
		}
		$where [] = "posts.created_at >= '{$this->params[self::DATE_BEGIN]}'";
		$where [] = "posts.created_at <= '{$this->params[self::DATE_END]}'";
		return $where;

	}
}
