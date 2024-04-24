<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\CsvImportTypeFieldsRequest;
use App\Http\Requests\CsvImportTypeRequest;
use App\Http\Controllers\Controller;
use App\Models\CsvImportType;
use App\Models\Organization;
use App\Models\Program;

class ImportTypeController extends Controller
{
    public function index(Organization $organization, Program $program)
    {
        $query = CsvImportType::query();

        $context = request()->get('context', '');
        if( $context )
        {
            $query->where('context', 'like', $context);
        }

        return response( $query->get() );
    }

    public function store(CsvImportTypeRequest $request, Organization $organization)
    {
        $data = $request->validated();
        $csvImportType = CsvImportType::create($data);
        return response([ 'csvImportType' => $csvImportType ]);
    }

    public function update(CsvImportTypeRequest $request, Organization $organization, CsvImportType $csvImportType )
    {
        $data = $request->validated();
        $csvImportType->update( $data );
        return response([ 'csvImportType' => $csvImportType ]);
    }

    public function fields(Organization $organization, CsvImportType $csvImportType)
    {
        return response( ['fields' => $csvImportType->fields] );
    }

    public function saveFields(CsvImportTypeFieldsRequest $request, Organization $organization, CsvImportType $csvImportType)
    {
        $existingIds = $csvImportType->fields->pluck('id')->toArray();
        $validated = $request->validated();
        $fields = $validated['fields'];
        $fields = array_map(function($x) use ($csvImportType) {
            $x['csv_import_type_id'] = $csvImportType->id;
            return $x;
        }, $fields);
        $inserts = [];
        $upserts = [];
        $deletes = [];
        $receivedIds = [];
        foreach($fields as $field)  {
            if( !isset($field['id']) || !$field['id'] ) {
                array_push($inserts, $field);
            }   else {
                $receivedIds[] = $field['id'];
                if( in_array($field['id'], $existingIds) )  {
                    array_push($upserts, $field);
                }
            }
        }
        $deletes = array_diff($existingIds, $receivedIds);
        if( $inserts ) {
            $csvImportType->fields()->createMany($inserts);
        }
        if( $upserts ) {
            $csvImportType->fields()->upsert($upserts, uniqueBy: ['id'], update: ['name', 'rule']);
        }
        if( $deletes ) {
            $csvImportType->fields()->whereIn('id', $deletes)->delete();
        }

        $csvImportType->refresh();
        return response( ['fields' => $csvImportType->fields()] );
    }
}
