<?php 

namespace App\Services;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Organization;
use App\Models\Program;
use App\Notifications\NewCommentsCreated;

class CommentService
{
     /**
     * @param Program $program
     * @param Organization $organization
     * @throws Exception
     */

    public function commentMany(Program $program, Organization $organization, $receivers, $comment)
    {
        try {
            $i =0;
            foreach( $receivers as $receiver)    {
                $user = User::where('account_holder_id', $receiver)->first();
                $result[$i] = $this->commentUser($user, $comment);
                $i++;
            }
            //return $result;
        } catch (Exception $e) {
            $result['error'] = "Error while processing awarding. Error:{$e->getMessage()} in line {$e->getLine()}";
            DB::rollBack();
            return $result;
        }
    }

    public function commentUser($user, $comment){
        // DB::beginTransaction();
        // $user->notify(new NewCommentsCreated($user, $comment));
        // DB::commit();
        $email = $user->email;
   
        try {
            Mail::raw('Hello, this is a plain-text email!', function ($message) {
                $message->to('recipient@example.com')
                        ->subject('Plain Text Email');
            });
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
?>