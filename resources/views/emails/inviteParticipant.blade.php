@extends('layouts.email')

@section('content')

    <table cellspacing="0" cellpadding="0" style="font-family:{{ $template['font_family'] }}, Helvetica, Arial;
border-left:solid 1px;
                    border-right:solid 1px; border-top:solid 1px;
                    border-bottom:solid 1px;
                    border-color:#bfbaba;">
        <tbody style="background-color:rgb(244,243,242);">
        <tr>
            <td height="312" style="font-size: 14px;">
                <div style="width:600px; height:312px; background-color: rgb(241,233,227); text-align: center;">
                    <img style="margin: 25px;max-height: 80px;" src="{{ url( '/storage/' . $template['small_logo'])}}"
                         border="0"><br>
                    <span
                        style="font-size:60px; font-weight: bold; color: #333333;line-height: .75em;letter-spacing: -3px;">YOU'RE INVITED</span><br>
                    <span style=" font-weight: bold; color: #333333;">YOU"RE INVITED TO PARTICIPATE<br>IN OUR REWARDS PROGRAM</span>
                    <hr style="border-top: 4px dashed {{ $template['theme_color'] }}; margin:40px;">
                </div>
                <p width="600px" style="margin-left:20px;
                              margin-right:20px;font-size:15px;">
                    {{ $contactFirstName }},
                    <br>
                    <br>
                    You're invited to participate in our rewards
                    program. Set-up your password to redeem your
                    points.
                </p>
            </td>
        </tr>
        <tr>
            <td style="border:none; text-align:center;">
                <a href="{{ $contactActivationTokenUrl }}"
                   class="inf-track-23153"><img src="{{ $imagePath }}nk110-edc07421-56e4-4b9b-b09b-584844e6843b-v2.png"
                                                border="0"></a>
                <br>
                <span style="font-size:10px;">Unable to see
                              the button?
                              Click <a href="{{ $contactActivationTokenUrl }}"
                                       style="color:#000;" class="inf-track-23155"> <span
                            style="text-decoration:underline;">Here</span></a>.</span>
            </td>
        </tr>
        <tr>
            <td style="padding-top:10px;
                            padding-bottom:10px;">
                <img src="{{ $imagePath }}nk110-03af2aac-065a-467b-802f-fa510503215b-v2.gif" width="600">
            </td>
        </tr>
        <tr>
            <td bgcolor="#FFFFFF" style="margin-left:10px; margin-right:10px;
                            font-size:9px; color: #000000;
                            text-align:center; padding-top:10px;
                            padding-bottom:14px;">
                Please do not reply to this email. If you
                need support please
                contact your
                program administrator or click <a href="https://incentco.zendesk.com/anonymous_requests/new"
                                                  class="inf-track-23157">here</a> to submit a ticket.
            </td>
        </tr>
        </tbody>
    </table>
@endsection
