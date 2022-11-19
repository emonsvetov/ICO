<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TangoOrderErrorEmail extends Mailable
{
    use SerializesModels;

    const IMAGE_PATH = 'https://email-templates-media.s3.amazonaws.com/';

    public $toAddress;
    public $data;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($toAddress, $data )
    {
        $this->toAddress = $toAddress;
        $this->data = (array) $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subject = "Incentco - Critical Tango Order Errors";
        $address = env("MAIL_FROM_ADDRESS");
        $name = env("MAIL_FROM_NAME");
        $this->data['imagePath'] = self::IMAGE_PATH;

        return $this->view('emails.tangoOrderError')
            ->to($this->toAddress)
            ->from($address, $name)
            ->cc($address, $name)
            ->bcc($address, $name)
            ->replyTo($address, $name)
            ->subject($subject)
            ->with($this->data);
    }
}
