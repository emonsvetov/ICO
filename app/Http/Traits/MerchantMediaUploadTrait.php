<?php
namespace App\Http\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

use App\Models\Merchant;

trait MerchantMediaUploadTrait {
    public $_merchant_media_fields = ['logo', 'icon', 'large_icon', 'banner'];
    public function handleMerchantMediaUpload( Request $request = null, $merchant, $updating = false, $fromUrl = [] ) {
        $uploads = [];
        $disk = config('app.env') == 'local' ? 'local' : 's3';
        $filePath = 'merchants/' . $merchant['id'];

        foreach( Merchant::MEDIA_FIELDS as $field ) {
            if( $fromUrl ) {
                if( isset($fromUrl[$field]) && !empty($fromUrl[$field]) ) {
                    $uploads[$field] = Storage::disk($disk)->put($filePath, file_get_contents(Merchant::MEDIA_SERVER . $fromUrl[$field]));
                }
            }   elseif($request->hasFile($field)) {
                $logo = $request->file($field);
                if( $logo->isValid() )  {
                    $uploads[$field] = Storage::disk($disk)->put($filePath, file_get_contents($logo));
                }
            }
            if( $updating ) {
                //try to find and delete old file
                $oldFile = $merchant[$field];
                if( $oldFile )  {
                    Storage::disk($disk)->delete($oldFile);
                }
            }
        }
        return $uploads;
    }
}
