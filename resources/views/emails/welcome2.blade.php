@extends('layouts.email')

@section('content')

    <table cellspacing="0" cellpadding="0" style="font-family:{{ $template['font_family'] }}, Helvetica, Arial;
border-left:solid 1px;
                    border-right:solid 1px; border-top:solid 1px;
                    border-bottom:solid 1px;
                    border-color:#bfbaba;">
        <tbody>
        <tr>
            <td style="background-color:rgb(244,243,242); font-size: 14px;">
                <div style="width:600px; margin-bottom: 20px; background-color: rgb(241,233,227); text-align: center;">
                    <img style="width: 100%;" src="https://email-templates-media.s3.amazonaws.com/asdas2f3fdssd.png"
                         border="0" />
                </div>
                <p style="margin-left:30px; margin-right:30px;">
                    Welcome to Liv High 5, your Team Member recognition and appreciation program!! As a valuable member of our team, you have been invited to participate and earn Liv Loot and recognition from Liv.
                </p>
                <p style="margin-left:30px; margin-right:30px;">
                    To kick things off, Liv has awarded you $5 of Liv Loot which you can redeem now for a gift card of your choice or save it for later and combine it with other awards.
                </p>
                <p style="margin-left:30px; margin-right:
                            30px;padding-bottom:10px;">
                    Visit <a href="www.livhigh5.com" target="_blank">www.livhigh5.com</a> and sign in with your Office 365 log in to access your account.  You can also download the Incentco app for access on your mobile device.
                </p>
                <p style="margin-left:30px; margin-right:
                            30px;padding-bottom:10px;">
                    Questions?<br/>
                    &nbsp;&nbsp;• Contact <a href="mailto:livwell@livcommunities.com">livwell@livcommunities.com</a> for more information on how to earn rewards.<br/>
                    &nbsp;&nbsp;• Contact <a href="mailto:support@incentco.zedesk.com">support@incentco.zedesk.com</a> for technical assistance with logins, the website and mobile app.
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="background-color: rgb(244,243,242); border:none;
                          text-align:center;">
                <a href="{{ $contactProgramHost0 }}" class="inf-track-23165"><img
                        src="https://email-templates-media.s3.amazonaws.com/nk110-edc07421-56e4-4b9b-b09b-584844e6843b-v2.png"
                        border="0"></a><br>
                <span style="font-size:10px;">Unable to see the
                              button?
                              Click  <span style="text-decoration:underline;"><a href="{{ $contactProgramHost0 }}"
                                                                                 class="inf-track-23167">Here</a></span>.</span>
                <p>
                    <img
                        src="https://email-templates-media.s3.amazonaws.com/nk110-03af2aac-065a-467b-802f-fa510503215b-v2.gif"
                        alt="Merchants" width="600">
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
                                                  class="inf-track-23169">here</a> to submit a ticket.
            </td>
        </tr>
        </tbody>
    </table>
@endsection
