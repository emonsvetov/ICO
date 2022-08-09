@extends('layouts.email')

@section('content')

    <table width="600" bgcolor="#ffffff" cellspacing="0" cellpadding="0" style="font-family:Arial, Helvetica, sans-serif;border-color:#bfbaba;
                    border-size:1px;">
        <tbody>
        <tr>
            <td style="padding-bottom:10px;">
                <img
                    src="{{ $imagePath }}nk110-7beb56d0-bb8d-441c-99a9-9adf41701434-v2.jpeg">
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
            <td align="center" style="background-color:#ffffff; border:none;
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
