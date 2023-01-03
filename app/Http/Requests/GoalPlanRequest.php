<?php

namespace App\Http\Requests;

use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Http\FormRequest;
use \Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;
use App\Models\EventType;
use App\Models\GoalPlanType;
use App\Models\Event;
use App\Models\GoalPlan;

class GoalPlanRequest extends FormRequest
{

    public function __construct(ValidationFactory $validationFactory)
    { 
        $sale_type_id = GoalPlanType::getIdByTypeSales();
        //if(!empty($archived_event)) 
        //$archived_event->load('eventType');
        $validationFactory->extend(
            'exceeded_event_type_check',
            function ($attribute, $value, $parameters) use($sale_type_id) {
                $request = $this->all(); 
                if($request['goal_plan_type_id'] == $sale_type_id) {
                    $exceeded_event = Event::getEvent($request['exceeded_event_id']);
                    if(!empty($exceeded_event)) 
                    $exceeded_event->load('eventType');
                    if($exceeded_event->eventType->id != EventType::getIdByTypeStandard()) {
                        return false;
                    }
                    /* TO DO
                    if (! $this->event_templates_model->is_valid_event_template ( $program_account_holder_id, $goal_plan->exceeded_event_template_id )) {
                    throw new InvalidArgumentException ( 'Invalid "goal_plan->exceeded_event_template_id" passed, could not find event_template_id=' . $goal_plan->exceeded_event_template_id, 400 );
                    } */
                 }
                 return true;
            },
            ':Goal Exceeded Event type must be standard'
        );
        $personel_type_id = GoalPlanType::getIdByTypePersonal();
        $validationFactory->extend(
            'achieved_event_type_standard',
            function ($attribute, $value, $parameters) use($personel_type_id) {
                $request = $this->all();
                $achieved_event = Event::getEvent($request['achieved_event_id']);
                if(!empty($achieved_event)) 
                    $achieved_event->load('eventType');
                
               if($request['goal_plan_type_id'] == $personel_type_id) {
                    if($achieved_event->eventType->id != EventType::getIdByTypeStandard()) {
                        return false;
                    }
                }
                 return true;
            },
            ':Goal Exceeded Event type must be standard'
        );

        $recog_type_id = GoalPlanType::getIdByTypeRecognition();
        $validationFactory->extend(
            'achieved_event_type_badge',
            function ($attribute, $value, $parameters) use($recog_type_id) {
                $request = $this->all();
                $achieved_event = Event::getEvent($request['achieved_event_id']);
                if(!empty($achieved_event)) 
                    $achieved_event->load('eventType');
                if($request['goal_plan_type_id'] == $recog_type_id) {
                    if($achieved_event->eventType->id != EventType::getIdByTypeBadge()) {
                        $customMessage="Event type must be badge";
                        return false;
                    }
                }
                
                 return true;
            },
            ':Event type must be badge'
        );
        // TO DO - validate goal plan even type id
        //default :
				//throw new RuntimeException ( "Invalid Goal Plan Type: " . $goal_plan_type->type . ".", 400 );*/
    }
    protected function prepareForValidation()
    {
        $request = $this->all(); 
        if( empty($request['date_begin']) )   {
            $request['date_begin'] = date("Y-m-d"); //default goal plan start start date to be today
         }
		// Default custom expire date to 1 year from today
         if( empty($request['date_end']) )   { //default custom expire date to 1 year from today
            $request['date_end'] = date('Y-m-d', strtotime('+1 year'));
         }
        //$request->goal_measurement_label = '$';
         $request['state_type_id'] = GoalPlan::calculateStatusId($request['date_begin'], $request['date_end']);
         $archived_event = Event::getEvent($request['achieved_event_id']);
		if(!empty($archived_event)) 
		$archived_event->load('eventType');
		$exceeded_event = Event::getEvent($request['exceeded_event_id']);
		if(!empty($exceeded_event)) 
		$exceeded_event->load('eventType');
		//Ques - shoud we haeve to move these in request
		switch ($request['goal_plan_type_id']) {
			case GoalPlanType::getIdByTypeEventcount() :
				// Force the factors to 0 so we don't have to check the goal plan type when we do the awarding
				$request['factor_before'] = 0.0;
				$request['factor_after'] = 0.0;
				//$goal_plan->award_per_progress = false;
				$request['award_email_per_progress'] = false;
				break;
			case GoalPlanType::getIdByTypeRecognition() :
				// Force the factors to 0 so we don't have to check the goal plan type when we do the awarding
				$request['factor_before']=0.0;
				$request['factor_after'] = 0.0;
				$request['award_per_progress'] = false;
				$request['award_email_per_progress'] = false;
				break;
		}
        $this->merge(
            $request
        );
        //pr($this->all()); die;
    }
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
           // 'name' => ['required|string', Rule::unique('goal_plans', 'name')->ignore($this->goal_plans)],
            'next_goal_id'=>'integer|sometimes',
            'previous_goal_id'=>'integer|sometimes', 
            'program_id'=>'required|integer',
            'organization_id'=>'required|integer',
            'name'=>'required|string',
            'goal_measurement_label'=>'required|string',
            'goal_plan_type_id'=>'required|integer',
            'state_type_id'=>'sometimes|integer',
            'default_target'=>'required|numeric',
            //'email_template_id'=>'integer',
            'notification_body'=>'string',
            'achieved_callback_id'=>'string',
            'exceeded_callback_id'=>'string',
            'achieved_event_id'=>'sometimes|integer|achieved_event_type_standard|achieved_event_type_badge',
            //'exceeded_event_id'=>'required_if:goal_plan_type_id,1',//|integer', //required if sales goal (id-1)
            'exceeded_event_id'=>'required_if:goal_plan_type_id,1|exceeded_event_type_check',
            'automatic_progress'=> 'required|boolean',
            'automatic_frequency'=>'required_if:automatic_progress,1',//|string',
            'automatic_value'=>'required_if:automatic_progress,1',//|integer',
            'expiration_rule_id'=>'required|integer',
            'custom_expire_offset'=>'required_if:expiration_rule_id,4',//|integer', //if expiration_rule_id is custom
            'custom_expire_units'=>'required_if:expiration_rule_id,4',//|string', //if expiration_rule_id is custom 
            'annual_expire_month'=>'required_if:expiration_rule_id,5',//|integer', //if expiration_rule_id is annual
            'annual_expire_day'=> 'required_if:expiration_rule_id,5',//|integer',  //if expiration_rule_id is annual integer
            'date_begin'=> 'required|date_format:Y-m-d',
            'date_end'=>'required_if:expiration_rule_id,6|date_format:Y-m-d|after:date_begin', //if expiration_rule_id is specific date
            'factor_before'=>'required_if:goal_plan_type_id,1',//|numeric',
            'factor_after'=>'required_if:goal_plan_type_id,1',//|numeric',
            'is_recurring'=>'sometimes|boolean',
            'award_per_progress'=>'sometimes|boolean',
            'award_email_per_progress'=>'sometimes|boolean',
            'progress_requires_unique_ref_num'=>'sometimes|boolean',
            'progress_notification_email_id'=>'required|integer',
            //'progress_email_template_id'=>'required|integer',// not in old db
            'assign_goal_all_participants_default'=>'sometimes|boolean',
            'created_by'=>'	sometimes|integer',
            'modified_by'=>'sometimes|integer',
            'expired',
        ];
    }
     /**
          * Get the validation messages that apply to the request.
          *
          * @return array
          */
         /* public function messages()
          {
              return [
                //'exceeded_event_id.required' =>"The exceeded event id field is required when goal plan type is 'Sales Goal'",
                /*'oldpassword.required' => Lang::get('userpasschange.oldpasswordrequired'),
                'oldpassword.max' => Lang::get('userpasschange.oldpasswordmax255'),
                'newpassword.required' => Lang::get('userpasschange.newpasswordrequired'),
                'newpassword.min' => Lang::get('userpasschange.newpasswordmin6'),
                'newpassword.max' => Lang::get('userpasschange.newpasswordmax255'),
                'newpassword.alpha_num' =>Lang::get('userpasschange.newpasswordalpha_num'),
                'newpasswordagain.required' => Lang::get('userpasschange.newpasswordagainrequired'),
                'newpasswordagain.same:newpassword' => Lang::get('userpasschange.newpasswordagainsamenewpassword'),
                'username.max' => 'The :attribute field must  have under 255 chars',*/
             // ];
          //}
}