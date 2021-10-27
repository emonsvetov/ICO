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



Route::get('/v1/organization/{organization}/participantgroup', [App\Http\Controllers\API\ParticipantGroupController::class, 'index'])->name('api.v1.organization.participantgroup.index');
Route::get('/v1/organization/{organization}/participantgroup/{participantGroup}', [App\Http\Controllers\API\ParticipantGroupController::class, 'show'])->name('api.v1.organization.participantgroup.show');
Route::post('/v1/organization/{organization}/participantgroup', [App\Http\Controllers\API\ParticipantGroupController::class, 'store'])->name('api.v1.organization.participantgroup.store');
Route::put('/v1/organization/{organization}/participantgroup/{participantGroup}', [App\Http\Controllers\API\ParticipantGroupController::class, 'update'])->name('api.v1.organization.participantgroup.update');
//Route::delete('/v1/organization/{organization}/participantgroup/{participantGroup}', [App\Http\Controllers\API\ParticipantGroupController::class, 'destroy'])->name('api.v1.organization.participantgroup.destroy');

Route::get('/v1/organization/{organization}/participantgroup/{participantGroup}/user', [App\Http\Controllers\API\ParticipantGroupUserController::class, 'index'])->name('api.v1.organization.participantgroup.user.index');
Route::post('/v1/organization/{organization}/participantgroup/{participantGroup}/user', [App\Http\Controllers\API\ParticipantGroupUserController::class, 'store'])->name('api.v1.organization.participantgroup.user.store');
Route::delete('/v1/organization/{organization}/participantgroup/{participantGroup}/user', [App\Http\Controllers\API\ParticipantGroupUserController::class, 'destroy'])->name('api.v1.organization.participantgroup.user.destroy');

Route::get('/v1/organization/{organization}/program/{program}/event', [App\Http\Controllers\API\EventController::class, 'index'])->name('api.v1.organization.program.event.index');
Route::get('/v1/organization/{organization}/program/{program}/event/{event}', [App\Http\Controllers\API\EventController::class, 'show'])->name('api.v1.organization.program.event.show');
Route::post('/v1/organization/{organization}/program/{program}/event', [App\Http\Controllers\API\EventController::class, 'store'])->name('api.v1.organization.program.event.store');
Route::put('/v1/organization/{organization}/program/{program}/event/{event}', [App\Http\Controllers\API\EventController::class, 'update'])->name('api.v1.organization.program.event.update');
//Route::delete('/v1/organization/{organization}/program/{program}', [App\Http\Controllers\API\EventController::class, 'destroy'])->name('api.v1.organization.program.destroy');

Route::get('/v1/organization/{organization}/programgroup', [App\Http\Controllers\API\ProgramGroupController::class, 'index'])->name('api.v1.organization.programgroup.index');
Route::get('/v1/organization/{organization}/programgroup/{programGroup}', [App\Http\Controllers\API\ProgramGroupController::class, 'show'])->name('api.v1.organization.programgroup.show');
Route::post('/v1/organization/{organization}/programgroup', [App\Http\Controllers\API\ProgramGroupController::class, 'store'])->name('api.v1.organization.programgroup.store');
Route::put('/v1/organization/{organization}/programgroup/{programGroup}', [App\Http\Controllers\API\ProgramGroupController::class, 'update'])->name('api.v1.organization.programgroup.update');


Route::get('/v1/organization/{organization}/programgroup/{programGroup}/program', [App\Http\Controllers\API\ProgramGroupProgramController::class, 'index'])->name('api.v1.organization.programgroup.user.index');
Route::post('/v1/organization/{organization}/programgroup/{programGroup}/program', [App\Http\Controllers\API\ProgramGroupProgramController::class, 'store'])->name('api.v1.organization.programgroup.user.store');
Route::delete('/v1/organization/{organization}/programgroup/{programGroup}/program', [App\Http\Controllers\API\ProgramGroupProgramController::class, 'destroy'])->name('api.v1.organization.programgroup.user.destroy');

Route::get('/v1/organization/{organization}/program/{program}/event/{event}/eventparticipantgroup', [App\Http\Controllers\API\EventParticipantGroupController::class, 'index'])->name('api.v1.organization.program.event.eventparticipantgroup.index');
//Route::get('/v1/organization/{organization}/program/{program}/event/{event}/eventparticipantgroup/{eventparticipantgroup}', [App\Http\Controllers\API\EventController::class, 'show'])->name('api.v1.organization.program.event.show');
Route::post('/v1/organization/{organization}/program/{program}/event/{event}/eventparticipantgroup', [App\Http\Controllers\API\EventParticipantGroupController::class, 'store'])->name('api.v1.organization.program.event.eventparticipantgroup.store');
//Route::put('/v1/organization/{organization}/program/{program}/event/{event}/participantgroup/{eventparticipantgroup}', [App\Http\Controllers\API\EventController::class, 'update'])->name('api.v1.organization.program.event.update');
Route::delete('/v1/organization/{organization}/program/{program}/event/{event}/eventparticipantgroup', [App\Http\Controllers\API\EventParticipantGroupController::class, 'destroy'])->name('api.v1.organization.program.event.eventparticipantgroup.destroy');


// Remember to remove this route. Just for Postman Testing
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['middleware' => ['json.response']], function () {

    Route::post('/v1/login', [App\Http\Controllers\API\AuthController::class, 'login'])->name('api.v1.login');
    Route::post('/v1/organization/{organization}/register', [App\Http\Controllers\API\AuthController::class, 'register'])->name('api.v1.register');

    Route::post('/v1/password/forgot', [App\Http\Controllers\API\PasswordController::class, 'forgotPassword']);
    Route::post('v1/password/reset', [App\Http\Controllers\API\PasswordController::class, 'reset']);

});

Route::middleware(['auth:api', 'json.response'])->group(function () {
    
    Route::post('/v1/logout', [App\Http\Controllers\API\AuthController::class, 'logout'])->name('api.v1.logout');

    Route::post('/v1/email/verification-notification', [App\Http\Controllers\API\EmailVerificationController::class, 'sendVerificationEmail']);
    Route::get('/v1/email/verify/{id}/{hash}', [App\Http\Controllers\API\EmailVerificationController::class, 'verify'])->name('verification.verify');

});


//ALL USERS WHO HAS VERIFIED THEIR EMAIL ACCOUNTS
Route::middleware(['auth:api', 'json.response', 'verified'])->group(function () {

    Route::get('/v1/organization/{organization}/user', [App\Http\Controllers\API\UserController::class, 'index'])->name('api.v1.organization.user.index');
    Route::get('/v1/organization/{organization}/user/{user}', [App\Http\Controllers\API\UserController::class, 'show'])->name('api.v1.organization.user.show');
    //Route::post('/v1/organization/{organization}/user', [App\Http\Controllers\API\UserController::class, 'store'])->name('api.v1.organization.user.store');
    Route::put('/v1/organization/{organization}/user/{user}', [App\Http\Controllers\API\UserController::class, 'update'])->name('api.v1.organization.user.update')->middleware('can:update,user');;
    //Route::delete('/v1/organization/{organization}/user/{user}', [App\Http\Controllers\API\UserController::class, 'destroy'])->name('api.v1.organization.user.destroy');

});

