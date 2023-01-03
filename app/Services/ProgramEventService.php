<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EmailTemplate;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class ProgramEventService
{

    public function __construct(
    )
    {
    }
    /**
     * @param array $data
     */
    public function create(array $data)
    {
        $data = $this->_handleEmailTemplateData( $data );
        return Event::create( $data );
    }
    /**
     * @param Event $event
     * @param array $data
     */
    public function update(Event $event, array $data)
    {
        $data = $this->_handleEmailTemplateData($data);
        $event->update( $data );
        return $event;
    }

    private function _handleEmailTemplateData(array $data): array
    {
        if(!empty($data['custom_email_template'])) {
            $template['name']  = $data['template_name'];
            $template['content']= $data['email_template'];
            $template['type']= 'program_event';
            $data['email_template_id'] = EmailTemplate::insertGetId( $template);
            unset($data['custom_email_template']);
            unset($data['template_name']);
            unset($data['email_template']);
        }
        return $data;
    }
}
