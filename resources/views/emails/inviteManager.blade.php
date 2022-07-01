<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8"/>
</head>
<body>

<table width="600" bgcolor="#ffffff" cellspacing="0" cellpadding="0" style="font-family:Arial, Helvetica, sans-serif;
border-left:solid 1px;
                    border-right:solid 1px; border-top:solid 1px;
                    border-bottom:solid 1px;
                    border-color:#bfbaba;">
    <tbody>
    <tr>
        <td width="600" height="312">
            <img src="{{ $imagePath }}nk110-95a7c570-71db-4e2c-b134-68eeff7593d1-v2.jpeg">
            <p width="600px" style="margin-left:20px;
                            margin-right:20px;">
                Dear {{ $contactFirstName }},
            </p>
            <p width="600px" style="margin-left:20px;
                            margin-right:20px; padding-bottom:10px;">
                You have been invited to be an administrator for
                our rewards program. Use the
                button below to activate your administrator's
                account.<br>
                <br>
                Thank You!
            </p>
        </td>
    </tr>
    <tr>
        <td style="background-color:#ffffff; border:none;
                          text-align:center;">
            <p>
                <a href="{{ $contactProgramHost0 }}activation?token={{ $contactActivationToken }}" class="inf-track-23147"><img src="{{ $imagePath }}nk110-edc07421-56e4-4b9b-b09b-584844e6843b-v2.png" width="200" height="73" border="0"></a><br>
                <span style="font-size:10px;">Unable to see
                                the button?
                                Click <a href="{{ $contactProgramHost0 }}activation?token={{ $contactActivationToken }}" style="color:#000;" class="inf-track-23149"> <span style="text-decoration:underline;">Here</span></a>.</span>
            </p>
        </td>
    </tr>
    <tr>
        <td colspan="2" height="30" bgcolor="#FFFFFF" style="margin-left:10px;
                              margin-right:10px; font-size:9px; color:
                              #000000; text-align:center;">
            Please do not reply to this email. If you
            need support please contact your
            program administrator or click <a href="https://incentco.zendesk.com/anonymous_requests/new" class="inf-track-23151">here</a> to submit a ticket.
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>
