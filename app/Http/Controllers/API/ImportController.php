<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;

// use App\Http\Requests\CSVImportRequest;
// use App\Services\CSVimportHeaderService;
// use App\Services\CSVimportService;
use Illuminate\Support\Facades\Response;
use App\Models\Organization;
use App\Models\Program;
use App\Models\CsvImport;
use App\Models\CsvImportType;

class ImportController extends Controller
{
    public function index(Organization $organization)
    {
        $query = CsvImport::withOrganization($organization);

        $csv_import_type = request()->get('csv_import_type', '');
        if( $csv_import_type )
        {
            $query->whereRelation('csv_import_type', 'context', '=', 'Users');
        }

        $sortby = request()->get('sortby', 'created_at');
        $direction = request()->get('direction', 'desc');
        $orderByRaw = "{$sortby} {$direction}";
        $limit = request()->get('limit', config('global.paginate_limit'));

        $csvImports = $query
        ->with('csv_import_type')
        ->orderByRaw($orderByRaw)
        ->paginate($limit);

        return response($csvImports);
    }
    public function downloadTemplate( Organization $organization, Program $program = null, CsvImportType $csvImportType)
    {
        if( $csvImportType->fields )    {
            $columns = $csvImportType->fields->pluck('csv_column_name');
            $headers = array(
                "Content-type"        => "text/csv",
                "Content-Disposition" => "attachment; filename={$csvImportType->type}-template.csv",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            );
            $callback = function() use($columns) {
                $file = fopen('php://output', 'w');
                fputcsv($file, $columns->toArray());
                fclose($file);
            };
            return response()->stream($callback, 200, $headers);
        }
        // return response()->json(['error' => 'Template not found'], 404);
    }
}
