<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\EventXmlData;

class MigrateEventXmlDataService extends MigrationService
{
    public $offset = 0;
    public $limit = 1000;
    public $iteration = 0;
    public $count = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function migrate()  {
        $this->v2db->statement("SET SQL_MODE=''");
        $this->setDebug(true);
        $this->migrateEventXmlData();
    }

    public function migrateEventXmlData() {
        $eventXmlData = $this->getEventXmlDataToMigrate();
        foreach( $eventXmlData as $v2EventXmlDataRow) {
            try {
                $this->migrateEventXmlDataRow($v2EventXmlDataRow);
            } catch(Exception $e) {
                print($e->getMessage());
            }
            $this->printf("--------------------------------------------------\n");
        }
        $this->executeV2SQL();
        $this->executeV3SQL();
        if( $this->iteration > 1 ) exit;
        if( count($eventXmlData) >= $this->limit ) {
            $this->offset = $this->offset + $this->limit;
            $this->migrateEventXmlData();
        }
    }

    public function migrateEventXmlDataRow( $v2EventXmlDataRow )  {
        $v3Id = EventXmlData::insertGetId([
            'v2_id' => $v2EventXmlDataRow->id,
            'awarder_account_holder_id' => $v2EventXmlDataRow->v3_user_id,
            'name' => $v2EventXmlDataRow->name,
            'award_level_name' => $v2EventXmlDataRow->award_level_name,
            'amount_override' => $v2EventXmlDataRow->amount_override,
            'notification_body' => $v2EventXmlDataRow->notification_body,
            'notes' => $v2EventXmlDataRow->notes,
            'referrer' => $v2EventXmlDataRow->referrer,
            'email_template_id' => $v2EventXmlDataRow->email_template_id,
            'event_type_id' => $v2EventXmlDataRow->event_type_id,
            'event_template_id' => $v2EventXmlDataRow->v3_event_id,
            'icon' => $v2EventXmlDataRow->icon,
            'xml' => $v2EventXmlDataRow->xml,
            'award_transaction_id' => $v2EventXmlDataRow->award_transaction_id,
            'lease_number' => $v2EventXmlDataRow->lease_number,
            'token' => $v2EventXmlDataRow->token
        ]);

        if( $v3Id ) {
            $this->printf("EventXmlData imported into v3 as \"%d\"\n", $v3Id);
            $this->addV2SQL(sprintf("UPDATE `event_xml_data` SET `v3_id`=%d WHERE `id`=%d;", $v3Id, $v2EventXmlDataRow->id));
        }
    }

    public function getEventXmlDataToMigrate() {
        printf("EventXmlData migration iteration:%d\n", ++$this->iteration);
        $sql = sprintf("SELECT exd.*, e.v3_event_id, u.v3_user_id  FROM `event_xml_data` exd LEFT JOIN event_templates e ON e.id = exd.event_template_id LEFT JOIN users u ON u.account_holder_id=exd.awarder_account_holder_id where exd.v3_id IS NULL LIMIT {$this->offset},{$this->limit}");
        $this->printf("SQL:%s\n", $sql);
        return $this->v2db->select($sql);
    }
}
