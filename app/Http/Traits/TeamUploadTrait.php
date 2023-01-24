<?php
namespace App\Http\Traits;

use Illuminate\Support\Facades\Storage;

trait TeamUploadTrait {
    public $_team_media_fields = ['photo'];
    public function handleTeamMediaUpload( $request, $oldTeam, $updating = false ) {
        $uploads = [];
        foreach( $this->_team_media_fields as $field ) {
            if($request->hasFile($field)) {
                $logo = $request->file($field);
                if( $logo->isValid() )  {
                    $uploads[$field] = $logo->store('teams/' . $oldTeam['id']);
                    //try to find and delete old file
                    if( $updating ) {
                        $oldFile = $oldTeam[$field];
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