<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ProgramTemplateMediaUploadTrait;
use App\Http\Requests\ProgramTemplateRequest;
use App\Models\ProgramTemplate;
use App\Services\ProgramTemplateService;
use App\Models\Organization;
use App\Models\Program;

class ProgramTemplateController extends Controller
{
    use ProgramTemplateMediaUploadTrait;

    private ProgramTemplateService $programTemplateService;

    public function __construct(ProgramTemplateService $programTemplateService)
    {
        $this->programTemplateService = $programTemplateService;
    }

    public function store(ProgramTemplateRequest $request, Organization $organization, Program $program)
    {
        $newProgramTemplate = $this->programTemplateService->create($request, $program);

        return response($newProgramTemplate);
    }

    public function update(ProgramTemplateRequest $request, Organization $organization,  Program $program, ProgramTemplate $programTemplate)
    {
        $newProgramTemplate = $this->programTemplateService->update($request, $programTemplate, $program);

        return response($newProgramTemplate);
    }

}
