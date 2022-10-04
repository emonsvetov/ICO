<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use ReflectionClass;

/**
 * @example
 * Mail::to('oganshonkov@incentco.com')->send(
 *     new WelcomeEmail('Oleg', 'oganshonkov@incentco.com', 'contact_programHost0')
 * );
 */
class SendgridEmail extends Mailable
{
    use Queueable, SerializesModels;

    const IMAGE_PATH = 'https://email-templates-media.s3.amazonaws.com/';

    public string $type = '';
    protected array $data = [];

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct()
    {
        $this->data['imagePath'] = self::IMAGE_PATH;
    }

    protected function init($arguments)
    {
        $parameters = (new ReflectionClass($this))
            ->getConstructor()
            ->getParameters();

        foreach ($parameters as $key => $parameter) {
            $argument = $arguments[$key] ?? '';
            $this->data[$parameter->name] = $argument;
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = env("MAIL_FROM_ADDRESS");
        $name = env("MAIL_FROM_NAME");

        return $this->view($this->type)
            ->from($address, $name)
            ->cc($address, $name)
            ->bcc($address, $name)
            ->replyTo($address, $name)
            ->subject($this->subject)
            ->with($this->data);
    }
}
