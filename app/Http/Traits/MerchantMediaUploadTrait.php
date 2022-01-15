<?php
namespace App\Http\Traits;

trait MerchantMediaUploadTrait {
    public $_merchant_media_fields = ['logo', 'icon', 'large_icon', 'banner'];
    public function handleMerchantMediaUpload( $request, $mechant_id ) {
        $uploads = [];
        foreach( $this->_merchant_media_fields as $field ) {
            if($request->hasFile($field)) {
                $logo = $request->file($field);
                if( $logo->isValid() )  {
                    $uploads[$field] = $logo->store('merchants/' . $mechant_id);
                }
            }
        }
        return $uploads;
    }
}