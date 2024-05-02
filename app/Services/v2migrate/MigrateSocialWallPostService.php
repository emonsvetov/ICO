<?php

namespace App\Services\v2migrate;

use App\Models\SocialWallPost;
use App\Models\User;
use Exception;

use App\Models\Program;

class MigrateSocialWallPostService extends MigrationService
{
    public array $importedSocialWallPosts = [];
    public $v3Users;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @param int $v2AccountHolderID
     * @return array
     * @throws Exception
     */
    public function migrate(int $v2AccountHolderID): array
    {
        if (!$v2AccountHolderID) {
            throw new Exception("Wrong data provided. v2AccountHolderID: {$v2AccountHolderID}");
        }
        $programArgs = ['program' => $v2AccountHolderID];

        $this->printf("Starting Social Wall Post migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migrateAll($v2RootPrograms);
        $this->executeV2SQL();

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedSocialWallPosts) . " items",
        ];

    }

    /**
     * @throws Exception
     */
    public function migrateAll(array $v2RootPrograms): void
    {
        $organizationId = null;
        $accountHolderIds = [];
        foreach ($v2RootPrograms as $v2RootProgram) {
            $accountHolderIds[] = $v2RootProgram->account_holder_id;
            $v3Program = Program::findOrFail($v2RootProgram->v3_program_id);
            $organizationId = $v3Program->organization_id ?? null;

            $subPrograms = $this->read_list_children_heirarchy(( int )$v2RootProgram->account_holder_id);
            foreach ($subPrograms as $subProgram) {
                $accountHolderIds[] = $subProgram->account_holder_id;
            }
        }

        $this->migrateSocialWallPosts($accountHolderIds, $organizationId);
    }

    /**
     * @throws Exception
     */
    public function migrateSocialWallPosts($accountHolderIds, $organizationId)
    {
        $v2Data = $this->getSocialWallPostsByIds($accountHolderIds);

        $v2Users = [];
        foreach ($v2Data as $v2SocialWallPost) {
            $v3_sender_id = $v2SocialWallPost->v3_sender_id;
            $v3_receiver_id = $v2SocialWallPost->v3_receiver_id;
            $v2Users[$v3_sender_id] = $v3_sender_id;
            $v2Users[$v3_receiver_id] = $v3_receiver_id;
        }

        $this->v3Users = User::whereIn('id', $v2Users)->pluck('account_holder_id', 'id')->toArray();

        foreach ($v2Data as $item) {
            $this->syncOrCreateSocialWallPost($item, $organizationId);
        }

        $v2Data = $this->getSocialWallPostsLogByIds($accountHolderIds);

        foreach ($v2Data as $item) {
            $this->syncOrCreateSocialWallPostLog($item, $organizationId);
        }
    }

    public function syncOrCreateSocialWallPost($v2SocialWallPost, $organizationId)
    {
        $parent_id = null;
        if ($v2SocialWallPost->social_wall_post_id) {
            $v2SocialWallPostUpdated = $this->getSocialWallPost($v2SocialWallPost->social_wall_post_id);
            $parent_id = $v2SocialWallPostUpdated ? $v2SocialWallPostUpdated->v3_id : null;
        }
        $data = [
            'social_wall_post_type_id' => $v2SocialWallPost->social_wall_post_type_id,
            'social_wall_post_id' => $parent_id,
            'event_xml_data_id' => $v2SocialWallPost->v3_event_xml_data_id,
            'awarder_program_id' => $v2SocialWallPost->v3_awarder_program_id,
            'sender_user_account_holder_id' => $this->v3Users[$v2SocialWallPost->v3_sender_id],
            'receiver_user_account_holder_id' => $this->v3Users[$v2SocialWallPost->v3_receiver_id],
            'comment' => $v2SocialWallPost->comment,
            'created_at' => $v2SocialWallPost->created,
            'organization_id' => $organizationId,
            'program_id' => $v2SocialWallPost->v3_program_id,
            'likesCount' => 0,
            'v2_id' => $v2SocialWallPost->id,
        ];

        $dataSearch = $data;
        unset($dataSearch['comment']);
        unset($dataSearch['v2_id']);

        $v3SocialWallPost = SocialWallPost::where($dataSearch)->first();
        if (!$v3SocialWallPost) {
            $v3SocialWallPost = SocialWallPost::create($data);
        }
        $this->printf("SocialWallPost done: {$v3SocialWallPost->id}. Count= " . count($this->importedSocialWallPosts) . " \n\n");

        if ($v3SocialWallPost) {
            $this->addV2SQL(sprintf("UPDATE `social_wall_posts` SET `v3_id`=%d WHERE `id`=%d", $v3SocialWallPost->id, $v2SocialWallPost->id));
            $this->importedSocialWallPosts[] = $v3SocialWallPost->id;
        }
    }

    public function syncOrCreateSocialWallPostLog($v2SocialWallPost, $organizationId)
    {
        $data = [
            'social_wall_post_type_id' => $v2SocialWallPost->social_wall_post_type_id,
            'social_wall_post_id' => null,
            'event_xml_data_id' => $v2SocialWallPost->v3_event_xml_data_id,
            'awarder_program_id' => $v2SocialWallPost->v3_awarder_program_id,
            'sender_user_account_holder_id' => $v2SocialWallPost->v3_sender_id,
            'receiver_user_account_holder_id' => $v2SocialWallPost->v3_receiver_id,
            'comment' => $v2SocialWallPost->comment,
            'created_at' => $v2SocialWallPost->created,
            'organization_id' => $organizationId,
            'program_id' => $v2SocialWallPost->v3_program_id,
            'likesCount' => 0,
            'deleted_at' => $v2SocialWallPost->deleted_at,
            'updated_by' => $v2SocialWallPost->v3_deleted_by_id,
            'v2_id' => $v2SocialWallPost->id,
        ];

        $dataSearch = $data;
        unset($dataSearch['comment']);
        unset($dataSearch['v2_id']);

        $v3SocialWallPost = SocialWallPost::where($dataSearch)->first();
        if (!$v3SocialWallPost) {
            $v3SocialWallPost = SocialWallPost::create($data);
        }
        $this->printf("SocialWallPost done: {$v3SocialWallPost->id}. Count= " . count($this->importedSocialWallPosts) . " \n\n");

        if ($v3SocialWallPost) {
            $this->addV2SQL(sprintf("UPDATE `social_wall_posts` SET `v3_id`=%d WHERE `id`=%d", $v3SocialWallPost->id, $v2SocialWallPost->id));
            $this->importedSocialWallPosts[] = $v3SocialWallPost->id;
        }
    }

}
