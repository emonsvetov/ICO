<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Remember to remove this route. Just for Postman Testing
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['middleware' => ['json.response']], function () {

    Route::post('/v1/login', [App\Http\Controllers\API\AuthController::class, 'login'])->name('api.v1.login');
    Route::post('/v1/register', [App\Http\Controllers\API\AuthController::class, 'register'])->name('api.v1.register');

});

Route::middleware(['auth:api', 'json.response'])->group(function () {
    
    Route::post('/v1/logout', [App\Http\Controllers\API\AuthController::class, 'logout'])->name('api.v1.logout');

});
