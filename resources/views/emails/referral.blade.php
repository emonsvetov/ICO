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
                        style="font-size:50px; font-weight: bold; color: #333333;line-height: .75em;letter-spacing: -3px;">YOU HAVE RECEIVED<br>A SUBMISSION</span>
                    <hr style="border-top: 4px dashed {{ $template['theme_color'] }}; margin:40px;">
                </div>
                <p style="margin-left:20px; margin-right:20px;">
                    {{ $contactFirstName }},
                </p>
                <p style="margin-left:20px; margin-right:20px;">
                    Congratulations! You've just received a submission from {{$referrer_first_name}} {{$referrer_last_name}}.
                </p>
                @if (empty($referee_first_name) && empty($referee_last_name) && empty($referee_email))
                    <p style="margin-left:20px; margin-right:20px;">
                        Full name: {{$referee_first_name}} {{$referee_last_name}}
                    </p>
                    <p style="margin-left:20px; margin-right:20px;">
                        Email: {{$referee_email}}
                    </p>
                    @if (!empty($referee_area_code) && !empty($referee_phone))
                        <p style="margin-left:20px; margin-right:20px;">
                            Phone number: {{ $referee_area_code }}-{{ $referee_phone }}
                        </p>
                    @endif
                @endif
                <p style="margin-left:20px; margin-right:20px;">
                    Log-in to your dashboard to view the submission in the reports section. Remember to give a reward to increase engagement AND get more submissions!
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style=" border:none;
                          text-align:center; padding-top:4px;">
                <a href="{{ $contactProgramHost0 }}" class="inf-track-23171"><img
                        src="{{ $imagePath }}nk110-edc07421-56e4-4b9b-b09b-584844e6843b-v2.png" border="0"></a><br>
                <span style="font-size:10px;">Unable to see the
                              button?
                              Click  <span style="text-decoration:underline;"><a href="{{ $contactProgramHost0 }}"
                                                                                 class="inf-track-23173">Here</a></span>.</span>
                <p>
                    <img src="{{ $imagePath }}nk110-03af2aac-065a-467b-802f-fa510503215b-v2.gif" alt="Merchants"
                         width="600">
                </p>
            </td>
        </tr>
        <tr>
            <td colspan="2" height="30" bgcolor="#FFFFFF" style="margin-left:10px;
                          margin-right:10px; font-size:9px; color: #000000;
                          text-align:center;">
                Please do not reply to this email. If you need
                support please contact your
                program administrator or click <a href="https://incentco.zendesk.com/anonymous_requests/new"
                                                  class="inf-track-23175">here</a> to submit a ticket.
            </td>
        </tr>
        </tbody>
    </table>
@endsection
