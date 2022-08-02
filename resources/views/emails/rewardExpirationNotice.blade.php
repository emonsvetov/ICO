@extends('layouts.email')

@section('content')

<table width="600" bgcolor="#ffffff" cellspacing="0" cellpadding="0" style="font-family:Arial, Helvetica, sans-serif; border-left:solid 1px;
                    border-right:solid 1px; border-top:solid 1px;
border-bottom:solid 1px;
                    border-color:#bfbaba; font-size:14px;">
    <tbody>
    <tr>
        <td width="600" height="312">
            <img src="{{ $imagePath }}nk110-036966af-cf2e-49e1-8bc1-8faf38f560d3-v2.jpeg">
            <p style="margin-left:30px; margin-right:30px;">
                Dear {{ $contactFirstName }},
            </p>
            <p style="margin-left:30px; margin-right:30px;">
                You have <strong>{{ $pointsExpiring }}</strong>
                reward points that will expire
                on<strong> {{ $pointsExpirationDate }}</strong>.
                Please log in to your account to
                redeem your rewards today!
                <br>
                &nbsp;
            </p>
        </td>
    </tr>
    <tr>
        <td align="center" style="background-color:#ffffff; border:none;
                          text-align:center;">
            <a href="{{ $contactProgramHost0 }}" class="inf-track-23207"><img src="https://email-templates-media.s3.amazonaws.com/nk110-edc07421-56e4-4b9b-b09b-584844e6843b-v2.png" border="0"></a>&nbsp;<br>
            <span style="font-size:10px;">Unable to see the
                            button?
                            Click  <span style="text-decoration:underline;"><a href="{{ $contactProgramHost0 }}" class="inf-track-23209">Here</a></span>.</span>
            <p>
                <img src="https://email-templates-media.s3.amazonaws.com/nk110-03af2aac-065a-467b-802f-fa510503215b-v2.gif" alt="Merchants" width="600">
            </p>
        </td>
    </tr>
    <tr>
        <td colspan="2" height="14" bgcolor="#FFFFFF" style="margin-left:10px;
                          margin-right:10px; font-size:9px; color:
                          #000000;
                          text-align:center;">
            Please do not reply to this email. If you need
            support please contact your
            program administrator or click <a href="https://incentco.zendesk.com/anonymous_requests/new" class="inf-track-23211">here</a> to submit a ticket.
        </td>
    </tr>
    </tbody>
</table>
@endsection
