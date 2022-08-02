@extends('layouts.email')

@section('content')
    <table width="600" bgcolor="#ffffff" cellspacing="0" cellpadding="0" style="font-family:Arial, Helvetica, sans-serif; border-left:solid 1px;
                    border-right:solid 1px; border-top:solid 1px;
                    border-bottom:solid 1px;
                    border-color:#bfbaba; font-size:14px;">
        <tbody>
        <tr>
            <td height="312">
                <img src="{{ $imagePath }}nk110-81847cbc-a977-43ee-9e5f-598438fe45a9-v2.jpeg">
                <p style="margin-left:30px; margin-right:30px;">
                    Dear {{ $contactFirstName }},
                </p>
                <p style="margin-left:30px; margin-right:30px;">
                    Use the button below or following link to reset
                    your
                    password:
                    <a href="{{ $contactProgramHost0 }}password-reset?token={{ $contactPasswordResetToken }}"
                       class="inf-track-23213">Reset
                        Password</a>.
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="background-color:#ffffff; border:none; text-align=" center="center">
                &nbsp;<br>
                <a href="{{ $contactProgramHost0 }}password-reset?token={{ $contactPasswordResetToken }}"
                   class="inf-track-23215"><img src="{{ $imagePath }}nk110-edc07421-56e4-4b9b-b09b-584844e6843b-v2.png"
                                                border="0"></a><br>
                <span style="font-size:10px;">Unable to see the
                              button?
                              Click  <span style="text-decoration:underline;"><a
                            href="{{ $contactProgramHost0 }}password-reset?token={{ $contactPasswordResetToken }}"
                            class="inf-track-23217">Here</a></span>.</span>
                <br>
                &nbsp;
            </td>
        </tr>
        <tr>
            <td colspan="2" height="14" bgcolor="#FFFFFF" style="margin-left:10px;
                          margin-right:10px; font-size:9px; color: #000000;
                          text-align:center;">
                Please do not reply to this email. If you need
                support please contact your
                program administrator or click <a href="https://incentco.zendesk.com/anonymous_requests/new"
                                                  class="inf-track-23219">here</a> to submit a ticket.
            </td>
        </tr>
        </tbody>
    </table>
@endsection
