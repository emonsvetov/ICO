<?php

namespace App\Mail;

use App\Models\EmailTemplateSender;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Messages\MailMessage;
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
    public string $fromEmail = '';
    public string $fromUsername = '';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct()
    {
        $this->data['imagePath'] = self::IMAGE_PATH;
        $this->data['contactProgramHost0'] = app()->call('App\Services\DomainService@makeUrl');
    }

    protected function init($arguments)
    {
        $parameters = (new ReflectionClass($this))
            ->getConstructor()
            ->getParameters();

        foreach ($parameters as $key => $parameter) {
            $argument = $arguments[$key] ?? '';
            $this->data[$parameter->name] = $argument;

            if( $parameter->name == 'program' && $argument ){
                $argument->loadTemplate();
                $this->data[$parameter->name] = $argument;
                $this->data['template'] = $argument->template??\App\Models\ProgramTemplate::DEFAULT_TEMPLATE;

                /** @var EmailTemplateSender $emailTemplateSender */
                $emailTemplateSender = $argument->getEmailTemplateSender();
                if ($emailTemplateSender){
                    $this->fromEmail = $emailTemplateSender->email;
                    $this->fromUsername = $emailTemplateSender->username;
                }
            }
        }
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $address = $this->fromEmail ?? env("MAIL_FROM_ADDRESS");
        $name = $this->fromUsername ?? env("MAIL_FROM_NAME");

        return $this->view($this->type)
            ->from($address, $name)
            ->cc($address, $name)
            ->bcc($address, $name)
            ->replyTo($address, $name)
            ->subject($this->subject)
            ->with($this->data);
    }

    public function convertToMailMessage(): MailMessage
    {
        $data = $this->build();

        $cc = $data->cc ? $this->prepareArray($data->cc) : null;

        $bcc = $data->cc ? $this->prepareArray($data->bcc) : null;
        $replyTo = $data->replyTo ? $this->prepareArray($data->replyTo) : null;

        $mailMessage = new MailMessage();
        $mailMessage->subject($data->subject);
        $mailMessage->view($data->view, $data->viewData);
        $mailMessage->from = $this->prepareArray($data->from, true);
        if ($cc){
            $mailMessage->cc = $cc;
        }
        if ($bcc){
            $mailMessage->bcc = $bcc;
        }
        if ($replyTo){
            $mailMessage->replyTo = $replyTo;
        }

        return $mailMessage;
    }

    protected function prepareArray(array $arr, $onlyFirst = false): array
    {
        $result = [];
        foreach ($arr as $item) {
            $newArr = [];
            if (isset($item['address'])) {
                $newArr[] = $item['address'];
                if (isset($item['name'])) {
                    $newArr[] = $item['name'];
                }
            }
            if (!empty($newArr)){
                $result[] = $newArr;
            }
        }
        if($onlyFirst){
            $result = array_shift($result);
        }
        return $result ?? [];
    }
}
