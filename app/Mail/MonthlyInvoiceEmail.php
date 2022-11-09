<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MonthlyInvoiceEmail extends Mailable
{
    use SerializesModels;

    const IMAGE_PATH = 'https://email-templates-media.s3.amazonaws.com/';

    public $toAddress;
    public $data;
    public $attachment;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($toAddress, $attachment, $data )
    {
        $this->toAddress = $toAddress;
        $this->attachment = $attachment;
        $this->data = (array) $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "Incentco Automatic Email Notification";
        $address = env("MAIL_FROM_ADDRESS");
        $name = env("MAIL_FROM_NAME");
        $this->data['imagePath'] = self::IMAGE_PATH;

        return $this->view('emails.monthlyInvoice')
            ->to($this->toAddress)
            ->from($address, $name)
            ->cc($address, $name)
            ->bcc($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->attach($this->attachment)
            ->with($this->data);
    }
}
