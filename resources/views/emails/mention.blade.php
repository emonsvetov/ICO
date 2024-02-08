@extends('layouts.email')

@section('content')

    <table cellspacing="0" cellpadding="0" style="font-family:Helvetica, Arial;
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
                        style="font-size:50px; font-weight: bold; color: #333333;line-height: .75em;letter-spacing: -3px;">YOU ARE MENTIONED<br>ON THE SOCIAL WALL</span>
                    <hr style="border-top: 4px dashed {{ $template['theme_color'] }}; margin:40px;">
                </div>
                <p style="margin-left:20px; margin-right:20px;">
                    Dear {{ $name }}
                </p>
                <p style="margin-left:20px; margin-right:20px;">
                    You are mentioned on the social wall.
                </p>
                <p style="margin-left:20px; margin-right:20px;">
                    Thanks!
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
