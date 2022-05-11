<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;

class GiftcodeCollection extends ResourceCollection
{
    // public static $wrap = 'data';

    public function toArray($request)
    {
        return $this->collection;
    }

    public function with($request)
    {
        return [
            'success' => true,
            'count' => $this->count()
        ];
    }
}
