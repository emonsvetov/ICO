<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class ReportParticipantAccountSummaryService extends ReportServiceAbstract
{
    const FIELD_TOTAL_DOLLAR_COST_BASIS = 'total_cost_basis';

    const FIELD_TOTAL_DOLLAR_PREMIUM = 'total_premium';

    const FIELD_TOTAL_DOLLAR_REDEMPTION_VALUE = 'total_redemption_value';

    const FIELD_AVG_DISCOUNT_PERCENT = 'avg_discount_percent';

    const FIELD_PERCENT_TOTAL_REDEMPTION_VALUE = 'percent_total_redemption_value';

    const FIELD_PERCENT_TOTAL_COST = 'percent_total_cost';

    const FIELD_REDEMPTIONS = 'redemptions';

    const FIELD_REDEMPTION_VALUE = 'redemption_value';

    const FIELD_SKU_VALUE = 'sku_value';

    private $total = [];


    protected function calc(): array
    {

        $programs = Program::whereIn('account_holder_id', $this->params[self::PROGRAMS])->get()->pluck('id')->toArray();
        $program = Program::findOrFail($this->params[self::PROGRAM_ID]);

        $redeem_international_jet = '';
        if ($program->programIsInvoiceForAwards()) {
            $account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
            $redeem_giftCode_jet = JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES;
            $redeem_international_jet = JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING;
            $reclaim_jet = JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS;
            $award_credit_reclaim_jet = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_CREDIT_RECLAIM_POINTS;

            $peer_account = AccountType::ACCOUNT_TYPE_PEER2PEER_POINTS;
            $peer_jet_reclaim = JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_PEER_POINTS;
            $peer_jet_awarded = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT;
        } else {
            $account_type = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
            $peer_account_type = AccountType::ACCOUNT_TYPE_PEER2PEER_MONIES;
            $redeem_giftCode_jet = JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES;
            $reclaim_jet = JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES;
            $award_credit_reclaim_jet = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_CREDIT_RECLAIM_MONIES;
            $peer_account = AccountType::ACCOUNT_TYPE_PEER2PEER_MONIES;
            $peer_jet_reclaim = JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_PEER_MONIES;
            $peer_jet_awarded = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT;
        }

        $topLevelProgram = $program->getRoot(['id']);
        $root_program_id = $topLevelProgram->id;

        // Get user ids for report query to optimise query execution time
        $query = DB::table('users');
        $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
        $query->join('model_has_roles', function ($join) use ($userClassForSql) {
            $join->on('model_has_roles.model_id', '=', 'users.id');
            $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
        });
        $query->join('roles', 'roles.id', '=', 'model_has_roles.role_id');
        $query->addSelect('users.id');
        $query->where('roles.name', 'LIKE', config('roles.participant'));
        $query->whereIn('model_has_roles.program_id', $programs);
//        $userIds = $query->get();

        $start_date = $this->params[self::DATE_FROM];
        $end_date = $this->params[self::DATE_TO];
        $points_awarded_sub_query = "
        (
            SELECT
                COALESCE(SUM(posting_amount), 0)
            FROM
                `postings`
                INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
            WHERE
                `accounts`.account_holder_id = recipient_id
                AND is_credit = 1
                AND `account_types`.name = '{$account_type}'
                AND DATE_FORMAT(`postings`.created_at,'%Y-%m-%d H:i:s') >= DATE_FORMAT('{$start_date} 00:00:00','%Y-%m-%d H:i:s')
                AND DATE_FORMAT(`postings`.created_at,'%Y-%m-%d H:i:s') <= DATE_FORMAT('{$end_date} 23:59:59','%Y-%m-%d H:i:s')
        )";

        $peer_points_awarded_sub_query = "
         (
             SELECT
             (
                SELECT
                    COALESCE(SUM(posting_amount), 0)
                FROM
                    `postings`
                    INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                    INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                    INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
                WHERE
                    `accounts`.account_holder_id = recipient_id AND is_credit = 1
                    AND `account_types`.name = '" . $peer_account . "'
            )
            -
            (
                SELECT
                    COALESCE(SUM(posting_amount), 0)
                FROM
                    `postings`
                    INNER JOIN `journal_events` ON `journal_events`.id = `postings`.journal_event_id
                    INNER JOIN `journal_event_types` ON `journal_event_types`.id = `journal_events`.journal_event_type_id
                    INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                    INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                    INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
                WHERE
                    `accounts`.account_holder_id = recipient_id AND is_credit = 0
                    AND `account_types`.name = '" . $peer_account . "'
                    AND `journal_event_types`.type = '" . $peer_jet_reclaim . "'
            )
        )";

        $points_expired_sub_query = "
        (
            SELECT
                COALESCE(SUM(posting_amount), 0)
            FROM `postings`
                INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
                INNER JOIN `journal_events` on `journal_events`.id = `postings`.journal_event_id
                INNER JOIN `journal_event_types` on `journal_event_types`.id = `journal_events`.journal_event_type_id
            WHERE `accounts`.account_holder_id = recipient_id
                AND is_credit = 0
                AND `account_types`.name = '" . $account_type . "'
                AND (
                    `journal_event_types`.type = '" . JournalEventType::JOURNAL_EVENT_TYPES_DEACTIVATE_MONIES . "'
                    OR `journal_event_types`.type = '" . JournalEventType::JOURNAL_EVENT_TYPES_DEACTIVATE_POINTS . "'
                    OR `journal_event_types`.type = '" . JournalEventType::JOURNAL_EVENT_TYPES_EXPIRE_MONIES . "'
                    OR `journal_event_types`.type = '" . JournalEventType::JOURNAL_EVENT_TYPES_EXPIRE_POINTS . "'
                    )
                AND DATE_FORMAT(`postings`.created_at,'%Y-%m-%d H:i:s') >= DATE_FORMAT('{$start_date} 00:00:00','%Y-%m-%d H:i:s')
                AND DATE_FORMAT(`postings`.created_at,'%Y-%m-%d H:i:s') <= DATE_FORMAT('{$end_date} 23:59:59','%Y-%m-%d H:i:s')
        )";

        $points_redeemed_sub_query = "
        (
            SELECT
                COALESCE(SUM(posting_amount), 0)
            FROM
                `postings`
                INNER JOIN `journal_events` ON `journal_events`.id = `postings`.journal_event_id
                INNER JOIN `journal_event_types` ON `journal_event_types`.id = `journal_events`.journal_event_type_id
                INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
            WHERE `accounts`.account_holder_id = recipient_id
                AND is_credit = 0
                AND `account_types`.name = '" . $account_type . "'
                AND (
                    `journal_event_types`.type = '" . $redeem_giftCode_jet . "'" . "
                    OR `journal_event_types`.type = '" . $redeem_international_jet . "'
                    )
                AND DATE_FORMAT(`postings`.created_at,'%Y-%m-%d H:i:s') >= DATE_FORMAT('{$start_date} 00:00:00','%Y-%m-%d H:i:s')
                AND DATE_FORMAT(`postings`.created_at,'%Y-%m-%d H:i:s') <= DATE_FORMAT('{$end_date} 23:59:59','%Y-%m-%d H:i:s')
        )";

        $peer_points_given_sub_query = "
        (
            SELECT
                COALESCE(SUM(posting_amount), 0)
            FROM
                `postings`
                INNER JOIN `journal_events` ON `journal_events`.id = `postings`.journal_event_id
                INNER JOIN `journal_event_types` ON `journal_event_types`.id = `journal_events`.journal_event_type_id
                INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
            WHERE
                `accounts`.account_holder_id = recipient_id AND is_credit = 0
                AND `account_types`.name = '" . $peer_account . "'
                AND `journal_event_types`.type = '" . $peer_jet_awarded . "'
        )";

        $points_reclaimed_sub_query = "
        (
            SELECT
                COALESCE(SUM(posting_amount), 0)
            FROM
                `postings`
                INNER JOIN `journal_events` ON `journal_events`.id = `postings`.journal_event_id
                INNER JOIN `journal_event_types` ON `journal_event_types`.id = `journal_events`.journal_event_type_id
                INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
            WHERE
                `accounts`.account_holder_id = recipient_id
                AND is_credit = 0
                AND `account_types`.name = '" . $account_type . "'
                AND `journal_event_types`.type = '" . $reclaim_jet . "'
                AND DATE_FORMAT(`postings`.created_at,'%Y-%m-%d H:i:s') >= DATE_FORMAT('{$start_date} 00:00:00','%Y-%m-%d H:i:s')
                AND DATE_FORMAT(`postings`.created_at,'%Y-%m-%d H:i:s') <= DATE_FORMAT('{$end_date} 23:59:59','%Y-%m-%d H:i:s')
        )";

        $award_credit_points_reclaimed_sub_query = "
        (
            SELECT
                COALESCE(SUM(posting_amount), 0)
            FROM
                `postings`
                INNER JOIN `journal_events` ON `journal_events`.id = `postings`.journal_event_id
                INNER JOIN `journal_event_types` ON `journal_event_types`.id = `journal_events`.journal_event_type_id
                INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
            WHERE `accounts`.account_holder_id = recipient_id
                AND is_credit = 0
                AND `account_types`.name = '" . $account_type . "'
                AND `journal_event_types`.type = '" . $award_credit_reclaim_jet . "'
                AND DATE_FORMAT(`postings`.created_at,'%Y-%m-%d H:i:s') >= DATE_FORMAT('{$start_date} 00:00:00','%Y-%m-%d H:i:s')
                AND DATE_FORMAT(`postings`.created_at,'%Y-%m-%d H:i:s') <= DATE_FORMAT('{$end_date} 23:59:59','%Y-%m-%d H:i:s')
        )";

        $points_balance_sub_query = "
        (
            SELECT
                (
                SELECT
                    COALESCE(SUM(posting_amount), 0)
                FROM `postings`
                    INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                    INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                    INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
                WHERE
                    `accounts`.account_holder_id = recipient_id
                    AND is_credit = 1
                    AND `account_types`.name = '" . $account_type . "'
                )
                -
                (
                SELECT
                    COALESCE(SUM(posting_amount), 0)
                FROM `postings`
                    INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                    INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                    INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
                WHERE
                    `accounts`.account_holder_id = recipient_id
                    AND is_credit = 0
                    AND `account_types`.name = '" . $account_type . "'
                )
        )";

        $peer_points_balance_sub_query = "
        (
            SELECT
                (
                SELECT
                    COALESCE(SUM(posting_amount), 0)
                FROM `postings`
                    INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                    INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                    INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
                WHERE
                    `accounts`.account_holder_id = recipient_id
                    AND is_credit = 1
                    AND `account_types`.name = '" . $peer_account . "'
                )
                -
                (
                SELECT
                    COALESCE(SUM(posting_amount), 0)
                FROM
                    `postings`
                    INNER JOIN `accounts` ON `accounts`.id = `postings`.account_id
                    INNER JOIN `account_types` ON `accounts`.account_type_id = account_types.id
                    INNER JOIN `account_holders` ON `accounts`.account_holder_id = account_holders.id
                WHERE
                    `accounts`.account_holder_id = recipient_id
                    AND is_credit = 0
                    AND `account_types`.name = '" . $peer_account . "'
                )
        )";

        $subQuery = DB::table('users');
        $subQuery->selectRaw("
            `programs`.id as program_id,
            `users`.account_holder_id AS recipient_id,
            `users`.activated,
            `users`.created_at as created,
            `users`.email AS recipient_email,
            `users`.first_name AS recipient_first_name,
            `users`.last_name AS recipient_last_name,
            `users`.organization_id AS recipient_organization_uid,
            `users`.work_anniversary AS anniversary,
            `users`.dob AS birth,
            " .
            // TODO: award level
//                     `award_levels_has_users`.award_levels_id as award_level_id,
//                     `award_level`.name as recipient_group,
            "
            `users`.user_status_id,
            `statuses`.status,
            `programs`.external_id,
            `programs`.name as program_name,
            `programs`.account_holder_id as program_account_holder_id,
            CASE
                WHEN `programs`.factor_valuation IS NULL THEN 1
                ELSE `programs`.factor_valuation
            END as factor_valuation

        ");
        $subQuery->leftJoin('model_has_roles', function ($join) use ($userClassForSql) {
            $join->on('model_has_roles.model_id', '=', 'users.id');
            $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
        });
        $subQuery->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id');
        $subQuery->leftJoin('programs', 'programs.id', '=', 'model_has_roles.program_id');
        $subQuery->leftJoin('statuses', 'statuses.id', '=', 'users.user_status_id');
        $subQuery->whereIn('model_has_roles.program_id', $programs);
        if ($this->params[self::CREATED_ONLY]) {
            $subQuery->whereRaw("
                DATE_FORMAT(`users`.created_at,'%Y-%m-%d H:i:s') >= DATE_FORMAT('{$start_date} 00:00:00','%Y-%m-%d H:i:s')
                AND DATE_FORMAT(`users`.created_at,'%Y-%m-%d H:i:s') <= DATE_FORMAT('{$end_date} 23:59:59','%Y-%m-%d H:i:s')
            ");
        }

        $query = DB::table(DB::raw("({$subQuery->toSql()}) as sub"));

        // pagination
        $query2 = clone $query;
        $query2->selectRaw("*");
        $query2->mergeBindings($subQuery);
        $query2->groupBy('program_id', 'recipient_id', 'activated', 'created', 'recipient_email',
            'recipient_first_name', 'recipient_last_name', 'recipient_organization_uid', 'anniversary',
            'birth', 'user_status_id', 'status', 'external_id', 'program_name', 'program_account_holder_id'
        );
        $allQuery =  DB::table(DB::raw("({$query2->toSql()}) as count"));
        $allQuery->mergeBindings($query2);
        $this->table['total'] =  $allQuery->count();

        $query->selectRaw("
            *,
            " . $peer_points_awarded_sub_query . " AS peer_rewards_earned,
            " . $peer_points_given_sub_query . " AS peer_rewards_given,
            " . $peer_points_balance_sub_query . " AS peer_balance,
            " . $points_awarded_sub_query . " AS points_awarded,
            " . $points_redeemed_sub_query . " AS points_redeemed,
            " . $points_expired_sub_query . " AS points_expired,
            " . $points_balance_sub_query . " AS points_balance,
            " . $points_reclaimed_sub_query . " AS points_reclaimed,
            " . $award_credit_points_reclaimed_sub_query . " AS award_credit_points_reclaimed
        ");
        $query->mergeBindings($subQuery);

        $query = $this->setLimit($query);
        $query->groupBy('program_id', 'recipient_id', 'activated', 'created', 'recipient_email',
            'recipient_first_name', 'recipient_last_name', 'recipient_organization_uid', 'anniversary',
            'birth', 'user_status_id', 'status', 'external_id', 'program_name', 'program_account_holder_id'
        );
        $query->orderBy('recipient_last_name', 'ASC');
        $query->orderBy('recipient_first_name', 'ASC');

        $table = $query->get();

        $this->table['data'] = $table;

        return $this->table;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program Id',
                'key' => 'program_id'
            ],
            [
                'label' => 'Program Name',
                'key' => 'program_name'
            ],
            [
                'label' => 'External Id',
                'key' => 'external_id'
            ],
            [
                'label' => 'Org Id',
                'key' => 'recipient_organization_uid'
            ],
            [
                'label' => 'First Name',
                'key' => 'recipient_first_name'
            ],
            [
                'label' => 'Last Name',
                'key' => 'recipient_last_name'
            ],
            [
                'label' => 'Email',
                'key' => 'recipient_email'
            ],
            [
                'label' => 'Amount Awarded',
                'key' => 'points_awarded'
            ],
            [
                'label' => 'Amount Redeemed',
                'key' => 'points_redeemed'
            ],
            [
                'label' => 'Amount Expired',
                'key' => 'points_expired'
            ],
            [
                'label' => 'Amount Reclaimed',
                'key' => 'points_reclaimed'
            ],
            [
                'label' => 'Award Credit Reclaimed',
                'key' => 'award_credit_points_reclaimed'
            ],
            [
                'label' => 'Current Balance',
                'key' => 'points_balance'
            ],
            [
                'label' => 'Peer Points Allocated',
                'key' => 'peer_rewards_earned'
            ],
            [
                'label' => 'Peer Points Given',
                'key' => 'peer_rewards_given'
            ],
            [
                'label' => 'Peer Points Balance',
                'key' => 'peer_balance'
            ],
            [
                'label' => 'Anniversary',
                'key' => 'anniversary'
            ],
            [
                'label' => 'Birthday',
                'key' => 'birth'
            ],
        ];
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data = $this->getTable();

        foreach ($data['data'] as $key => $item) {
            foreach ($item as $keyItem => $itemValue) {
                if (in_array($keyItem, [
                    'points_awarded',
                    'points_redeemed',
                    'points_expired',
                    'points_reclaimed',
                    'award_credit_points_reclaimed',
                    'points_balance',
                    'peer_rewards_earned',
                    'peer_rewards_given',
                    'poinpeer_balancets_expired',
                ])) {
                    $tmp = $itemValue * $data['data'][$key]->factor_valuation;
                    $data['data'][$key]->$keyItem = number_format ( ( float ) $tmp, 4, '.', '' );
                }
            }
        }
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

}