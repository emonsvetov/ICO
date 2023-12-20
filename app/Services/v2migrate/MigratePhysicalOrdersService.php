<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\PhysicalOrder;
use App\Models\Program;

class MigratePhysicalOrdersService extends MigrationService
{
    public $offset = 0;
    public $limit = 1000;
    public $iteration = 0;
    public $count = 0;
    public $organizationCache = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function migrate()  {
        $this->v2db->statement("SET SQL_MODE=''");
        $this->setDebug(true);
        // $this->migrateUserRoleService->migrate();
        $this->migratePhysicalOrders();
    }

    public function migratePhysicalOrders() {
        $physicalOrders = $this->getPhysicalOrdersToMigrate();
        foreach( $physicalOrders as $v2PhysicalOrder) {
            try {
                $this->migratePhysicalOrder($v2PhysicalOrder);
            } catch(Exception $e) {
                print($e->getMessage());
            }
            $this->printf("--------------------------------------------------\n");
        }
        $this->executeV2SQL();
        $this->executeV3SQL();
        // if( count($physicalOrders) >= $this->limit) {
        //     $this->offset = $this->offset + $this->limit;
        //     $this->migratePhysicalOrders();
        // }
    }

    public function migratePhysicalOrder( $v2PhysicalOrder )  {
        if( !isset($this->organizationCache[$v2PhysicalOrder->v3_program_id]))  {
            $this->organizationCache[$v2PhysicalOrder->v3_program_id] = Program::select(['id', 'organization_id'])->find($v2PhysicalOrder->v3_program_id)->organization_id;
        }
        $v3OrderId = PhysicalOrder::insertGetId([
            'organization_id' => $this->organizationCache[$v2PhysicalOrder->v3_program_id],
            'v2_id' => $v2PhysicalOrder->id,
            'user_id' => $v2PhysicalOrder->v3_user_id,
            'program_id' => $v2PhysicalOrder->v3_program_id,
            'ship_to_name' => $v2PhysicalOrder->ship_to_name,
            'line_1' => $v2PhysicalOrder->line_1,
            'line_2' => $v2PhysicalOrder->line_2,
            'zip' => $v2PhysicalOrder->zip,
            'city' => $v2PhysicalOrder->city,
            'country_id' => $v2PhysicalOrder->country_id,
            'state_id' => $v2PhysicalOrder->state_id,
            'state_type_id' => $v2PhysicalOrder->state_type_id,
            'modified_by' => $v2PhysicalOrder->modified_by,
            'created_at' => $v2PhysicalOrder->created_at,
            'updated_at' => $v2PhysicalOrder->updated_at,
            'notes' => $v2PhysicalOrder->notes,
        ]);

        if( $v3OrderId ) {
            $this->printf("PhysicalOrder imported into v3 as \"%d\"\n", $v3OrderId);
            $this->addV2SQL(sprintf("UPDATE `physical_orders` SET `v3_id`=%d WHERE `id`=%d", $v3OrderId, $v2PhysicalOrder->id));
        }
    }

    public function getPhysicalOrdersToMigrate() {
        printf("PhysicalOrders migration iteration:%d\n", ++$this->iteration);
        $sql = sprintf("SELECT o.*, p.v3_program_id, u.v3_user_id FROM `physical_orders` o JOIN programs p on p.account_holder_id = o.program_id JOIN users u ON o.user_id=u.account_holder_id WHERE p.v3_program_id IS NOT NULL AND u.v3_user_id IS NOT NULL AND o.v3_id IS NULL LIMIT {$this->offset},{$this->limit}");
        $this->printf("SQL:%s\n", $sql);
        return $this->v2db->select($sql);
    }
}
