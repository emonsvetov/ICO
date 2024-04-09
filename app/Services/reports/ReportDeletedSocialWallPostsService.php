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
use App\Models\SocialWallPostType;
use App\Models\SocialWallPost;

class ReportDeletedSocialWallPostsService extends ReportServiceAbstract
{
    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $query = DB::table('social_wall_posts');
        $query->join('social_wall_post_types', 'social_wall_post_types.id', '=', 'social_wall_posts.social_wall_post_type_id');
        $query->leftJoin('users AS sender', 'sender.id', '=', 'social_wall_posts.sender_user_account_holder_id');
        $query->leftJoin('users AS receiver', 'receiver.id', '=', 'social_wall_posts.receiver_user_account_holder_id');
        $query->leftJoin('users AS deleted_by_user', 'deleted_by_user.id', '=', 'social_wall_posts.updated_by');
        $query->leftJoin('users AS created_by_user', 'created_by_user.id', '=', 'social_wall_posts.created_by');
        $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'social_wall_posts.event_xml_data_id');
        $query->join('programs', 'programs.id', '=', 'social_wall_posts.program_id');

        $query->selectRaw("
            social_wall_posts.id
            ,DATE_FORMAT(social_wall_posts.created_at, '%m/%d/%Y') as created_at_format_date
            ,social_wall_posts.social_wall_post_id
            ,DATE_FORMAT(social_wall_posts.deleted_at, '%m/%d/%Y') as deleted_at_format_date
            ,CONCAT(sender.first_name, ' ', sender.last_name) as sender_name
            ,CONCAT(receiver.first_name, ' ', receiver.last_name) as receiver_name
            ,CONCAT(deleted_by_user.first_name, ' ', deleted_by_user.last_name) as deleted_by_name
            ,CONCAT(created_by_user.first_name, ' ', created_by_user.last_name) as created_by_name
            ,programs.name as program_name
            ,social_wall_post_types.type as social_wall_post_type
            ,event_xml_data.name as event_name
            ,CASE
                WHEN social_wall_posts.social_wall_post_type_id = 2
                THEN social_wall_posts.comment
                ELSE event_xml_data.notification_body
             END as comment
            ,event_xml_data.icon as icon
        ");
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);
        $query->whereNotNull('social_wall_posts.deleted_at');
        $query->whereNull('social_wall_posts.social_wall_post_id');
        if ($this->params[self::DATE_BEGIN] && $this->params[self::DATE_END]) {
            $query->whereBetween('social_wall_posts.deleted_at', [$this->params[self::DATE_BEGIN], $this->params[self::DATE_END]]);
        }
        return $query;
    }

    protected function calc()
    {
        $this->table = [];
        $this->getDataDateRange();

        foreach ($this->table as $key => $item) {
            $comments = (new SocialWallPost())->allComments($item->id)->toArray();
            $this->table[$key]->subRows = $comments;
        }

        return $this->table;
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data = $this->getTable();
        $newData = [];
        foreach ($data as $item){
            $newData[] = $item;
            foreach ($item->subRows as $subItem){
                $newData[] = $subItem;
            }
        }
        $data['data'] = $newData;
        $data['total'] = count($newData);
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program Name',
                'key' => 'program_name'
            ],
            [
                'label' => 'Receiver Name',
                'key' => 'receiver_name'
            ],
            [
                'label' => 'Event',
                'key' => 'event_name'
            ],
            [
                'label' => 'Event',
                'key' => 'event_name'
            ],
            [
                'label' => 'Type',
                'key' => 'social_wall_post_type'
            ],
            [
                'label' => 'Social Wall Post/Comment',
                'key' => 'comment'
            ],
            [
                'label' => 'Created By',
                'key' => 'created_by_name'
            ],
            [
                'label' => 'Created At',
                'key' => 'created_at_format_date'
            ],
            [
                'label' => 'Deleted By',
                'key' => 'deleted_by_name'
            ],
            [
                'label' => 'Deleted At',
                'key' => 'deleted_at_format_date'
            ],
        ];
    }

}
