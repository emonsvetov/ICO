<?php

namespace App\Services;

use App\Models\JournalEventType;
use App\Models\Leaderboard;
use App\Models\AccountType;
use App\Models\LeaderboardJournalEvent;
use App\Models\LeaderboardType;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    public function createLeaderboardJournalEvent($eventId, $journalEventId)
    {
        $data = Leaderboard::select('event_id', 'leaderboards.id')
            ->join('leaderboard_event', 'leaderboard_event.leaderboard_id', '=', 'leaderboards.id')
            ->join('statuses', 'statuses.id', '=', 'leaderboards.status_id')
            ->where('leaderboard_event.event_id', '=', $eventId)
            ->whereNotIn('statuses.status',
                [Leaderboard::LEADERBOARD_STATE_DELETED, Leaderboard::LEADERBOARD_STATE_DEACTIVATED])
            ->get();

        foreach ($data as $item){
            LeaderboardJournalEvent::insertGetId([
                'leaderboard_id' => $item->id,
                'journal_event_id' => $journalEventId,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Summary of awards
     * @param $leaderboards
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws \Exception
     */
    public function readEventLeaders($leaderboards, int $offset, int $limit)
    {
        foreach ($leaderboards as $leaderboard) {
            switch ($leaderboard->leaderboard_type->name) {
                case LeaderboardType::LEADERBOARD_TYPE_EVENT_SUMMARY :
                    $leaderboard->leaders = $this->readEventLeadersByAwards($leaderboard->id, $limit, $offset);
                    break;
                case LeaderboardType::LEADERBOARD_TYPE_EVENT_VOLUME :
                    $leaderboard->leaders = $this->readEventLeadersByCount($leaderboard->id, $limit, $offset);
                    break;
                case LeaderboardType::LEADERBOARD_TYPE_GOAL_PROGRESS :
                    $leaderboard->leaders = $this->readEventLeadersByProgress($leaderboard->id, $limit, $offset);
                    break;
            }
        }

        return $leaderboards;
    }

    public function readEventLeadersByAwards(int $leaderboardId, int $limit, int $offset)
    {
        DB::statement(DB::raw('SET @i= 0;'));
        DB::statement(DB::raw('SET @val= null;'));

        $query = DB::table(function ($subQuery) use ($leaderboardId) {
            $subQuery->from('leaderboards');
            $subQuery->join('leaderboard_journal_event',
                'leaderboard_journal_event.leaderboard_id', '=', 'leaderboards.id');
            $subQuery->join('journal_events', 'journal_events.id', '=',
                'leaderboard_journal_event.journal_event_id');
            $subQuery->join('journal_event_types', 'journal_event_types.id', '=',
                'journal_events.journal_event_type_id');
            $subQuery->join('postings', 'postings.journal_event_id', '=', 'journal_events.id');
            $subQuery->join('accounts', 'accounts.id', '=', 'postings.account_id');
            $subQuery->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $subQuery->join('users', 'users.account_holder_id', '=', 'accounts.account_holder_id');
            $subQuery->leftJoin('journal_events as reclaimed', 'reclaimed.parent_journal_event_id', '=',
                'journal_events.id');
            $subQuery->leftJoin('journal_event_types as reclaimed_types', function ($join) {
                $join->on('reclaimed_types.id', '=', 'reclaimed.journal_event_type_id')
                    ->where(function ($q) {
                        $q->where('journal_event_types.type', '=',
                            JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS)
                            ->orWhere('journal_event_types.type', '=',
                                JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES);
                    });
            });
            $subQuery->leftJoin('postings as reclaimed_postings', function ($join) {
                $join->on('reclaimed_postings.journal_event_id', '=', 'reclaimed.id');
                $join->on('reclaimed_postings.account_id', '=', 'accounts.id');
            });

            $subQuery->addSelect(
                DB::raw("
                        users.account_holder_id as user_id,
                        concat (users.first_name, ' ', users.last_name) as `display_name`,
                        sum(postings.posting_amount) - sum(ifnull(reclaimed_postings.posting_amount, 0)) as `total`
                    ")
            );
            $subQuery->where('leaderboards.id', '=', $leaderboardId);
            $subQuery->whereNull('reclaimed.id');
            $subQuery->whereIn('journal_event_types.type', [
                JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT,
                JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT,
                JournalEventType::JOURNAL_EVENT_TYPES_PROMOTIONAL_AWARD,
                JournalEventType::JOURNAL_EVENT_TYPES_REDEEMABLE_ON_INTERNAL_STORE,
            ]);
            $subQuery->groupBy(['user_id', 'display_name']);
        }, 'subQuery')
            ->select(
                DB::raw("
                        user_id,
                        display_name,
                        total,
                        (@i:= ifnull(@i, 1) + (if(ifnull(@val, 0) = total, 0, 1))) as ranking,
                        @val:= total
                ")
            )->groupBy(['user_id', 'display_name', 'total'])
            ->orderBy('total', 'DESC')
            ->orderBy('display_name', 'ASC');


        try {
           return $limit ? $query->limit($limit)->offset($offset)->get() : $query->get();
        } catch (\Exception $e) {
            throw new \Exception('DB query failed.', 500);
        }
    }

    public function readEventLeadersByCount(int $leaderboardId, int $limit, int $offset)
    {
        DB::statement(DB::raw('SET @i= 0;'));
        DB::statement(DB::raw('SET @val= null;'));

        $query = DB::table(function ($subQuery) use ($leaderboardId) {
            $subQuery->from('leaderboards');
            $subQuery->join('leaderboard_journal_event',
                'leaderboard_journal_event.leaderboard_id', '=', 'leaderboards.id');
            $subQuery->join('journal_events', 'journal_events.id', '=',
                'leaderboard_journal_event.journal_event_id');
            $subQuery->join('journal_event_types', 'journal_event_types.id', '=',
                'journal_events.journal_event_type_id');
            $subQuery->join('postings', 'postings.journal_event_id', '=', 'journal_events.id');
            $subQuery->join('accounts', 'accounts.id', '=', 'postings.account_id');
            $subQuery->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $subQuery->join('users', 'users.account_holder_id', '=', 'accounts.account_holder_id');
            $subQuery->leftJoin('journal_events as reclaimed', 'reclaimed.parent_journal_event_id', '=',
                'journal_events.id');
            $subQuery->leftJoin('journal_event_types as reclaimed_types', function ($join) {
                $join->on('reclaimed_types.id', '=', 'reclaimed.journal_event_type_id')
                    ->where(function ($q) {
                        $q->where('journal_event_types.type', '=',
                            JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS)
                            ->orWhere('journal_event_types.type', '=',
                                JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES);
                    });
            });

            $subQuery->addSelect(
                DB::raw("
                        users.account_holder_id as user_id,
                        concat (users.first_name, ' ', users.last_name) as `display_name`,
                        count(0) as `count`
                    ")
            );
            $subQuery->where('leaderboards.id', '=', $leaderboardId);
            $subQuery->whereNull('reclaimed.id');
            $subQuery->whereIn('journal_event_types.type', [
                JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT,
                JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT,
                JournalEventType::JOURNAL_EVENT_TYPES_PROMOTIONAL_AWARD,
                JournalEventType::JOURNAL_EVENT_TYPES_REDEEMABLE_ON_INTERNAL_STORE,
            ]);
            $subQuery->whereIn('account_types.name', [
                AccountType::ACCOUNT_TYPE_POINTS_AWARDED,
                AccountType::ACCOUNT_TYPE_MONIES_AWARDED,
                AccountType::ACCOUNT_TYPE_INTERNAL_STORE_POINTS,
                AccountType::ACCOUNT_TYPE_PROMOTIONAL_POINTS,
            ]);
            $subQuery->groupBy(['user_id', 'display_name']);
        }, 'subQuery')
            ->select(
                DB::raw("
                        user_id,
                        display_name,
                        `count` as total,
                        (@i:= ifnull(@i, 1) + (if(ifnull(@val, 0) = count, 0, 1))) as ranking,
                        @val:= count
                ")
            )->groupBy(['user_id', 'display_name', 'total'])
            ->orderBy('total', 'DESC')
            ->orderBy('display_name', 'ASC');

        try {
            return $limit ? $query->limit($limit)->offset($offset)->get() : $query->get();
        } catch (\Exception $e) {
            throw new \Exception('DB query failed.', 500);
        }
    }

    public function readEventLeadersByProgress(int $leaderboardId, int $limit, int $offset)
    {
        DB::statement(DB::raw('SET @i= 0;'));
        DB::statement(DB::raw('SET @val= null;'));

        $query = DB::table(function ($subQuery) use ($leaderboardId) {
            $subQuery->from('leaderboards');
            $subQuery->join('leaderboard_goal',
                'leaderboard_goal.leaderboard_id', '=', 'leaderboards.id');
            $subQuery->join('leaderboard_goal_plan', 'leaderboard_goal_plan.id', '=',
                'leaderboard_goal.goal_plan_id');
            $subQuery->join('user_goal', 'user_goal.goal_plan_id', '=',
                'leaderboard_goal_plan.id');
            $subQuery->join('users', 'users.account_holder_id', '=', 'user_goal.user_id');
            $subQuery->join('user_goal_progress', 'user_goal_progress.user_goal_id', '=', 'user_goal.id');

            $subQuery->addSelect(
                DB::raw("
                        users.account_holder_id as user_id,
                        concat (users.first_name, ' ', users.last_name) as `display_name`,
                        sum(user_goal_progress.progress_value) as progress,
                        user_goal.target_value as target,
                        round(100 * (sum(user_goal_progress.progress_value) / user_goal.target_value	), 2) as pcnt_progress
                    ")
            );
            $subQuery->where('leaderboards.id', '=', $leaderboardId);
            $subQuery->groupBy(['user_id', 'display_name', 'user_goal.target_value']);
        }, 'subQuery')
            ->select(
                DB::raw("
                        user_id,
                        display_name,
                        progress,
                        target,
                        pcnt_progress,
                        (@i:= ifnull(@i, 0) + (if(round(ifnull(@val, 0),2) = round(pcnt_progress, 2), 0, 1))) as ranking,
                        @val:= pcnt_progress
                ")
            )->groupBy(['user_id', 'display_name', 'progress', 'target', 'pcnt_progress'])
            ->orderBy('pcnt_progress', 'DESC')
            ->orderBy('display_name', 'ASC');

        try {
            return $limit ? $query->limit($limit)->offset($offset)->get() : $query->get();
        } catch (\Exception $e) {
            print_r($e->getMessage());
            die;
            throw new \Exception('DB query failed.', 500);
        }
    }

}
