<?php

namespace App\Models;

use Aws\S3\S3Client;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\WithOrganizationScope;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CsvImport extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use WithOrganizationScope;
    use Notifiable;

    protected $guarded = [];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function csv_import_type()
    {
        return $this->belongsTo(CsvImportType::class);
    }

    public function createCsvImport($fileUpload)
    {
        $disk = config('app.env') == 'local' ? 'local' : 's3';
        $path = Storage::disk($disk)->put($fileUpload['organization_id'] . '/uploads', $fileUpload['upload-file']);

        $csv = [
            'organization_id'       => $fileUpload['organization_id'],
            'csv_import_type_id'    => $fileUpload['csv_import_type_id'],
            'name'                  => $fileUpload['upload-file']->getClientOriginalName(),
            'path'                  => $path,
            'size'                  => $fileUpload['upload-file']->getSize(),
            'rowcount'              => count( file( $fileUpload['upload-file'], FILE_SKIP_EMPTY_LINES ) ),
            'is_processed'          => 1
        ];

        return parent::create($csv);
    }

    public function createCsvAutoImport($fileUpload)
    {
        $disk = config('app.env') == 'local' && !env('AUTO_IMPORT_AWS_ENABLED') ? 'local' : 's3-auto-import';
        $file = $fileUpload['upload-file'];
        $name = Str::random(40);
        $extension = $file->getClientOriginalExtension();
        $saveTo = $fileUpload['organization_id'] . '/' . $fileUpload['csv_import_type_id'];
        $path = Storage::disk($disk)->putFileAs($saveTo, $file, $name . '.' . $extension);

        $csv = [
            'organization_id' => $fileUpload['organization_id'],
            'csv_import_type_id' => $fileUpload['csv_import_type_id'],
            'name' => $fileUpload['upload-file']->getClientOriginalName(),
            'path' => $path,
            'size' => $fileUpload['upload-file']->getSize(),
            'rowcount' => count(file($fileUpload['upload-file'], FILE_SKIP_EMPTY_LINES)),
            'is_processed' => 1
        ];

        return parent::create($csv);
    }

    public static function getAllIsProcessed(){
        return self::where(['is_processed' => 1])
            ->whereNull('deleted_at')
            ->get();
    }

    public static function getAutoImportS3(CsvImport $csvImport)
    {
        try {
            $client = new S3Client([
                'credentials' => [
                    'key' => env('AUTO_IMPORT_AWS_ACCESS_KEY_ID'),
                    'secret' => env('AUTO_IMPORT_AWS_SECRET_ACCESS_KEY'),
                ],
                'region' => env('AWS_REGION'),
                'version' => 'latest',
            ]);

            $client->registerStreamWrapper();
            $bucket = env('AUTO_IMPORT_AWS_BUCKET');
            $key = $csvImport->path;
            $stream = fopen("s3://{$bucket}/{$key}", 'r');
            if ($stream === false) {
                throw new \Exception("CSV file ({$csvImport->id}){$csvImport->name} not found on s3");
            }
            return $stream;
        } catch (\Exception $exception) {
            return $exception->getMessage();
        }
    }
}
