@php

@endphp

@extends('layouts.email')

@section('content')
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
                         border="0"/>
                </div>

                <p style="margin-left:20px; margin-right:20px; font-size:14px;">
                    Dear {{ $contactFirstName }},
                </p>
                <p style="margin-left:20px; margin-right:20px;">
                    {{ $awardNotificationBody }}
                </p>

                <p style="margin-left:20px; margin-right:30px; margin-bottom: 50px;">&nbsp;</p>
                <p style="margin-left:20px; margin-right:30px; margin-bottom: 50px;">
                    Questions?
                    <br/>
                    &nbsp;&nbsp; ⦁ Contact <a style="color:#aeed5c" href="mailto:livwell@livcommunities.com">livwell@livcommunities.com</a>
                    for more information on how to earn rewards<br>
                    &nbsp;&nbsp; ⦁ Contact <a style="color:#aeed5c" href="mailto:support@incentco.zedesk.com">support@incentco.zedesk.com</a>
                    for technical assistance with logins, the website and mobile app.
                </p>
            </td>
        </tr>
        </tbody>
    </table>
@endsection
