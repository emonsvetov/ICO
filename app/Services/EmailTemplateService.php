<?php

namespace App\Services;

use App\Models\EmailTemplate;

class EmailTemplateService
{

    public function __construct(
    )
    {
    }
    /**
     * @param EmailTemplate $emailTemplate
     * @param array $data
     */
    public function update(EmailTemplate $emailTemplate, array $data)
    {
        $emailTemplate->update($data);
        return $emailTemplate;
    }
}
