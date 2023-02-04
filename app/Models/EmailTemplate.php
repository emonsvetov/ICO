<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EmailTemplate extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];
    const DEFAULTS = [
        'font_family' => 'Roboto',
        'theme_color' => '#fff',
        'button_color' => '#fff',
        'button_bg_color' => '#fff',
        'button_corner' => 0,
        'small_logo' => '',
        'big_logo' => '',
        'hero_banner' => '',
    ];
}
