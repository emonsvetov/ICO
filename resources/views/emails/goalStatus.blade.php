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
                        style="font-size:60px; font-weight: bold; color: #333333;line-height: .75em;letter-spacing: -3px;">YOUR CURRENT<br>GOAL STATUS</span><br>
                    <hr style="border-top: 4px dashed {{ $template['theme_color'] }}; margin:40px;">
                </div>
                <h3 style="margin-left:30px; margin-right:30px;
color:#381D44;">
                    Your Goal Progress
                </h3>
                <p style="margin-left:30px; margin-right:30px;
font-size:14px;">
                    Dear {{ $contactFirstName }},
                </p>
                <p style="margin-left:30px; margin-right:30px;
font-size:14px;">
                    Your current Goal Progress:
                </p>
                <p style="margin-left:50px; margin-right:30px;
font-size:14px;">
                    <strong>Goal Name:</strong> {{ $goalName }}
                </p>
                <p style="margin-left:50px; margin-right:30px;
font-size:14px;">
                    <strong>Goal Current Progress:</strong>
                    {{ $goalCurrentProgress }} <span style="font-size:11px;">(includes newly added
progress)</span>
                </p>
                <p style="margin-left:50px; margin-right:30px;
font-size:14px;">
                    <strong>Newly Added Progress:</strong>
                    {{ $goalProgress }}
                </p>
                <p style="margin-left:50px; margin-right:30px;
font-size:14px;">
                    <strong>Your Goal Target:</strong> {{ $goalTarget }}
                </p>
                <p style="margin-left:50px; margin-right:30px;
font-size:14px;">
                    <strong>Goal End Date:</strong> {{ $goalEndDate }}
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="border:none;
                          text-align:center; padding-bottom:4px;">
                <a href="{{ $contactProgramHost0 }}" class="inf-track-23201"><img
                        src="{{ $imagePath }}nk110-edc07421-56e4-4b9b-b09b-584844e6843b-v2.png"
                        border="0"></a>&nbsp;<br>
                <span style="font-size:10px;">Unable to see the
                            button?
                            Click  <span style="text-decoration:underline;"><a href="{{ $contactProgramHost0 }}"
                                                                               class="inf-track-23203">Here</a></span>.</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" height="14" bgcolor="#FFFFFF" style="margin-left:10px;
                          margin-right:10px; padding-top:10px;
padding-bottom:10px; font-size:9px; color:
                          #000000; text-align:center;">
                Please do not reply to this email. If you need
                support please contact your
                program administrator or click <a href="https://incentco.zendesk.com/anonymous_requests/new"
                                                  class="inf-track-23205">here</a> to submit a ticket.
            </td>
        </tr>
        </tbody>
    </table>
@endsection
