<?php
namespace App\Http\Traits;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Http\File;

use App\Models\Merchant;

trait MerchantMediaUploadTrait {
    public $_merchant_media_fields = ['logo', 'icon', 'large_icon', 'banner'];
    public function handleMerchantMediaUpload( Request $request = null, $merchant, $updating = false, $fromUrl = [] ) {
        $uploads = [];
        $disk = config('app.env') == 's3' ? 's3' : 'public';
        $filePath = 'merchants/' . $merchant['id'];

        foreach( Merchant::MEDIA_FIELDS as $field ) {
            if( $fromUrl ) {
                if( isset($fromUrl[$field]) && !empty($fromUrl[$field]) && $this->URL_exists(env('V2_API_URL') . $fromUrl[$field]) ) {
                    $extension = pathinfo($fromUrl[$field], PATHINFO_EXTENSION);
                    $filename = Str::random(40) . '.' . $extension;
                    $fullFilepath = $filePath . '/' . $filename;
                    $uploaded = Storage::disk($disk)->put($fullFilepath, file_get_contents(env('V2_API_URL') . $fromUrl[$field]));
                    if( $uploaded ) {
                        $uploads[$field] = $fullFilepath;
                    }
                }
            }   elseif($request->hasFile($field)) {
                $logo = $request->file($field);
                if( $logo->isValid() )  {
                    $uploads[$field] = Storage::disk($disk)->putFile($filePath, $logo);
                }
            }
            if( $updating && $uploads ) {
                if(isset($uploads[$field])) {
                    $oldFile = $merchant[$field];
                    if( $oldFile )  {
                        Storage::disk($disk)->delete($oldFile);
                    }
                }
            }
        }
        return $uploads;
    }
    function URL_exists($url){
        if (!filter_var($url, FILTER_VALIDATE_URL)) return false;
        $headers=get_headers($url);
        return stripos($headers[0],"200 OK")?true:false;
     }
}
