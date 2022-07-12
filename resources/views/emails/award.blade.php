@extends('layouts.email')

@section('content')

    <table width="600" bgcolor="#ffffff" cellspacing="0" cellpadding="0" style="font-family:Arial, Helvetica, sans-serif;
border-left:solid 1px;
                    border-right:solid 1px; border-top:solid 1px;
                    border-bottom:solid 1px;
                    border-color:#bfbaba;">
        <tbody>
        <tr>
            <td>
                <img src="{{ $imagePath }}nk110-3bdc164b-fce9-4386-b300-ead50f99605c-v2.jpeg">
                <p style="margin-left:20px; margin-right:20px;">
                    {{ $contactFirstName }},
                </p>
                <p style="margin-left:20px; margin-right:20px;">
                    You have been awarded<span style="font-weight:bold;"> {{ $awardPoints }} points</span>.
                </p>
                <p style="margin-left:20px; margin-right:20px;">
                    {{ $awardNotificationBody }}
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="background-color:#ffffff; border:none;
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
