<?php
namespace App\Http\Traits;

use Illuminate\Support\Facades\Storage;

trait MerchantMediaUploadTrait {
    public $_merchant_media_fields = ['logo', 'icon', 'large_icon', 'banner'];
    public function handleMerchantMediaUpload( $request, $oldMerchant, $updating = false ) {
        $uploads = [];
        foreach( $this->_merchant_media_fields as $field ) {
            if($request->hasFile($field)) {
                $logo = $request->file($field);
                if( $logo->isValid() )  {
                    $uploads[$field] = $logo->store('merchants/' . $oldMerchant['id']);
                    //try to find and delete old file
                    if( $updating ) {
                        $oldFile = $oldMerchant[$field];
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