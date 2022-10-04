<?php

namespace App\Models\interfaces;

interface ImageInterface
{

    /**
     * @desc Array of images columns
     * @return array
     */
    public function getImageFields(): array;

    /**
     * @desc Image directory
     * @return string
     */
    public function getImagePath(): string;

}
