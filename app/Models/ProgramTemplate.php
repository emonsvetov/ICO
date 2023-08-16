<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class ProgramTemplate extends BaseModel
{
    use HasFactory;

    const IMAGE_FIELDS = ['small_logo', 'big_logo', 'hero_banner', 'slider_01', 'slider_02', 'slider_03'];
    const DEFAULT_TEMPLATE = [
        'name' => 'Default',
        'font_family' => 'Roboto',
        'button_bg_color' => '#42B0FF',
        'button_color' => '#FCFCFF',
        'button_corner' => '4',
        'theme_color' => '#000',
        'welcome_message' => '',
        'participant_homepage_message'=>'Rewards can be redeemed with leading national retailers offering millions of products and brands. Redeem your rewards when you earn them or save them up for a `rainy day`.',
        'big_logo' => 'theme/default/images/big_logo.png',
        'small_logo' => 'theme/default/images/small_logo.png',
        'hero_banner' => 'theme/default/images/hero_banner.png',
        'slider_01' => 'theme/default/images/slider-01.jpg',
        'slider_02' => 'theme/default/images/slider-02.jpg',
        'slider_03' => 'theme/default/images/slider-03.jpg',
    ];

    protected $guarded = [];
    public $timestamps = true;

}
