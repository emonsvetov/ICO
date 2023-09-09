<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CsvImportSettings;
use App\Services\UserImportService;
use Illuminate\Http\Request;

use App\Http\Requests\CSVImportRequest;
use App\Services\CSVimportHeaderService;
use App\Services\CSVimportService;

use App\Models\Organization;
use App\Models\CsvImport;
use App\Models\CsvImportType;

use App\Jobs\ImportUserForProgramValidationJob;
use Illuminate\Support\Collection;

// use Illuminate\Support\Facades\Storage;

// use Aws\S3\S3Client;

// Remove after test
use App\Models\User;
use App\Models\Program;
use App\Http\Traits\UserImportTrait;

use DB;
use Mail;
use DateTime;

// use Illuminate\Support\Facades\Notification;
use App\Notifications\CSVImportNotification;
use App\Mail\templates\WelcomeEmail;

class CsvImportSettingController extends Controller
{
    use UserImportTrait;

    public function index(Organization $organization, string $type)
    {
        $csvImportTypeId = CsvImportType::getIdByName($type);
        $csvImportSettings = CsvImportSettings::getByOrgIdAndTypeId($organization->id, $csvImportTypeId);

        return response($csvImportSettings);
    }

    public function store(Request $request, Organization $organization, CSVimportService $csvImportService)
    {
        $validated = $request->validate([
            'fieldsToMap' => 'required|json',
            'setups' => 'required|json'
        ]);

        $csvImportSetting = $csvImportService->saveSettings($validated, $organization);
        return response(['success' => (bool)$csvImportSetting, 'csvImportSetting' => $csvImportSetting]);

    }
}
