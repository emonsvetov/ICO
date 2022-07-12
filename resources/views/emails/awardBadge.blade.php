@extends('layouts.email')

@section('content')

    <table width="600" bgcolor="#ffffff" cellspacing="0" cellpadding="0" style="font-family:Arial, Helvetica, sans-serif; border-left:solid 1px;
                    border-right:solid 1px; border-top:solid 1px;
border-bottom:solid 1px;
                    border-color:#bfbaba; font-size:14px;">
        <tbody>
        <tr>
            <td>
                <img src="{{ $imagePath }}nk110-75933eb3-d85d-4093-aaab-7c450915f0e0-v2.jpeg">
                <p style="margin-left:20px; margin-right:20px;">
                    Dear {{ $contactFirstName }},
                </p>
                <p style="margin-left:20px; margin-right:20px; padding-bottom:10px;">
                    You've been awarded a recognition
                    badge for {{ $eventName }}. Thank you for
                    participating!
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="background-color:#ffffff; border:none;
                          text-align:center; padding-bottom:4px;">
                <a href="{{ $contactProgramHost0 }}" class="inf-track-23183"><img
                        src="{{ $imagePath }}nk110-edc07421-56e4-4b9b-b09b-584844e6843b-v2.png"
                        border="0"></a>&nbsp;<br>
                <span style="font-size:10px;">Unable to see the
                            button?
                            Click  <span style="text-decoration:underline;"><a href="{{ $contactProgramHost0 }}"
                                                                               class="inf-track-23185">Here</a></span>.</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" height="14" bgcolor="#FFFFFF" style="margin-left:10px;
                          margin-right:10px; font-size:9px; color:
                          #000000;
                          text-align:center;">
                Please do not reply to this email. If you need
                support please contact your
                program administrator or click <a href="https://incentco.zendesk.com/anonymous_requests/new"
                                                  class="inf-track-23187">here</a> to submit a ticket.
            </td>
        </tr>
        </tbody>
    </table>
@endsection
