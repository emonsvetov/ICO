<?php
namespace App\Services\Program;

use Barryvdh\DomPDF\Facade\Pdf;

use App\Services\ProgramService;
use App\Services\InvoiceService;
use App\Models\Program;

class SendMonthlyInvoiceService
{
    // public function __construct(
    //     ProgramService $programService,
    //     CreateInvoiceService $createInvoiceService,
    // ) {
    //     $this->programService = $programService;
    //     $this->createInvoiceService = $createInvoiceService;
    // }

    public function send(Program $program, $invoiceData)
    {
        $response = [];
        $response['invoice_id'] = $invoiceData->id;

        // dump($invoiceData->id);
        // dump($program->accounts_receivable_email);
        // dump("total_end_balance: " . $invoiceData->total_end_balance);
        // dump("custom_invoice_amount: " . $invoiceData->custom_invoice_amount);
        // // dump($invoiceData->toArray());
        // dump('--------------------');
        // return;

		if (! isset ( $program->accounts_receivable_email ) || $program->accounts_receivable_email == '') {
			$response['msg'] = "Program '" . $program->name . "' does not have an accounts receivable email";
		} elseif ($invoiceData->total_end_balance == 0) {
			// invoice ending balance is $0, do not send - feature requested by Janelle/Megha 3/2016
			$response['msg'] = "Program '" . $program->name . "' invoice ending balance is $0";
		} elseif ($invoiceData->custom_invoice_amount == 0) {
			// DHF-135 - suppress all $0 invoices from being created
			$response['msg'] = "Program '" . $program->name . "' Total Invoice Amount is $0";
		} else {
			// Get the PDF file from the service, making sure to base64_decode before writing to disk
			// dd($invoiceData->toArray());
			$invoice_path = storage_path("invoices/" . $program->id);
			if (!\File::exists( $invoice_path )) {
				\File::makeDirectory($invoice_path, 0755, true);
			}

			$invoice_filepath = $invoice_path . '/' . $invoiceData->invoice_number . '-' . time() . '.pdf';

			$pdf = Pdf::loadView('pdf.invoice_monthly', ['invoice' => $invoiceData])
			->save($invoice_filepath);
			dd("Saved");
			$subject = "Incentco Automatic Email Notification";
			$message = "<h3> Attached is your Incentco bill for </h3><b>" . $program->name . "</b> for <b>" . $date_begin . "</b> through <b>" . $date_end . "</b>

            <br/><br/>
            Wire Transfer:<br/>
            Routing Number (RTN/ABA): 021000021<br/>
            Account Number: 138091170<br/>
            Chase Bank, NA<br/>
            2696 S Colorado Blvd<br/>
            Denver, CO 80222<br/>
            <br/><br/>
            ACH Payment:<br/>
            Routing Number (RTN/ABA): 102001017<br/>
            Account Number: 138091170<br/>
            Chase Bank, NA<br/>
            2696 S Colorado Blvd<br/>
            Denver, CO 80222<br/>
            ";
			//Start with Email Class
			//===============================================================================
			$textBody = str_replace('<br/>', '',$message );
			$textBody = str_replace('<h3>', '',$textBody );
			$textBody = str_replace('</h3>', '',$textBody );
			$textBody = str_replace('<b>', '',$textBody);
			$textBody = str_replace('</b>', '',$textBody);


			require_once APPPATH.'/libraries/SesMailer.php';
			file_put_contents('email-log.txt',PHP_EOL."===============NEW INVOICE=======================",FILE_APPEND);
			$mailAgent = new SesMailer();

			$cc_email_list = explode(';', $extra->cc_email_list);
			$bcc_email_list = explode(';', $extra->bcc_email_list);
			$accounts_receivable_email = explode(';', $extra->accounts_receivable_email);
			$extraArgs = array(
				'program_id' => $program_id,
				'email_source' => Log_Email_Sources_Model::MONTHLY_INVOICES,
				'data' => ['file_path' => $invoice_file_path]
			);
			$mailAgent->sendAttachment(null, $accounts_receivable_email, $cc_email_list, $bcc_email_list, $message, $textBody, $subject,$invoice_file_path, 'noreply@incentco.net', $extraArgs);
		}
		return $response;
    }
}