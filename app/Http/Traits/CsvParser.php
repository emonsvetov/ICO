<?php
namespace App\Http\Traits;

trait CsvParser {
    //Just a basic parser now. May need to extend!
    public function CsvToArray( $content )    {
        $rows = array_map('str_getcsv', explode(PHP_EOL, $content));
        $rowKeys = array_shift($rows);
        $formattedData = [];
        foreach ($rows as $row) {
            if( sizeof($row) == sizeof($rowKeys) )  {
                $associatedRowData = array_combine($rowKeys, $row);
                if (empty($keyField)) {
                    $formattedData[] = $associatedRowData;
                } else {
                    $formattedData[$associatedRowData[$keyField]] = $associatedRowData;
                }
            }
        }
        return $formattedData;
    }
}