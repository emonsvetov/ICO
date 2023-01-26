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
                        style="font-size:50px; font-weight: bold; color: #333333;line-height: .75em;letter-spacing: -3px;">YOU RECEIVED<br>PEER-TO-PEER POINTS</span>
                    <hr style="border-top: 4px dashed {{ $template['theme_color'] }}; margin:40px;">
                </div>
                <p style="margin-left:30px; margin-right:30px;">
                    Dear {{ $contactFirstName }},
                </p>
                <p style="margin-left:30px; margin-right:30px; padding-bottom:10px;">
                    You received
                    <strong>{{ $awardPoints }} points</strong> to share
                    with your peers.
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style=" border:none;
                          text-align:center; padding-bottom:4px;">
                <a href="{{ $contactProgramHost0 }}" class="inf-track-23189"><img
                        src="{{ $imagePath }}nk110-edc07421-56e4-4b9b-b09b-584844e6843b-v2.png"
                        border="0"></a>&nbsp;<br>
                <span style="font-size:10px;">Unable to see the
                            button?
                            Click  <span style="text-decoration:underline;"><a href="{{ $contactProgramHost0 }}"
                                                                               class="inf-track-23191">Here</a></span>.</span>
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
                                                  class="inf-track-23193">here</a> to submit a ticket.
            </td>
        </tr>
        </tbody>
    </table>
@endsection
