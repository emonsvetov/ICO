<?php
namespace App\Http\Traits;

use App\Models\ProgramTemplate;
use Illuminate\Support\Facades\Storage;

trait ProgramTemplateMediaUploadTrait {
    public function handleProgramTemplateMediaUpload( $request, $program, $updating = false ) {
        $uploads = [];
        foreach( ProgramTemplate::IMAGE_FIELDS as $field ) {
            if($request->hasFile($field)) {
                $logo = $request->file($field);
                if( $logo->isValid() )  {
                    $uploads[$field] = $logo->store('programs/' . $program['id']);
                    //try to find and delete old file
                    if( $updating ) {
                        $oldFile = $program[$field];
                        if( $oldFile )  {
                            Storage::delete( $oldFile );
                        }
                    }
                }
            }
        }
        return $uploads;
    }
}
