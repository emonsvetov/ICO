<?php
namespace App\Services\v2migrate;

use App\Models\Domain;
use App\Models\Program;
use Exception;

class MigrateDomainsService extends MigrationService
{
    // ?
    const V3_ORGANIZATION_ID = 1;

    public $countCreatedDomains = 0;
    public $countUpdatedDomains = 0;

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
                'info' => "created $this->countCreatedDomains items, updated $this->countUpdatedDomains items",
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
        $v3Domain = Domain::where('v2_domain_id', $v2Domain->access_key)->first();
        $v3DomainByName = Domain::where('name', $v2Domain->name)->first();

        if (!blank($v3DomainByName) && blank($v3Domain)) {
            throw new Exception("Error migrating domains. Domain $v3DomainByName->name is exists and column v2_domain_id is empty from v3.");
        }

        $v3DomainData = [
            'organization_id' => self::V3_ORGANIZATION_ID,
            'name' => $v2Domain->name,
            'secret_key' => $v2Domain->secret_key,
            'v2_domain_id' => $v2Domain->access_key
        ];

        if (blank($v3Domain)) {
            $v3Domain = Domain::create($v3DomainData);
            $this->countCreatedDomains++;
        }
        else {
            $v3Domain->update($v3DomainData);
            $this->countUpdatedDomains++;
        }

        $v2DomainIPs =  $this->v2db->select("SELECT * FROM `domains_ips` WHERE domain_access_key = {$v2Domain->access_key}");
        $v3Domain->domain_ips()->delete();
        if (!blank($v2DomainIPs)) {
            foreach ($v2DomainIPs as $v2DomainIP) {
                $v3DomainIPs[] = [
                    'domain_id' => $v3Domain->id,
                    'ip_address' => $v2DomainIP->ip_address,
                ];
            }
            $v3Domain->domain_ips()->createMany($v3DomainIPs);
        }
    }

    /**
     * Sync domains to a program.
     *
     * @param $v2AccountHolderID
     * @return array
     * @throws Exception
     */
    function syncProgramDomainRelations($v2AccountHolderID) {
        $result = [
            'success' => FALSE,
            'info' => '',
        ];

        $v3Program = Program::where('v2_account_holder_id', $v2AccountHolderID)->first();
        $v3AccountHolderID = $v3Program->account_holder_id ?? NULL;

        // Checking if v3 program is exists.
        if (empty($v3AccountHolderID)) {
            throw new Exception("v3 program or v2_account_holder_id not found.");
        }

        $v3DomainIDs = Domain::all()->pluck('id', 'v2_domain_id')->toArray();

        $v2ProgramDomains = $this->v2db->select(
            sprintf("
            SELECT
				domains.access_key,
				programs.account_holder_id as program_id,
				domains.name,
				domains.secret_key,
				domains.dns_owner,
				domains.monitor_ssl_cert,
				domains.primary,
				domains.sess_match
			FROM
				domains
			INNER JOIN
				domains_has_programs ON domains_has_programs.domains_access_key = domains.access_key
			INNER JOIN
				programs ON domains_has_programs.programs_id = programs.account_holder_id
			WHERE
				programs.account_holder_id = %d
                ", $v2AccountHolderID)
        );

        $v3ProgramDomains = [];

        if (!blank($v2ProgramDomains)) {
            foreach ($v2ProgramDomains as $v2ProgramDomain) {
                if ($v3DomainIDs[$v2ProgramDomain->access_key] ?? FALSE) {
                    $v3ProgramDomains[] = $v3DomainIDs[$v2ProgramDomain->access_key];
                }
                else {
                    throw new Exception("Domain $v2ProgramDomain->name not found in V3. Please run global migrations for migrate a new domains.");
                }
            }
        }

        try {
            $v3Program->domains()->sync($v3ProgramDomains);
            $result['success'] = TRUE;
            $countV3ProgramDomains = count($v3ProgramDomains);
            $result['info'] = "sync $countV3ProgramDomains items";
        } catch (\Exception $exception) {
            throw new Exception("Sync domains to a program is failed.");
        }

        return $result;
    }
}
