<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(\App\Http\Middleware\CheckExternalToken::class)->group(function () {
    Route::get('/external/get-codes', [App\Http\Controllers\API\GiftcodeController::class, 'getCodes'])->name('external.get-codes');
});
