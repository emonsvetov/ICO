<?php
namespace App\Models\Traits;

use App\Models\CsvImportType;

trait CSVImport
{
    public function getRules( $importTypeType = null ) {
        // pr(get_class());
        if( !$importTypeType ) {
            $className = get_class();
            $str = str_replace("App\Http\Requests\CSVImport", "", $className);
            $str = substr($str, 0, strrpos($str, 'Request'));
            $importTypeType = \Illuminate\Support\Str::snake($str);
        }

        if( $importTypeType ) {
            $csvImportType = CsvImportType::where('type', '=', $importTypeType)->with('fields')->first();
            if( $csvImportType && $csvImportType->fields ) {
                $rules = [];
                foreach($csvImportType->fields as $field) {
                    $rules[$field['name']] = $field['rule'];
                }
                return $rules;
            }
        }
    }
}
