<?php
namespace App\Services\Report;
use App\Services\Report\ReportServiceAbstractBase;

class ReportServiceSumProgramCostOfGiftCodesRedeemed extends ReportServiceAbstractBase
{

	const FIELD_COST_BASIS = "cost_basis";

	const FIELD_PREMIUM = "premium";

	/** setup default parameters */
	protected function setDefaultParams() {
		parent::setDefaultParams ();
		$this->params [self::SQL_GROUP_BY] = array (
				'a.account_holder_id',
				'atypes.id',
				'jet.id' 
		);
	
	}

	/** Calculate data by date range (timestampFrom|To) */
	protected function getDataDateRange() {
		$data = $this->calcByDateRange ( $this->getParams () );
		// Organize the data table so it is easier to look stuff up later
		foreach ( $data as $row ) {
			$this->table [$row->{$this::FIELD_ID}] [$row->{self::FIELD_ACCOUNT_TYPE}] [$row->{self::FIELD_JOURNAL_EVENT_TYPE}] [self::FIELD_COST_BASIS] = $row->{self::FIELD_COST_BASIS};
			$this->table [$row->{$this::FIELD_ID}] [$row->{self::FIELD_ACCOUNT_TYPE}] [$row->{self::FIELD_JOURNAL_EVENT_TYPE}] [self::FIELD_PREMIUM] = $row->{self::FIELD_PREMIUM};
		}
	
	}

	/** basic sql without any filters */
	protected function getBaseSql() {
		$sql = "
                SELECT 
                    COALESCE(SUM(mi.cost_basis), 0) AS " . self::FIELD_COST_BASIS . ",
                    COALESCE(SUM(mi.redemption_value - mi.sku_value), 0) AS " . self::FIELD_PREMIUM . ",
                                jet.type AS " . self::FIELD_JOURNAL_EVENT_TYPE . ",
                                a.account_holder_id AS " . self::FIELD_ID . ",
                                atypes.account_type_name AS " . self::FIELD_ACCOUNT_TYPE . "
                    FROM
                        " . POSTINGS . " AS posts
                            LEFT JOIN
                        " . ACCOUNTS . " a ON posts.account_id = a.id
                            LEFT JOIN
                        " . ACCOUNT_TYPES . " atypes ON atypes.id = a.account_type_id
                            INNER JOIN
                        " . JOURNAL_EVENTS . " je ON je.id = posts.journal_event_id
                            INNER JOIN
                        " . JOURNAL_EVENT_TYPES . " jet ON jet.id = je.journal_event_type_id
                            INNER JOIN
                        " . POSTINGS . " merchant_posts ON merchant_posts.journal_event_id = je.id
                            INNER JOIN
                        " . MEDIUM_INFO . " mi ON mi.id = merchant_posts.medium_info_id
                            INNER JOIN
                        " . ACCOUNTS . " merchant_account ON merchant_account.id = merchant_posts.account_id
                            INNER JOIN
                        " . MERCHANTS . " m ON m.account_holder_id = merchant_account.account_holder_id";
		return $sql;
	
	}

	/** get sql where filter
	 * 
	 * @return array */
	protected function getWhereFilters() {
		$where = array ();
		$where [] = "posts.is_credit = 1";
		if (isset ( $this->params [self::ACCOUNT_TYPES] ) && count ( $this->params [self::ACCOUNT_TYPES] ) > 0) {
			$where [] = "atypes.account_type_name IN ('" . implode ( "','", $this->params [self::ACCOUNT_TYPES] ) . "')";
		}
		if (isset ( $this->params [self::JOURNAL_EVENT_TYPES] ) && count ( $this->params [self::JOURNAL_EVENT_TYPES] ) > 0) {
			$where [] = "jet.type IN ('" . implode ( "','", $this->params [self::JOURNAL_EVENT_TYPES] ) . "')";
		}
		if (isset ( $this->params [self::PROGRAMS] ) && count ( $this->params [self::PROGRAMS] ) > 0) {
			$where [] = "a.account_holder_id IN ('" . implode ( "','", $this->params [self::PROGRAMS] ) . "')";
		}
		$where [] = "posts.posting_timestamp >= '{$this->params[self::DATE_BEGIN]}'";
		$where [] = "posts.posting_timestamp <= '{$this->params[self::DATE_END]}'";
		return $where;
	
	}
}
