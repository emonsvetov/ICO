<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="utf-8"/>
</head>
<body>

<table cellpadding="10" cellspacing="0" bgcolor="#E5E5E5" style="background-color: #666666;
    height: 100%; width: 100%;">
    <tbody>
    <tr>
        <td valign="top" bgcolor="#bfbaba">
            <table align="center" cellpadding="0" cellspacing="0">
                <tbody>
                <tr>
                    <td width="600">
                        <table cellpadding="0" cellspacing="0" style="width: 600px;">
                            <tbody>
                            <tr>
                                <td style="border: none; line-height: normal; margin: 0; padding: 10px 0;
                          text-align: left; white-space: normal;">
                                    <div>
                                        <div>
                                            <div style="background: none; border: none;
                                color: #000000; font-family: arial;
                                font-size: 11px; line-height: normal; margin:
                                0; overflow: auto; padding: 0;
                                text-align: center; white-space: normal;">
                                                Having trouble viewing this email?
                                                <a href="{{ env('REACT_APP_BASE_URL') }}" shape="rect" style="color:#000000;" class="inf-track-no">Click here</a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        @yield('content')

                        <div>
                            <div style="background:
                        none; border: none; color: #000000;
                        font-family:
                        verdana; font-size:
                        10px; line-height: normal; margin: 0;
                        overflow:
                        auto;
                        padding: 0;
                        white-space: normal;">
                                If you no longer wish
                                to receive our emails, click the
                                link
                                below:
                                <br clear="none">
                                <a class="inf-track-no" href="{{ env('REACT_APP_BASE_URL') }}" shape="rect" style="font-size:11px; font-family: arial;
                          color: #000000;">
                                    Unsubscribe</a>
                            </div>
                        </div>
                    </td>
                </tr>
                </tbody>
            </table>
        </td>
    </tr>
    </tbody>
</table>
</body>
</html>
