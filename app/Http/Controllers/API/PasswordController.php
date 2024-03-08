<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use Illuminate\Support\Facades\Mail;
use Illuminate\Http\Request;
use App\Mail\ForgotCode;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
//use Illuminate\Validation\Rules\Password as RulesPassword;

use App\Models\PasswordReset as ModelPasswordReset;

class PasswordController extends Controller
{
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status == Password::RESET_LINK_SENT) {
            return [
                'status' => __($status)
            ];
        }

        throw \Illuminate\Validation\ValidationException::withMessages([
            'email' => [trans($status)],
        ]);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed'
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => $request->password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status == Password::PASSWORD_RESET) {
            return response([
                'message'=> 'Password reset successfully'
            ]);
        }

        return response([
            'message'=> __($status)
        ], 500);
    }

    public function sendResetCode(Request $request) {

        $data = $request->validate([
            'email' => 'required|email|exists:users',
        ]);

        $user = \App\Models\User::where('email', $data['email'])->first();

        $existing = ModelPasswordReset::where('email', $request->email)->first();
        // Generate random code
        $code = mt_rand(100000, 999999);

        if( !$existing ) {
            // Create a new password reset entry
            $token = Password::broker()->createToken($user);
        }   else {
            $token = $existing->token;
        }

        if( $token ) {
            Mail::to($user->email)->send(new \App\Mail\ForgotCode(['code' => $code]));
            //TODO: You can also include token in the email for confirmation via web link
            $data['code'] = $code;
            $data['created_at'] = now();
            ModelPasswordReset::where('email', $user->email)->update($data);
            return (
                [
                    'success' => true,
                    // 'token' => $token
                ]
            );
        }

        return response([
            'message'=> __('Reset code could not be generated')
        ], 500);
    }

    public function verifyResetCode(Request $request) {

        $request->validate([
            'email' => 'required|email|exists:users',
            'code' => 'required|integer',
        ]);

        $verified = ModelPasswordReset::where('email', $request->email)->where('code', $request->code)->first();

        if( $verified ) {
            ModelPasswordReset::where('email', $verified->email)->update(['code' => null]);
            return (
                [
                    'success' => true
                ]
            );
        }
        return response([
            'error'=> __('Reset code could not be verified')
        ], 404);
    }
}
