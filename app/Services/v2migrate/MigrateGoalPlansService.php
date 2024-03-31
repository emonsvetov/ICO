<?php
namespace App\Services\v2migrate;

use App\Models\Domain;
use App\Models\Event;
use App\Models\GoalPlan;
use App\Models\GoalPlansEvent;
use App\Models\Invoice;
use App\Models\InvoiceJournalEvent;
use App\Models\InvoiceType;
use App\Models\JournalEvent;
use App\Models\Program;
use App\Models\User;
use App\Services\ProgramService;
use Exception;

class MigrateGoalPlansService extends MigrationService
{

    public $countUpdateGoalPlans = 0;
    public $countCreateGoalPlans = 0;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Run migrate goal plans.
     *
     * @param $v2AccountHolderID
     * @return array
     * @throws Exception
     */
    public function migrate($v2AccountHolderID)
    {
        $result = [
            'success' => FALSE,
            'info' => '',
        ];

        $v3Program = Program::where('v2_account_holder_id', $v2AccountHolderID)->first();
        $v3AccountHolderID = $v3Program->account_holder_id ?? NULL;

        // Checking.
        if (empty($v3AccountHolderID)) {
            throw new Exception("v3 program with ID: " . $v2AccountHolderID . " not found.");
        }

        $this->getSubPrograms($v3Program);

        try {
            $result['success'] = TRUE;
            $result['info'] = "update $this->countUpdateGoalPlans items, create $this->countCreateGoalPlans items";
        } catch (\Exception $exception) {
            throw new Exception("Migrate goal plans is failed.");
        }

        return $result;
    }

    /**
     * Migrate goal plans.
     */
    public function getSubPrograms($v3Program)
    {
        $v2AccountHolderID = $v3Program->v2_account_holder_id ?? FALSE;
        $subPrograms = $v3Program->children ?? [];

        if ($v2AccountHolderID) {
            $this->migrateGoalPlans($v2AccountHolderID, $v3Program);
        }

        if (!empty($subPrograms)) {
            foreach ($subPrograms as $subProgram) {
                $this->getSubPrograms($subProgram);
            }
        }
    }

    /**
     * Migrate goal plans.
     *
     * @param $v2AccountHolderID
     */
    public function migrateGoalPlans($v2AccountHolderID, $v3SubProgram)
    {
        $v2GoalPlans = $this->getV2GoalPlans($v2AccountHolderID);
        foreach ($v2GoalPlans as $v2GoalPlan) {

            try {
                $nextGoalPlan = !blank($v2GoalPlan->next_goal_id) ? GoalPlan::where('v2_goal_plan_id', $v2GoalPlan->next_goal_id)->first() : NULL;
                $previousGoalPlan = !blank($v2GoalPlan->previous_goal_id) ? GoalPlan::where('v2_goal_plan_id', $v2GoalPlan->previous_goal_id)->first() : NULL;
            } catch(\Exception $e) {
                throw new Exception("dont find v2_goal_plan_id, please run php artisan migrate");
            }

            $v3GoalPlanData = [
                'next_goal_id' => !blank($nextGoalPlan) ? $nextGoalPlan->id : NULL,
                'previous_goal_id' => !blank($previousGoalPlan) ? $previousGoalPlan->id : NULL,
                'program_id' => $v3SubProgram->id,
                'organization_id' => $v3SubProgram->organization_id,
                'name' => $v2GoalPlan->name,
                'goal_measurement_label' => $v2GoalPlan->goal_measurement_label,
                'goal_plan_type_id' => $v2GoalPlan->goal_plan_type_id, // matches
                'state_type_id' => $v2GoalPlan->state_type_id, // matches is statuses on v3
                'default_target' => $v2GoalPlan->default_target,
                'email_template_id' => NULL, // all NULL on v2
                'notification_body' => NULL, // all NULL on v2
                'achieved_callback_id' => NULL, // all NULL on v2
                'exceeded_callback_id' => NULL, // all NULL on v2
                'achieved_event_id' => $this->getV3EventID($v2GoalPlan->achieved_event_template_id),
                'exceeded_event_id' => $this->getV3EventID($v2GoalPlan->exceeded_event_template_id),
                'automatic_progress' => $v2GoalPlan->automatic_progress,
                'automatic_frequency' => $v2GoalPlan->automatic_frequency,
                'automatic_value' => $v2GoalPlan->automatic_value,
                'expiration_rule_id' => $v2GoalPlan->expiration_rule_id, // matches
                'custom_expire_offset' => $v2GoalPlan->custom_expire_offset,
                'custom_expire_units' => $v2GoalPlan->custom_expire_units,
                'annual_expire_month' => $v2GoalPlan->annual_expire_month,
                'annual_expire_day' => $v2GoalPlan->annual_expire_day,
                'date_begin' => $v2GoalPlan->date_begin,
                'date_end' => $v2GoalPlan->date_end,
                'factor_before' => $v2GoalPlan->factor_before,
                'factor_after' => $v2GoalPlan->factor_after,
                'is_recurring' => $v2GoalPlan->is_recurring,
                'award_per_progress' => $v2GoalPlan->award_per_progress,
                'award_email_per_progress' => $v2GoalPlan->award_email_per_progress,
                'progress_requires_unique_ref_num' => $v2GoalPlan->progress_requires_unique_ref_num,
                'progress_notification_email_id' => NULL, // for now set any number, TO DO to make it dynamic
                'assign_goal_all_participants_default' => $v2GoalPlan->assign_goal_all_participants_default,
                'created_by' => 0,
                'modified_by' => 0,
                'expired' => $v2GoalPlan->expired,
                'v2_goal_plan_id' => $v2GoalPlan->id,
            ];

            $v3GoalPlan = GoalPlan::where('v2_goal_plan_id', $v2GoalPlan->id)->first();

            if (blank($v3GoalPlan)) {
                $v3GoalPlan = GoalPlan::create($v3GoalPlanData);
                $this->countCreateGoalPlans++;
            }
            else {
                $v3GoalPlan->update($v3GoalPlanData);
                $this->countUpdateGoalPlans++;
            }

            $v2GoalPlanEvents = $this->getV2GoalPlanEvents($v2GoalPlan->id);
            if (!empty($v2GoalPlanEvents)) {
                  $v2EventIDs = [];
                  foreach ($v2GoalPlanEvents as $v2GoalPlanEvent) {
                      $v2EventIDs[] = $v2GoalPlanEvent->event_template_id;
                  }
                  $v3Events = Event::whereIn('v2_event_id', $v2EventIDs)->get();
                  foreach ($v3Events as $v3Event) {
                      GoalPlansEvent::create([
                          'goal_plans_id' => $v3GoalPlan->id,
                          'event_id' => $v3Event->id,
                      ]);
                  }
            }

        }
    }

    /**
     * Get v3 event ID.
     *
     * @param $v2EventID
     * @return null
     */
    public function getV3EventID($v2EventID)
    {
        $event = Event::where('v2_event_id', $v2EventID)->first();
        return $event->id ?? NULL;
    }

}
