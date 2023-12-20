<?php
namespace App\Services\Report;

use Illuminate\Support\Facades\DB;
use App\Services\reports\ReportServiceAbstract as ReportServiceAbstractBase;

class ReportServiceAwardAudit extends ReportServiceAbstractBase
{
	/** Calculate data by date range (timestampFrom|To) */
    protected function getDataDateRange()
    {
        $this->table = $this->calcByDateRange($this->getParams());
    }
    
	/** setup default parameters */
    protected function setDefaultParams()
    {
        parent::setDefaultParams();
        
        $this->params[self::SQL_GROUP_BY] = array('`posts`.`id`');
        // $this->params[self::SQL_ORDER_BY] = array('`posts`.`created_at`');

    } 
    /** basic sql without any filters */
     protected function getBaseSql(): String
    {
        $sql="
        SELECT 
        `p`.`account_holder_id` AS `program_id`,
        `p`.`name` AS `program_name`,
        `recipient`.`account_holder_id` AS `recipient_id`,
        `recipient`.`first_name` AS `recipient_first_name`,
        `recipient`.`last_name` AS `recipient_last_name`,
        `recipient`.`organization_id` AS `recipient_organization_uid`,
        if((`posts`.`posting_amount` is not null),
            `posts`.`posting_amount`,
            0) AS `dollar_value`,
        `posts`.`created_at` AS `posting_timestamp`,
        DATE(`posts`.`created_at`) as `date`,
        MONTH(`posts`.`created_at`) as `month`,
        YEAR(`posts`.`created_at`) as `year`,
        `event_xml_data`.`id` AS `event_xml_data_id`,
        `event_xml_data`.`name` AS `event_name`,
        `event_xml_data`.`referrer` AS `referrer`,
        `event_xml_data`.`notes` AS `notes`,
        `event_xml_data`.`notification_body` AS `notification_body`,
        `event_xml_data`.`xml` AS `xml`,
        `awarder`.`account_holder_id` AS `awarder_id`,
        `awarder`.`first_name` AS `awarder_first_name`,
        `awarder`.`last_name` AS `awarder_last_name`,
        `atypes`.`name` AS `account_type`,
        `je`.`id` AS `journal_event_id`,
        `jet`.`type` AS `journal_event_type`,
        if((`program_posting`.`posting_amount` is not null),
            `program_posting`.`posting_amount`,
            0) AS `transaction_fee`,
         SUM(`posts`.posting_amount) as " . self::FIELD_TOTAL . ",
         COUNT(je.id) as " . self::FIELD_COUNT . "
    from
        (((((((((((((`users` `recipient`
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
        left join `users` `awarder` ON ((`awarder`.`account_holder_id` = `event_xml_data`.`awarder_account_holder_id`)))
        ))
        ";
        return $sql;
    }

   /** get sql where filter
	 *
	 * @return array */
    protected function getWhereFilters()
    {
        $where = array();      
        $where[] = "(`atypes`.`name` = 'Points Awarded' OR `atypes`.`name` = 'Monies Awarded')"; 
        $where[] = "(`jet`.`type` = 'Award points to recipient' OR `jet`.`type` = 'Award monies to recipient' OR `jet`.`type` = 'Reclaim points' OR `jet`.`type` = 'Reclaim monies')";
        // $where[] = "`posts`.`is_credit` = 1";               
        $where[] = "`posts`.created_at >= '{$this->params[self::DATE_BEGIN]}'";
        $where[] = "`posts`.created_at <= '{$this->params[self::DATE_END]}'";
        $where[] = "p.account_holder_id IN (". implode(',', $this->params[self::PROGRAMS]) . ")";
        
        if (isset($this->params[self::AWARD_LEVEL_NAMES]) && count($this->params[self::AWARD_LEVEL_NAMES]) > 0)
        {
           $where[] = "`event_xml_data`.award_level_name IN ('". implode("','", $this->params[self::AWARD_LEVEL_NAMES]) . "')"; 
        }
        
        return $where;
    }

}
