<?php
namespace App\Services\v2migrate;

use App\Models\Domain;
use Exception;

class MigrateDomainsService extends MigrationService
{
    // ?
    const V3_ORGANIZATION_ID = 1;

    public $countNewDomains = 0;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run domains migration.
     *
     * @return array
     * @throws Exception
     */
    public function migrate() {
        try {

            $v2Query = "
                SELECT
				domains.*
                FROM
                    domains
                WHERE
                    domains.access_key > 0
                AND
                    domains.`access_key` NOT IN (SELECT `domains_access_key` FROM (domains_has_owners))
               ";

            try {
                $this->v2db->statement("SET SQL_MODE=''");
                $v2Domains = $this->v2db->select($v2Query);

                if (!blank($v2Domains)) {
                    foreach ($v2Domains as $v2Domain) {
                        $this->migrateDomain($v2Domain);
                    }
                }

            } catch(\Exception $e) {
                throw new Exception( sprintf("Error fetching v2 domains. Error: %s", $e->getMessage()));
            }

            return [
                'success' => TRUE,
                'info' => "was migrated $this->countNewDomains items",
            ];
        } catch(Exception $e) {
            throw new Exception("Error migrating domains. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}");
        }
    }

    /**
     * @param $v2Domain
     * @throws Exception
     */
    public function migrateDomain($v2Domain)
    {
        $v3DomainByID = Domain::where('v2_domain_id', $v2Domain->access_key)->first();
        $v3DomainByName = Domain::where('name', $v2Domain->name)->first();

        if (!blank($v3DomainByName) && blank($v3DomainByID)) {
            throw new Exception("Error migrating domains. Domain $v3DomainByName->name is exists and column v2_domain_id is empty from v3.");
        }

        if (blank($v3DomainByID)) {

            $v3DomainIPs = [];
            $v3DomainData = [
                'organization_id' => self::V3_ORGANIZATION_ID,
                'name' => $v2Domain->name,
                'secret_key' => $v2Domain->secret_key,
                'v2_domain_id' => $v2Domain->access_key
            ];

            $v3Domain = Domain::create($v3DomainData);
            $this->countNewDomains++;
            $v2DomainIPs =  $this->v2db->select("SELECT * FROM `domains_ips` WHERE domain_access_key = {$v2Domain->access_key}");
            if (!blank($v2DomainIPs)) {
                foreach ($v2DomainIPs as $v2DomainIP) {
                    $v3DomainIPs[] = [
                        'domain_id' => $v3Domain->id,
                        'ip_address' => $v2DomainIP->ip_address,
                    ];
                }
                $v3Domain->domain_ips()->createMany($v3DomainIPs);
            }

            // TODO create v2_domain_id in domains v2, update the column?
        }
    }

}
