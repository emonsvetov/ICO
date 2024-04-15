@extends('layouts.email')

@section('content')
    <table border="0" width="100%" cellspacing="0" cellpadding="0" style="font-family:{{ $template['font_family'] }}, Helvetica, Arial;">
        <tbody>
            <tr>
                <td height="30"> </td>
            </tr>
            <tr>
                <td align="center">
                    <table style="min-width:500px;width:500px" border="0" width="500" cellspacing="0" cellpadding="0"
                        bgcolor="#eeeeee">
                        <tbody>
                            <tr>
                                <td bgcolor="#EEEEEE" width="4"> </td>
                                <td bgcolor="#EEEEEE" width="30" height="36">
                                    <img
                                        style="display: block; visibility: visible;"
                                        src="{{ asset('feeling/logo.png') }}"
                                        alt="" width="" height="30"></td>
                                <td style="font-size:16px;vertical-align:middle;color:#f9922b;padding-top:2px;line-height:20px"
                                    align="left" bgcolor="#EEEEEE">
                                    <strong>{{$program->name}} - How are you feeling survey</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    <table style="min-width:500px;width:500px" border="0" width="500" cellspacing="0" cellpadding="0"
                        bgcolor="#eeeeee">
                        <tbody>
                            <tr>
                                <td bgcolor="#EEEEEE" width="4"> </td>
                                <td align="center" bgcolor="#FFFFFF">
                                    <table border="0" width="100%" cellspacing="0" cellpadding="5">
                                        <tbody>
                                            <tr>
                                                <td style="padding:5px!important" valign="top" bgcolor="white" width="170">
                                                    Type a question
                                                </td>
                                                <td style="padding:5px!important" bgcolor="white">
                                                    <table>
                                                        <tbody>
                                                            <tr>
                                                                <td><a href=".png" target="_blank">
                                                                        <img
                                                                            width="64" height="64"
                                                                            src="{{ asset('feeling/'.$feeling.'.png') }}"
                                                                            style="visibility: visible;">
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td>{{$feeling}}</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            <tr >
                                                <td style="padding:5px!important"
                                                    valign="top" bgcolor="#f3f3f3" width="170">Name</td>
                                                <td style="padding:5px!important"
                                                    bgcolor="#f3f3f3">{{$first_name}} {{$last_name}}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px!important"
                                                    valign="top" bgcolor="white" width="170">Email</td>
                                                <td style="padding:5px!important"
                                                    bgcolor="white">
                                                    <a href="mailto:{{$email}}" target="_blank">
                                                        {{$email}}
                                                    </a>
                                                </td>
                                            </tr>
                                            <tr>
                                                <td style="padding:5px!important"
                                                    valign="top" bgcolor="#f3f3f3" width="170">
                                                </td>
                                                <td style="padding:5px!important"
                                                    bgcolor="#f3f3f3">
                                                    @isset($comment) {!! $comment !!} @endisset
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="font-size:4px" bgcolor="#EEEEEE" height="4"> </td>
                                <td style="font-size:4px" bgcolor="#EEEEEE"> </td>
                                <td style="font-size:4px" bgcolor="#EEEEEE"> </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td height="30"> </td>
            </tr>
        </tbody>
    </table>
@endsection
