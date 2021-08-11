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

Route::get('/v1/organization', [App\Http\Controllers\API\OrganizationController::class, 'index'])->name('api.v1.organization.index');
Route::get('/v1/organization/{organization}', [App\Http\Controllers\API\OrganizationController::class, 'show'])->name('api.v1.organization.show');
Route::post('/v1/organization', [App\Http\Controllers\API\OrganizationController::class, 'store'])->name('api.v1.organization.store');
Route::put('/v1/organization/{organization}', [App\Http\Controllers\API\OrganizationController::class, 'update'])->name('api.v1.organization.update');
//Route::delete('/v1/organization/{organization}', [App\Http\Controllers\API\OrganizationController::class, 'destroy'])->name('api.v1.organization.destroy');

Route::get('/v1/organization/{organization}/program', [App\Http\Controllers\API\ProgramController::class, 'index'])->name('api.v1.organization.program.index');
Route::get('/v1/organization/{organization}/program/{program}', [App\Http\Controllers\API\ProgramController::class, 'show'])->name('api.v1.organization.program.show');
Route::post('/v1/organization/{organization}/program', [App\Http\Controllers\API\ProgramController::class, 'store'])->name('api.v1.organization.program.store');
//Route::post('/v1/organization/{organization}/program/{program}', [App\Http\Controllers\API\ProgramController::class, 'store'])->name('api.v1.organization.subprogram.store');
Route::put('/v1/organization/{organization}/program/{program}', [App\Http\Controllers\API\ProgramController::class, 'update'])->name('api.v1.organization.program.update');
//Route::delete('/v1/organization/{organization}/program/{program}', [App\Http\Controllers\API\ProgramController::class, 'destroy'])->name('api.v1.organization.program.destroy');

Route::get('/v1/organization/{organization}/user', [App\Http\Controllers\API\UserController::class, 'index'])->name('api.v1.organization.user.index');
Route::get('/v1/organization/{organization}/user/{user}', [App\Http\Controllers\API\UserController::class, 'show'])->name('api.v1.organization.user.show');
Route::post('/v1/organization/{organization}/user', [App\Http\Controllers\API\UserController::class, 'store'])->name('api.v1.organization.user.store');
Route::put('/v1/organization/{organization}/user/{user}', [App\Http\Controllers\API\UserController::class, 'update'])->name('api.v1.organization.user.update');
Route::delete('/v1/organization/{organization}/user/{user}', [App\Http\Controllers\API\UserController::class, 'destroy'])->name('api.v1.organization.user.destroy');



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
