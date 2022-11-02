<?php
    use App\Models\InvoiceType;
    $subtotals = 0;
    if($invoice['invoice_type']['name']=='On-Demand' || $invoice['invoice_type']['name']=='Credit Card Deposit') 
    {
        $dues = [
            'label' => 'Total Due',
            'amount' => '$'. number_format($invoice['total_end_balance'] * -1, 2)
        ];
    }   
    else 
    {
        if( isset($invoice['journal_summary']) && $invoice['journal_summary'] )  {
            $dues = [
                'label' => 'Invoice Total',
                'amount' => '$'. number_format($invoice['journal_summary']['grand_total'] * -1, 2)
            ];
        }   else {
            $dues = [
                'label' => 'Balance Due',
                'amount' => '$'. number_format($invoice['total_end_balance'] * -1, 2)
            ];
        }
    }
?>
<table border="0" cellpadding="2" cellspacing="1" width="100%" style="border-collapse:collapse">
    <tr>
        <td width="50%">
            <div style="margin-bottom:20px;"><img src="{{ URL::to('/') }}/logo/Incentco_Logo.jpeg" style='max-Width:200px' /></div>
        </td>
        <td width="50%" align="right" valign="top">
            <div>Invoice # {{ $invoice['invoice_number'] }}</div>
            <table>
                <tr>
                    <td>Date:</td>
                    <td>{{ $invoice['date_end'] }}</td>
                </tr>
                <tr>
                    <td>Due Date:</td>
                    <td>{{ $invoice['date_due'] == $invoice['date_end'] ? 'Due upon receipt' : $invoice['date_due'] }}</td>
                </tr>
                @if (isset($invoice['invoice_po_number']) && $invoice['invoice_po_number'])
                    <tr>
                        <td>PO Number:</td>
                        <td>{{ $invoice['invoice_po_number'] }}</td>
                    </tr>
                @endif
                <tr>
                    <td>{{ $dues['label'] }}:</td>
                    <td>{{ $dues['amount'] }}</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<br />
<div style="clear: both;"></div>
<?php if (isset($journal_summary) && is_array($journal_summary)): ?>
    <div class="invoice-detail">
		<label> Invoice Summary </label>
	</div>
	<br />
	<div>
		<table>
           <?php foreach ($journal_summary as $i => $line): ?>
           		
	           	<?php if (is_int($i)):?>
	           	<?php foreach ($line as $key => $value): ?> 
	           	   <?php if ($value != 0) :?>
	           	   <tr>
				<td align="left"><?php echo $key;?></td>
				<td align="right">$<?php echo number_format($value, 2);?></td>
			</tr>
		           <?php endif; ?>
		        <?php endforeach; ?>
	           	<?php endif; ?>
           
           <?php endforeach; ?>
           
           <tr>
				<td style="border-top: thin black dotted; padding-top: 8px;"><b>Total
						Charges</b></td>
				<td align="right"
					style="border-top: thin black dotted; padding-top: 8px;"><b>$<?php echo number_format($journal_summary['grand_total'], 2);?></b></td>

			</tr>
		</table>
	</div>
	<!--  end of Invoice Summary Table -->
	<?php endif; ?>
<br />
<div style="clear: both;"></div>
<div style="text-align:center;border:1px solid #ccc;padding:5px;">
    <label> Invoice Detail </label>
</div>
<br />
<div>
    <table border="0" cellpadding="2" cellspacing="1" width="100%" style="border-collapse:collapse">
        <thead>
            <tr>
                <td valign="top"><b>Program Name</b></td>
                <td valign="top" align="right"><b>Needed Payment Type</b></td>
                <td valign="top" align="right"><b>Total</b></td>
                <td valign="top" align="right"><b>Refunds</b></td>
                <td valign="top" align="right"><b>Payments</b></td>
                <td valign="top" align="right"><b>Amount Due</b></td>
                <td></td>
                <td valign="top" align="right"><b>Amount needing dispersal: <br />
                        <span id="amount_left_to_disperse">$0.00</span></b></td>
            </tr>
        </thead>
        <?php
            $total = 0;
            $total_refunds = 0;
            $total_payments = 0;
            $total_due = 0;
        ?>
    <?php if (is_array($data['info']->debits) && count($data['info']->debits) > 0) :?>

        <?php if ($invoice['invoice_type']['name'] == InvoiceType::INVOICE_TYPE_ON_DEMAND || $invoice['invoice_type']['name'] == InvoiceType::INVOICE_TYPE_CREDITCARD) :?>
            <tr>
            <td></td>
            <td bgcolor='#dddddd' colspan='4'>Charges</td>
            </tr>
        <?php endif; ?>

        <?php foreach($data['info']->debits as $key => $row) : ?>
            <?php $subtotals += $row->amount; ?>
            <?php if ($row->amount == 0) { continue; } ?>
            <tr>
                <td></td>
                <td>
                    <?php
                        echo $row->friendly_journal_event_type;
                        if (isset ( $row->event_name ) && $row->event_name != '') {
                            echo ' - ' . $row->event_name;
                        if ( isset($row->ledger_code) && $row->ledger_code != '')
                            echo ' (' . $row->ledger_code . ')';
                        }
                    ?>
                </td>
                <td align="right">
                    <?php echo ''. number_format($row->qty, 2)?>
                </td>
                <td align="right">
                    <?php echo '$'. number_format($row->ea , 2)?>
                </td>
                <td align="right">
                    $<?php echo number_format($row->amount * -1, 2)?>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php
        foreach ( $data ['info']->credits as $key => $row ) :
            if (strpos ( $row->journal_event_type, 'Program pays for fixed fee' ) !== false or strpos ( $row->journal_event_type, 'Program pays for points' ) !== false or strpos ( $row->journal_event_type, 'Program pays for setup fee' ) !== false or strpos ( $row->journal_event_type, 'Program pays for admin fee' ) !== false or strpos ( $row->journal_event_type, 'Program pays for usage fee' ) !== false or strpos ( $row->journal_event_type, 'Program pays for deposit fee' ) !== false or strpos ( $row->journal_event_type, 'Program pays for convenience fee' ) !== false or strpos ( $row->journal_event_type, 'Program pays for monies pending' ) !== false or strpos ( $row->journal_event_type, 'Program pays for points transaction fee' ) !== false) 
            {
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
                    if (isset ( $row->event_name ) && $row->event_name != '') {
                        echo ' - ' . $row->event_name;
                    }
                ?>
                </td>
                <td align="right">
                    <?php echo ''. number_format($row->qty * -1, 2) ?>
                </td>
                <td align="right">
                    <?php echo '$'. number_format($row->ea * -1, 2)?>
                </td>
                <td align="right">
                    $<?php echo number_format($row->amount * -1, 2)?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
    <tr>
        <td colspan="1" align="right"><strong class="invoice-sub-total">
            <?php echo $data['info']->program_name; ?>
        </strong></td>
        <td colspan="4" align="right"
        style="border-top: thin black dotted; padding-top: 8px;">
            <?php
                echo '<strong class="invoice-sub-total">' . '$' . number_format($subtotals * -1, 2)  . '</strong>';
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
    </table>
</div>