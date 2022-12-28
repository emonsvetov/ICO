<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\WithOrganizationScope;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

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
}
