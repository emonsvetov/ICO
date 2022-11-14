<?php
namespace App\Services\Program;

use Illuminate\Support\Facades\Notification;
use Barryvdh\DomPDF\Facade\Pdf;

use App\Notifications\MonthlyInvoiceNotification;
use App\Models\Program;

class SendMonthlyInvoiceService
{
    public function send(Program $program, $invoiceData)
    {
        $response = [];
        $response['invoice_id'] = $invoiceData->id;

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

			$contactProgramHost0 = $program->domains()->first()->name;
			// dump($program->accounts_receivable_email);
			$to = [["email" => $program->accounts_receivable_email, "name"=>"Program Manager"]];
			try{
				Notification::route('mail', $to)
				->notify(new MonthlyInvoiceNotification($to, $invoice_filepath, 
				[
					'program' => $program,
					'date_begin' => date("m d, Y", strtotime($invoiceData['date_begin'])),
					'date_end' => date("m d, Y", strtotime($invoiceData['date_end'])),
					'contactProgramHost0' => $contactProgramHost0
				]));
				$response['msg'] = "Monthly Invoice email for program: {$program->id} sent successfully";
				$response['success'] = true;
			} catch (\Exception $e)	{
				$response['msg'] = sprintf("Error sending monthly invoice notication email for program: %d. Error: %s in line %d", $program->id, $e->getMessage(), $e->getLine());
				$response['error'] = true;
			}
		}
		return $response;
    }
}