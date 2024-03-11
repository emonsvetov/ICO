<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;

// use App\Http\Requests\CSVImportRequest;
// use App\Services\CSVimportHeaderService;
// use App\Services\CSVimportService;

use App\Models\Organization;
use App\Models\CsvImport;

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
}
