<?php

namespace App\Services\v2migrate;


class MigrateAvailabeCodesService extends MigrationService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function migrate()
    {
        $res['success'] = true;
        $res['itemsCount'] = 0;
        $v2PrograMmediumInfo = $this->v2db->select("SELECT mi.`id`,mi.`purchase_date`,mi.`redemption_date`,
           mi.`expiration_date`,mi.`hold_until`,mi.`redemption_value`,mi.`cost_basis`,mi.`discount`,mi.`sku_value`,mi.`pin`,
           mi.`redemption_url`,mi.`medium_info_is_test`,mi.`v3_gift_code_id`,UPPER(SUBSTRING(MD5(RAND()), 1, 20)) AS `code`
            FROM
        (SELECT medium_info.* FROM
                medium_info
                JOIN postings ON medium_info.id = postings.medium_info_id
                JOIN accounts a ON postings.account_id = a.id
            WHERE
                medium_info.medium_info_is_test != 1
                AND medium_info.virtual_inventory = 0
                AND medium_info.redemption_date IS NULL
                AND medium_info.purchased_by_v3 = 0
            GROUP BY medium_info.id) AS mi");

        foreach ($v2PrograMmediumInfo as $item){
            //todo
        }
        return [
            'success' => $res['success'],
            'info' => "number of lines ". $res['itemsCount'],
        ];
    }
}
