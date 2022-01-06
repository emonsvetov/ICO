<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Routing\Router;

use App\Http\Controllers\API\ProgramController;
use App\Http\Controllers\API\ParticipantGroupController;
use App\Http\Controllers\API\ParticipantGroupUserController;
use App\Http\Controllers\API\EventController;
use App\Http\Controllers\API\ProgramGroupController;
use App\Http\Controllers\API\EventParticipantGroupController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\OrganizationController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\EmailVerificationController;
use App\Http\Controllers\API\PasswordController;
use App\Http\Controllers\API\ProgramGroupProgramController;
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

/*
WARNING WARNING WARNING
NEED TO CREATE MIDDLEWARE THAT CONFIRMS THE CURRENT USER BELONGS TO THE REQUESTED ORGANIZATION
*/

Route::group(
    ['prefix' => 'v1', 'namespace' => 'Api', 'middleware'=>[]], //
    function(Router $router){
    // Route::get('/organization/{organization}/program', [ProgramController::class, 'index'])->name('api.v1.organization.program.index');
    Route::get('/organization/{organization}/program/{program}', [ProgramController::class, 'show'])->name('api.v1.organization.program.show');
    Route::post('/organization/{organization}/program', [ProgramController::class, 'store'])->name('api.v1.organization.program.store');
    //Route::post('/organization/{organization}/program/{program}', [ProgramController::class, 'store'])->name('api.v1.organization.subprogram.store');
    Route::put('/organization/{organization}/program/{program}', [ProgramController::class, 'update'])->name('api.v1.organization.program.update');
    //Route::delete('/organization/{organization}/program/{program}', [ProgramController::class, 'destroy'])->name('api.v1.organization.program.destroy');
    Route::get('/organization/{organization}/participantgroup', [ParticipantGroupController::class, 'index'])->name('api.v1.organization.participantgroup.index');
    Route::get('/organization/{organization}/participantgroup/{participantGroup}', [ParticipantGroupController::class, 'show'])->name('api.v1.organization.participantgroup.show');
    Route::post('/organization/{organization}/participantgroup', [ParticipantGroupController::class, 'store'])->name('api.v1.organization.participantgroup.store');
    Route::put('/organization/{organization}/participantgroup/{participantGroup}', [ParticipantGroupController::class, 'update'])->name('api.v1.organization.participantgroup.update');
    //Route::delete('/organization/{organization}/participantgroup/{participantGroup}', [ParticipantGroupController::class, 'destroy'])->name('api.v1.organization.participantgroup.destroy');

    Route::get('/organization/{organization}/participantgroup/{participantGroup}/user', [ParticipantGroupUserController::class, 'index'])->name('api.v1.organization.participantgroup.user.index');
    Route::post('/organization/{organization}/participantgroup/{participantGroup}/user', [ParticipantGroupUserController::class, 'store'])->name('api.v1.organization.participantgroup.user.store');
    Route::delete('/organization/{organization}/participantgroup/{participantGroup}/user', [ParticipantGroupUserController::class, 'destroy'])->name('api.v1.organization.participantgroup.user.destroy');

    Route::get('/organization/{organization}/program/{program}/event', [EventController::class, 'index'])->name('api.v1.organization.program.event.index');
    Route::get('/organization/{organization}/program/{program}/event/{event}', [EventController::class, 'show'])->name('api.v1.organization.program.event.show');
    Route::post('/organization/{organization}/program/{program}/event', [EventController::class, 'store'])->name('api.v1.organization.program.event.store');
    Route::put('/organization/{organization}/program/{program}/event/{event}', [EventController::class, 'update'])->name('api.v1.organization.program.event.update');
    //Route::delete('/organization/{organization}/program/{program}', [EventController::class, 'destroy'])->name('api.v1.organization.program.destroy');

    Route::get('/organization/{organization}/programgroup', [ProgramGroupController::class, 'index'])->name('api.v1.organization.programgroup.index');
    Route::get('/organization/{organization}/programgroup/{programGroup}', [ProgramGroupController::class, 'show'])->name('api.v1.organization.programgroup.show');
    Route::post('/organization/{organization}/programgroup', [ProgramGroupController::class, 'store'])->name('api.v1.organization.programgroup.store');
    Route::put('/organization/{organization}/programgroup/{programGroup}', [ProgramGroupController::class, 'update'])->name('api.v1.organization.programgroup.update');


    Route::get('/organization/{organization}/programgroup/{programGroup}/program', [ProgramGroupProgramController::class, 'index'])->name('api.v1.organization.programgroup.user.index');
    Route::post('/organization/{organization}/programgroup/{programGroup}/program', [ProgramGroupProgramController::class, 'store'])->name('api.v1.organization.programgroup.user.store');
    Route::delete('/organization/{organization}/programgroup/{programGroup}/program', [ProgramGroupProgramController::class, 'destroy'])->name('api.v1.organization.programgroup.user.destroy');

    Route::get('/organization/{organization}/program/{program}/event/{event}/eventparticipantgroup', [EventParticipantGroupController::class, 'index'])->name('api.v1.organization.program.event.eventparticipantgroup.index');
    //Route::get('/organization/{organization}/program/{program}/event/{event}/eventparticipantgroup/{eventparticipantgroup}', [EventController::class, 'show'])->name('api.v1.organization.program.event.show');
    Route::post('/organization/{organization}/program/{program}/event/{event}/eventparticipantgroup', [EventParticipantGroupController::class, 'store'])->name('api.v1.organization.program.event.eventparticipantgroup.store');
    //Route::put('/organization/{organization}/program/{program}/event/{event}/participantgroup/{eventparticipantgroup}', [EventController::class, 'update'])->name('api.v1.organization.program.event.update');
    Route::delete('/organization/{organization}/program/{program}/event/{event}/eventparticipantgroup', [EventParticipantGroupController::class, 'destroy'])->name('api.v1.organization.program.event.eventparticipantgroup.destroy');


    // Remember to remove this route. Just for Postman Testing
    Route::middleware('auth:api')->get('/user', function (Request $request) {
        return $request->user();
    });


    Route::group(['middleware' => ['json.response']], function () {

        Route::post('/login', [AuthController::class, 'login'])->name('api.v1.login');
        Route::post('/organization/{organization}/register', [AuthController::class, 'register'])->name('api.v1.register');

        Route::post('/password/forgot', [PasswordController::class, 'forgotPassword']);
        Route::post('v1/password/reset', [PasswordController::class, 'reset']);

    });

    Route::middleware(['auth:api', 'json.response'])->group(function () {
        
        Route::post('/logout', [AuthController::class, 'logout'])->name('api.v1.logout');

        Route::post('/email/verification-notification', [EmailVerificationController::class, 'sendVerificationEmail']);
        Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])->name('verification.verify');

    });


    //ALL USERS WHO HAS VERIFIED THEIR EMAIL ACCOUNTS
    Route::middleware(['auth:api', 'json.response', 'verified'])->group(function () {
        Route::get('/organization/{organization}/program', [ProgramController::class, 'index']);
        Route::get('/organization/{organization}/user', [UserController::class, 'index'])->name('api.v1.organization.user.index');
        Route::get('/organization/{organization}/user/{user}', [UserController::class, 'show']);//->middleware('can:view,user')
        //Route::post('/organization/{organization}/user', [UserController::class, 'store'])->name('api.v1.organization.user.store');
        Route::put('/organization/{organization}/user/{user}', [UserController::class, 'update'])->name('api.v1.organization.user.update')->middleware('can:update,user');
        Route::put('/organization/{organization}/users/create', [UserController::class, 'store'])->name('api.v1.organization.user.store');
        //Route::delete('/organization/{organization}/user/{user}', [UserController::class, 'destroy'])->name('api.v1.organization.user.destroy')->middleware('can:delete,user');

        Route::get('/organization', [OrganizationController::class, 'index'])->name('api.v1.organization.index')->middleware('can:viewAny,App\Organization');
        Route::get('/organization/{organization}', [OrganizationController::class, 'show'])->name('api.v1.organization.show')->middleware('can:view,organization');
        Route::post('/organization', [OrganizationController::class, 'store'])->name('api.v1.organization.store')->middleware('can:create,App\Organization');
        Route::put('/organization/{organization}', [OrganizationController::class, 'update'])->name('api.v1.organization.update')->middleware('can:update,organization');
        //Route::delete('/organization/{organization}', [OrganizationController::class, 'destroy'])->name('api.v1.organization.destroy')->middleware('can:delete,organization');

        //ROLES & PERMISSIONS
        Route::get('/organization/{organization}/user/{user}/role', [RoleController::class, 'userRoleIndex'])->name('api.v1.organization.user.roles')->middleware('can:view,App\Role,user');
        Route::put('/organization/{organization}/user/{user}/role/{role}', [RoleController::class, 'assign'])->name('api.v1.organization.user.role.assign')->middleware('can:update,role');
        Route::delete('/organization/{organization}/user/{user}/role/{role}', [RoleController::class, 'revoke'])->name('api.v1.organization.user.role.revoke')->middleware('can:update,role');

        Route::get('/organization/{organization}/role', [RoleController::class, 'index'])->name('api.v1.organization.role.index')->middleware('can:viewAny,App\Role');
        Route::get('/organization/{organization}/role/{role}', [RoleController::class, 'show'])->name('api.v1.organization.role.show')->middleware('can:viewAny,role');
        Route::post('/organization/{organization}/role', [RoleController::class, 'store'])->name('api.v1.organization.role.store')->middleware('can:create,App\Role');
        Route::put('/organization/{organization}/role/{role}', [RoleController::class, 'update'])->name('api.v1.organization.role.update')->middleware('can:update,role');
        Route::delete('/organization/{organization}/role/{role}', [RoleController::class, 'destroy'])->name('api.v1.organization.role.destroy')->middleware('can:delete,role');

        Route::get('/organization/{organization}/permission', [PermissionController::class, 'index'])->name('api.v1.organization.permission.index')->middleware('can:viewAny,App\Role');
        Route::put('/organization/{organization}/role/{role}/permission/{permission}', [PermissionController::class, 'assign'])->name('api.v1.organization.permission.assign')->middleware('can:update,role');
        Route::delete('/organization/{organization}/role/{role}/permission/{permission}', [PermissionController::class, 'revoke'])->name('api.v1.organization.permission.revoke')->middleware('can:update,role');
    });
});
