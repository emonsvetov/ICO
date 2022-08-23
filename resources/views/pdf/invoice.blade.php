<table border="1" cellpadding="2" cellspacing="1" width="100%" style="border-collapse:collapse">
    <tr>
        <td width="50%">
            <div style="margin-bottom:20px;"><img src="{{ URL::to('/') }}/logo/Incentco_Logo.jpeg" style='max-Width:200px' /></div>
            <div  style="margin-bottom:20px;">
                <p>
                    3801 PGA Blvd <br /> Suite 600 <br /> Palm Beach Gardens, FL 33410
                </p>
            </div>
            <div>
                <p>
                    Bill To : <br />
                    @if (isset($invoice['program']))
                        {{ $invoice['program']['name'] }}<br />
                    @endif
                    @if (isset($invoice['program']))
                        {{ $invoice['program']['name'] }}<br />
                    @endif
                </p>
            </div>
        </td>
        <td width="50%" align="right" valign="top">
            fsds
        </td>
    </tr>
</table>