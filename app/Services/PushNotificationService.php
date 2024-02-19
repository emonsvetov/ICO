<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

use App\Models\PushNotificationToken;
use App\Models\Program;
use App\Models\User;

class PushNotificationService
{
    public array $responses = [];
    public function firstOrCreate(Program $program, $data )  {
        $token = PushNotificationToken::where('user_id', $data['user_id'])
            ->where('token', 'LIKE', $data['token'])
            ->first();
        if( !$token )   {
            $token = PushNotificationToken::create($data);
        }
        return $token;
    }

    public function notifySingleUser( User $user, $data = [] )    {
        $tokens = [];
        if( $user->push_tokens() )
        {
            foreach( $user->push_tokens() as $token )   {
                $tokens[] = $token->token;
            }
        }

        if( $tokens )   {
            $this->__notify(
                [
                    'to'=>$tokens,
                    'title'=>$data['title'],
                    'body'=>$data['body'],
                    'data'=>$data['data']
                ]
            );
        }
    }

    public function notifyUsersByProgram( Program $program, $data = [] )    {
        $tokens = [];
        $pushTokens = PushNotificationToken::where('program_id', $program->id)
        ->get();
        if( !$pushTokens->empty() )
        {
            foreach( $pushTokens as $token )   {
                $tokens[] = $token->token;
            }
        }

        if( $tokens )   {
            $this->__notify(
                [
                    'to'=>$tokens,
                    'title'=>$data['title'],
                    'body'=>$data['body'],
                    'data'=>$data['data']
                ]
            );
        }
    }

    public function notifyMultipleUsers( $userIds = [], $data = [] )    {
        $tokens = [];
        $pushTokens = PushNotificationToken::whereIn('user_id', $userIds)
        ->get();
        if( !$pushTokens->empty() )
        {
            foreach( $pushTokens as $token )   {
                $tokens[] = $token->token;
            }
        }

        if( $tokens )   {
            $this->__notify(
                [
                    'to'=>$tokens,
                    'title'=>$data['title'],
                    'body'=>$data['body'],
                    'data'=>$data['data']
                ]
            );
        }
    }

    private function __notify( $params = []) {

        if( !$params['to'] ) return;

        $response = Http::post('https://exp.host/--/api/v2/push/send',
            [
                'to'=>$params['to'],
                'title'=>$params['title'],
                'body'=>$params['body'],
                'data'=>$params['data']
            ]
        );

        if($response->status() == 200)	{
			array_push($this->responses, $response);
			// $responseBody = json_decode($response->body());
			// pr($responseBody);
		}	else if($response->status() == 400)	{
			$res = json_decode($response->body());
			foreach( $res->errors as $error )	{
				if(isset($error->code) && $error->code == "PUSH_TOO_MANY_EXPERIENCE_IDS")	{
					// pr($error->details);
					foreach( $error->details as $experienceId => $expTokens)	{
						// pr($expTokens);
						$this->__notify(
                            [
                                'to'=>$expTokens,
                                'title'=>$params['title'],
                                'body'=>$params['body'],
                                'data'=>$params['data']
                            ]
                        );
						array_push($this->responses, $response);
					}
				}
			}
		}
    }
}

