@php
    $activationLink = false;
    $explode = explode('@', $contactEmail);
    if (isset($explode[1]) && mb_strpos($explode[1], 'brooks.us.com') !== false){
        $user = \App\Models\User::where('email', $contactEmail)
        ->whereHas('roles', function ($query) use ($program) {
            $query->where('roles.name', 'LIKE', config('roles.participant'))
            ->where('model_has_roles.program_id', $program->id);
        })->first();
        $token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
        $activationLink = "{$contactProgramHost0}/invitation?token={$token}";
        Illuminate\Support\Facades\Log::debug("activationLink={$activationLink}");
    }
@endphp

@extends('layouts.email')

@section('content')
    @if($activationLink)
        <table cellspacing="0" cellpadding="0" style="font-family:{{ $template['font_family'] }}, Helvetica, Arial;
border-left:solid 1px;
                    border-right:solid 1px; border-top:solid 1px;
                    border-bottom:solid 1px;
                    border-color:#bfbaba;">
            <tbody>
            <tr>
                <td style="background-color:white; font-size: 14px;">
                    <div style="width:700px; margin-bottom: 20px; text-align: center; margin-bottom: 50px;">
                        <img style="width: 100%;" src="https://email-templates-media.s3.amazonaws.com/asdas2f3fdssd.png"
                             border="0" />
                    </div>
                    <p style="margin-left:30px; margin-right:30px;
                            font-size:14px;">
                        Welcome to Liv High 5, your Team Member recognition and appreciation program!!  As a valuable member of our team, you have been invited to participate and earn Liv Loot and recognition from Liv.
                    </p>
                    <p style="margin-left:30px; margin-right:30px;">
                        To kick things off, Liv has awarded you Liv Loot which you can redeem now for a gift card of your choice or save it for later and combine it with other awards.
                    </p>
                    <p style="margin-left:30px; margin-right:30px;">
                        Visit <a href="{{ $activationLink }}" style="color:#ab0717; font-weight:bold;" target="blank">{{ $activationLink }}</a> to activate your account by logging in with your email and creating a password.
                    </p>
                    <p style="margin-left:30px; margin-right:30px; margin-bottom: 50px;">
                        Questions?
                        <br/>
                        &nbsp;&nbsp; ⦁	Contact <a style="color:#aeed5c" href="mailto:livwell@livcommunities.com">livwell@livcommunities.com</a> for more information on how to earn rewards<br>
                        &nbsp;&nbsp; ⦁	Contact <a style="color:#aeed5c" href="mailto:support@incentco.zedesk.com">support@incentco.zedesk.com</a> for technical assistance with logins, the website and mobile app.
                    </p>
                </td>
            </tr>
            </tbody>
        </table>
    @else
        <table cellspacing="0" cellpadding="0" style="font-family:{{ $template['font_family'] }}, Helvetica, Arial;
border-left:solid 1px;
                    border-right:solid 1px; border-top:solid 1px;
                    border-bottom:solid 1px;
                    border-color:#bfbaba;">
            <tbody>
            <tr>
                <td style="background-color:white; font-size: 14px;">
                    <div style="width:700px; margin-bottom: 20px; text-align: center; margin-bottom: 50px;">
                        <img style="width: 100%;" src="https://email-templates-media.s3.amazonaws.com/asdas2f3fdssd.png"
                             border="0" />
                    </div>
                    <p style="margin-left:30px; margin-right:30px;
                            font-size:14px;">
                        Welcome to Liv High 5, your Team Member recognition and appreciation program!!  As a valuable member of our team, you have been invited to participate and earn Liv Loot and recognition from Liv.

                    </p>
                    <p style="margin-left:30px; margin-right:30px;">
                        To kick things off, Liv has awarded you $10 of Liv Loot which you can redeem now for a gift card of your choice or save it for later and combine it with other awards.
                    </p>
                    <p style="margin-left:30px; margin-right:30px;">
                        Visit <a href="https://ipaliving.sharepoint.com/" style="color:#ab0717; font-weight:bold;" target="blank">ipaliving.sharepoint.com/</a> and sign in with your Office 365 log in to access your account.
                    </p>
                    <p style="margin-left:30px; margin-right:30px; margin-bottom: 50px;">
                        Questions?
                        <br/>
                        &nbsp;&nbsp; ⦁	Contact <a style="color:#aeed5c" href="mailto:livwell@livcommunities.com">livwell@livcommunities.com</a> for more information on how to earn rewards<br>
                        &nbsp;&nbsp; ⦁	Contact <a style="color:#aeed5c" href="mailto:support@incentco.zedesk.com">support@incentco.zedesk.com</a> for technical assistance with logins, the website and mobile app.
                    </p>
                </td>
            </tr>
            </tbody>
        </table>
    @endif
@endsection
