<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\UserRegisterRequest;
use App\Http\Requests\UserLoginRequest;
use App\Models\User;

class AuthController extends Controller
{
    
    public function register(UserRegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        if ( !$user )
        {
            return response(['errors' => 'User registration failed'], 422);
        }

        $accessToken = $user->createToken('authToken')->accessToken;
        
        return response([ 'user' => $user, 'access_token' => $accessToken]);
    }


    public function login(UserLoginRequest $request)
    {
       
        if (!auth()->attempt( $request->validated() )) {
            return response(['message' => 'Invalid Credentials'], 422);
        }

        $accessToken = auth()->user()->createToken('authToken')->accessToken;

        return response(['user' => auth()->user(), 'access_token' => $accessToken]);

    }


    public function logout (Request $request) {
       
        $token = $request->user()->token();
        $token->revoke();
        $response = ['message' => 'You have been successfully logged out!'];
        return response($response, 200);
    }
}
