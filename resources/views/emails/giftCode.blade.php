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
                <img src="{{ $imagePath }}nk110-f03be445-aebd-4ad2-b312-96ec3059b5df-v2.jpeg">
                <p style="margin-left:20px; margin-right:20px;
                            font-size:14px;">
                    {{ $contactFirstName }},
                </p>
                <p style="margin-left:20px; margin-right:20px;
                            font-size:14px; padding-bottom:10px;">
                    You have redeemed your reward points<strong>
                    </strong>for the gift code below:<br>
                    <br>
                    {{ $merchantName }}<br>
                    ${{ $giftCodeSkuValue }} Gift Code<br>
                    <br>
                    Redeem your gift code at the following link:<br>
                    {{ $giftCodeUrl }}
                    <br>
                    <br>
                    Your Gift Code: {{ $giftCode }}<br>
                    Your PIN: {{ $giftCodePin }}<br>
                    <br>
                    Redemption Instructions:<br>
                    <span style="font-size:10px;">{{ $merchantRedemptionInstructions }}</span><br>
                </p>
            </td>
        </tr>
        <tr>
            <td align="center" style="background-color:#ffffff; border:none; text-align=" center="center">
                <a href="{{ $contactProgramHost0 }}" class="inf-track-23177"><img
                        src="{{ $imagePath }}nk110-edc07421-56e4-4b9b-b09b-584844e6843b-v2.png" border="0"></a><br>
                <span style="font-size:10px;">Unable to see the
                              button?
                              Click  <span style="text-decoration:underline;"><a href="{{ $contactProgramHost0 }}"
                                                                                 class="inf-track-23179">Here</a></span>.</span>
            </td>
        </tr>
        <tr>
            <td colspan="2" height="30" bgcolor="#FFFFFF" style="margin-left:10px;
                          margin-right:10px; font-size:9px; color: #000000;
                          text-align:center;">
                Please do not reply to this email. If you need
                support please contact your
                program administrator or click <a href="https://incentco.zendesk.com/anonymous_requests/new"
                                                  class="inf-track-23181">here</a> to submit a ticket.
            </td>
        </tr>
        </tbody>
    </table>
@endsection
