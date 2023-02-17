<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\GoalPlanRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\GoalPlan;
use App\Models\Program;
use App\Services\GoalPlanService;

class GoalPlanController extends Controller
{
   /* public function store(GoalPlanRequest $request, Organization $organization, Program $program, GoalPlanService $goalplanservice)
    {

		if (!GoalPlan::CONFIG_PROGRAM_USES_GOAL_TRACKER) {
            return response(['errors' => "You can't add goal plan in this program."], 422);
        }
        if ($program->isShellProgram()) {
            return response(['errors' => "Invalid program id passed, you cannot create a goal plan in a shell program"], 422);
        }
        
        $data = $request->validated();

        try{
            $response= $goalplanservice->add_goal_plan($data,$organization,$program);
            return response($response);

        } catch (\Exception $e )    {
            return response(['errors' => 'Goal plan Creation failed','e'=>$e->getMessage()], 422);
        }
	}*/
    public function createUserGoalPlans(UserGoalRequest $request, Organization $organization, Program $program, UserGoalService $usergoalservice) {
		$response = array ();
        $data = $request->validated();
        pr($data); die;
		/*$response ['sent'] = false;
		$submitted_goal = $this->input->post ( 'goal' );
		$user_ids = $this->input->post ( 'user_ids' );
		try {
			if (! is_array ( $user_ids ) || count ( $user_ids ) < 1) {
                return response(['errors' => "No participants (s) selected"], 422);
			}
			// Read the program's goal plan, then copy over the necessary values
			$program_goal = $this->GoalPlan->getGoalPlan ( $data['goal_plan_id'],$program->id );
			$user_goal_plan = new UserGoalObject ();
			// Copy the submitted info into the user's goal plan object
			$user_goal_plan->goal_plan_id = ( int ) $program_goal->id;
			$user_goal_plan->target_value = $data ['target_value'];
			$user_goal_plan->date_begin = $data ['date_begin'];
			$user_goal_plan->date_end = $data ['date_end'];
			$user_goal_plan->factor_before = $data ['factor_before'];
			$user_goal_plan->factor_after = $data ['factor_after'];
			//$user_goal_plan->created_by = ( int ) $this->user->account_holder_id;
			$user_goal_plan->achieved_callback_id = ( int ) $data ['achieved_callback_id'];
			$user_goal_plan->exceeded_callback_id = ( int ) $data ['exceeded_callback_id'];
			// $user_goal_plan-> = $submitted_goal['date_end'];
			$failed_to_create_one = false;
			foreach ( $user_ids as $user_id ) {
				try {
					$user_goal_plan->user_account_holder_id = ( int ) $user_id;
					$this->user_goals_model->create ( ( int ) $this->program->account_holder_id, $user_goal_plan );
				} catch ( Exception $e ) {
					if (! isset ( $response ['errors'] )) {
						$response ['errors'] = '';
					}
					$user = $this->users_model->readByOwnerId ( ( int ) $user_id, ( int ) $this->program->account_holder_id );
					$failed_to_create_one = true;
					$response ['errors'] .= "Failed to create goal plan for user: {$user->email} <br /><br />";
					$response ['errors'] .= $this->_get_exception_message ( $e ) . '<br /><br />';
				}
			}
			if (! $failed_to_create_one) {
				$response ['sent'] = true;
			}
		} catch ( Exception $e ) {
			$response ['errors'] = $this->_get_exception_message ( $e );
		}
		$data ['json'] = json_encode ( $response );
		$this->load->view ( 'json', $data );*/
	
	}

   
}