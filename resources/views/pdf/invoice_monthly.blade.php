<?php 
use App\Models\InvoiceType;

$subtotals = 0;
//dont count these
$dontCount = array(
    'Reversal program pays for deposit fee',
    'Reversal program pays for points',
    'Reversal program pays for fixed fee',
    'Reversal program pays for setup fee',
    'Reversal program pays for admin fee',
    'Reversal program pays for usage fee',
    'Reversal program pays for convenience fee',
    'Reversal program pays for monies pending',
    'Reversal program pays for points transaction fee'
);
?>
<table width="100%">
    <tr>
        <td align="left" style="text-align: left;"><img src="{{ public_path() }}/logo/Incentco_Logo.jpeg" style='max-Width:200px' /></td>
        <td></td>
        <td align="right" style="text-align: right;">
            <table width="100%">
                <tr>
                    <td colspan="2">
                        <table width="100%" style="border: thin solid black;">
                            <tr>
                                <td>Invoice #</td>
                                <td align="right">
                                    <?php echo $invoice->invoice_number ?>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td>Date:</td>
                    <td align="right"><?php echo $invoice->date_end ?></td>
                </tr>
                <tr>
                    <td>Due Date:</td>
                    <td align="right">
                        <?php
                        if ($invoice->date_due == $invoice->date_end) {
                            echo 'Due upon receipt';
                        } else {
                            echo $invoice->date_due;
                        }
                        ?>
                    </td>
                </tr>
                <?php if (isset($invoice->invoice_po_number) && strlen($invoice->invoice_po_number) > 0) : ?>
                    <tr>
                        <td>PO Number:</td>
                        <td align="right"><?php echo $invoice->invoice_po_number ?></td>
                    </tr>
                <?php endif; ?>
                <?php if ($invoice->invoice_type->name == InvoiceType::INVOICE_TYPE_ON_DEMAND || $invoice->invoice_type->name == InvoiceType::INVOICE_TYPE_CREDITCARD) : ?>
                    <tr>
                        <td>Total Due:</td>
                        <td align="right"><strong class="invoice-total"> <b><u><?php echo "$", number_format($invoice['total_end_balance'] * -1, 2) ?></u></b>
                            </strong></td>
                    </tr>
                <?php else : ?>
                    <?php if (isset($invoice->journal_summary)) : ?>
                        <tr>
                            <td>Invoice Total:</td>
                            <td align="right"><strong class="invoice-total"> <u><?php echo "$", number_format(($invoice->journal_summary['grand_total']), 2) ?></u></b>
                                </strong></td>
                        </tr>
                    <?php else : ?>
                        <tr>
                            <td>Balance Due:</td>
                            <td align="right"><strong class="invoice-total"> <b><u><?php echo "$", number_format($invoice['total_end_balance'] * -1, 2) ?></u></b>
                                </strong></td>
                        </tr>
                    <?php endif; ?>
                <?php endif; ?>
            </table>
        </td>
    </tr>
