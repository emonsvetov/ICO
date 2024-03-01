<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProgramExtra extends Model
{
    use HasFactory;

    protected $table = 'programs_extra';
    protected $fillable = [
        "factor_valuation",
        "points_over_budget",
        "bill_direct",
        "allow_creditcard_deposits",
        "reserve_percentage",
        "setup_fee",
        "discount_rebate_percentage",
        "expiration_rebate_percentage",
        "convenience_fee",
        "percent_total_spend_rebate",
        "budget_number",
        "alarm_percentage",
        "administrative_fee",
        "administrative_fee_factor",
        "administrative_fee_calculation",
        "deposit_fee",
        "fixed_fee",
        "monthly_usage_fee",
        "monthly_recurring_points_billing_percentage",
        "bcc_email_list",
        "cc_email_list",
        "accounts_receivable_email",
        "allow_multiple_participants_per_unit",
        "uses_units",
        "allow_awarding_pending_activation_participants",
        "default_domain_access_key",
        "allow_hierarchy_to_view_social_wall",
        "can_view_hierarchy_social_wall",
        "allow_managers_to_change_email",
        "allow_participants_to_change_email",
        "air_show_programs_tab",
        "air_show_manager_award_tab",
        "air_premium_cost_to_program",
        "air_show_all_event_list",
        "sub_program_groups",
        "show_internal_store",
        "rank_range",
        "approve_grade_id",
        "approve_grade_ids",
        "approve_grade_notification_ids",
        "notification_email_list",
        "updated_at",
        "program_account_holder_id",
    ];
    public function updateProgramExtra($id, array $data)
    {
        $data['updated_at'] = now();
        unset($data['id']);
        unset($data['program_id']);
        unset($data['program_account_holder_id']);
        $programExtra = self::findOrFail($id);
        $programExtra->fill($data);
        $programExtra->save();
        return $programExtra;
    }
}