</table>
<table width="100%" cellpadding="0" cellspacing="0">
    <tr>
        <td align="left" style="text-align: left;" width="40%">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td>3801 PGA Blvd</td>
                </tr>
                <tr>
                    <td>Suite 600</td>
                </tr>
                <tr>
                    <td>Palm Beach Gardens, FL 33410 <br /> <br />
                    </td>
                </tr>
                <tr>
                    <td>Bill To:</td>
                </tr>
                <tr>
                    <td>
                        <?php
                        if (isset($parent_program_info->name)) {
                            echo $parent_program_info->name;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php
                        if (isset($address_info->address)) {
                            echo $address_info->address;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php
                        if (isset($address_info->address_ext) && $address_info->address_ext != '') {
                            echo $address_info->address_ext;
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>
                        <?php
                        if (isset($address_info->city) || isset($address_info->zip)) {
                            echo $address_info->city . ', ' . $address_info->state_code . ' ' . $address_info->zip;
                        }
                        ?>
                        <br /> <br />
                    </td>
                </tr>
            </table>
        </td>
        <td></td>
        <td align="right" style="text-align: right;" width="60%">
            <table width="100%" style="font-size: 12px;" cellpadding="0" cellspacing="0">
                <tr>
                    <td></td>
                    <td><b><u>Wire Transfer</u></b></td>
                    <td><b><u>ACH Payment</u></b></td>
                </tr>
                <tr>
                    <td>Routing Number (RTN/ABA):</td>
                    <td>021000021</td>
                    <td>102001017</td>
                </tr>
                <tr>
                    <td>Account Number:</td>
                    <td>138091170</td>
                    <td>138091170</td>
                </tr>
                <tr>
                    <td>Bank:</td>
                    <td>Chase Bank, NA</td>
                    <td>Chase Bank, NA</td>
                </tr>
                <tr>
                    <td valign="top">Address:</td>
                    <td>2696 S Colorado Blvd&nbsp;&nbsp;&nbsp;<br /> Denver, CO 80222
                    </td>
                    <td>2696 S Colorado Blvd<br /> Denver, CO 80222
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    <?php if (isset($invoice->journal_summary) && is_array($invoice->journal_summary)) : ?>
        <tr>
            <td colspan="3">
                <table width="100%" style="border: thin solid black;">
                    <tr>
                        <td align="center">Invoice Summary</td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td colspan="3"><br />
                <div>
                    <table width="100%">
                        <?php foreach ($invoice->journal_summary as $i => $line) : ?>

                            <?php if (is_int($i)) : ?>
                                <?php foreach ($line as $key => $value) : ?>
                                    <?php if ($value != 0) : ?>
                                        <tr>
                                            <td align="left"><?php echo $key; ?></td>
                                            <td align="right">$<?php echo number_format($value, 2); ?></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        <?php endforeach; ?>

                        <tr>
                            <td style="border-top: thin black dotted; padding-top: 8px;"><b>Total
                                    Charges</b></td>
                            <td align="right" style="border-top: thin black dotted; padding-top: 8px;"><b>$<?php echo number_format($invoice->journal_summary['grand_total'], 2); ?></b></td>

                        </tr>
                    </table>
                </div> <!--  end of Invoice Summary Table -->
            </td>
        </tr>
    <?php endif; ?>
    <tr>
        <td colspan="3"><br /></td>
    </tr>
    <tr>
        <td colspan="3">
            <table width="100%" style="border: thin solid black;">
                <tr>
                    <td align="center">Invoice Detail</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<table width="100%">
    <tr>
        <td><b>Program</b></td>
        <td><b>Description</b></td>
        <td align="right"><b>Qty</b></td>
        <td align="right"><b>Price</b></td>
        <td align="right"><b>Total</b></td>
    </tr>

    <?php
    foreach ($invoice->invoices as $key => $data) {
    ?>
        <tr>
            <td>
                <?php
                echo $data['info']->program_name;
                ?>
            </td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
        <?php if (is_array($data['info']->debits) && count($data['info']->debits) > 0) : ?>

            <?php if ($invoice['invoice_type']['name'] == InvoiceType::INVOICE_TYPE_ON_DEMAND || $invoice['invoice_type']['name'] == InvoiceType::INVOICE_TYPE_CREDITCARD) : ?>

                <tr>
                    <td></td>
                    <td bgcolor='#dddddd' colspan='4'>Charges</td>
                </tr>


            <?php endif; ?>

            <?php foreach ($data['info']->debits as $key => $row) : ?>
                <?php
                if ($row->amount == 0 || in_array($row->journal_event_type, $dontCount)) {
                    continue;
                }

                $subtotals += $row->amount;
                ?>
                <tr>
                    <td></td>
                    <td>
                        <?php
                        echo $row->friendly_journal_event_type;
                        if (isset($row->event_name) && $row->event_name != '') {
                            echo ' - ' . $row->event_name;
                            if (isset($row->ledger_code) && $row->ledger_code != '')
                                echo ' (' . $row->ledger_code . ')';
                        }
                        ?>
                    </td>
                    <td align="right">
                        <?php echo '' . number_format(round($row->qty, 2, PHP_ROUND_HALF_DOWN), 2) ?>
                    </td>
                    <td align="right">
                        <?php echo '$' . number_format(round($row->ea, 2, PHP_ROUND_HALF_DOWN), 2) ?>
                    </td>
                    <td align="right">
                        $<?php echo number_format(round($row->amount  * -1, 2, PHP_ROUND_HALF_DOWN), 2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php
            foreach ($data['info']->credits as $key => $row) :
                if (strpos($row->journal_event_type, 'Program pays for fixed fee') !== false or strpos($row->journal_event_type, 'Program pays for points') !== false or strpos($row->journal_event_type, 'Program pays for setup fee') !== false or strpos($row->journal_event_type, 'Program pays for admin fee') !== false or strpos($row->journal_event_type, 'Program pays for usage fee') !== false or strpos($row->journal_event_type, 'Program pays for deposit fee') !== false or strpos($row->journal_event_type, 'Program pays for convenience fee') !== false or strpos($row->journal_event_type, 'Program pays for monies pending') !== false or strpos($row->journal_event_type, 'Program pays for points transaction fee') !== false) {
                    continue;
                }
                $subtotals += $row->amount;
                if ($row->amount == 0) {
                    continue;
                }
            ?>
                <tr>
                    <td></td>
                    <td>
                        <?php
                        echo $row->friendly_journal_event_type;
                        if (isset($row->event_name) && $row->event_name != '') {
                            echo ' - ' . $row->event_name;
                        }
                        ?>
                    </td>
                    <td align="right">
                        <?php echo '' . number_format(round($row->qty, 2, PHP_ROUND_HALF_DOWN), 2) ?>
                    </td>
                    <td align="right">
                        <?php echo '$' . number_format(round($row->ea, 2, PHP_ROUND_HALF_DOWN), 2) ?>
                    </td>
                    <td align="right">
                        $<?php echo number_format(round($row->amount  * -1, 2, PHP_ROUND_HALF_DOWN), 2) ?>
                    </td>
                </tr>
            <?php endforeach; ?>

        <?php endif; ?>
        <tr>
            <td colspan="1" align="right"><strong class="invoice-sub-total">
                    <?php echo $data['info']->program_name; ?>
                </strong></td>
            <td colspan="4" align="right" style="border-top: thin black dotted; padding-top: 8px;">
                <?php
                echo '<strong class="invoice-sub-total">' . '$' . number_format(round($subtotals * -1, 2, PHP_ROUND_HALF_DOWN), 2) . '</strong>';
                ?>
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
        </tr>
    <?php
        $subtotals = 0;
    }
    ?>
    <!--             
    <tr>
    <td>
    </td>
    <td>
    </td>
    <td>
    </td>
    <td align="right">
    <strong class="invoice-total">Total Due:</strong>
    </td>
    <td align="right">
    <?php
    echo '<strong class="invoice-total">' . '$' . number_format(round($invoice['total_end_balance'] * -1, 2, PHP_ROUND_HALF_DOWN), 2) . '</strong>';
    ?>
    </td>
    </tr>
    -->
</table>