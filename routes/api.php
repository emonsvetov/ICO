<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use App\Http\Controllers\API\SocialWallPostController;
use App\Http\Controllers\API\SocialWallPostTypeController;

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

Route::group(
    ['prefix' => 'v1', 'namespace' => 'Api'],
    function(Router $router){
        Route::get('/', function(){
            return "Did you forget where you placed your keys??";
        });
    }
);

/*
WARNING WARNING WARNING
NEED TO CREATE MIDDLEWARE THAT CONFIRMS THE CURRENT USER BELONGS TO THE REQUESTED ORGANIZATION
*/

Route::get('/v1/organization/{organization}/program/{program}/merchant',[App\Http\Controllers\API\ProgramMerchantController::class, 'index'])->name('api.v1.program.merchant.index'); //Need to load for guest participant/manager on home page.

Route::post('/v1/organization/{organization}/userimportheaders', [App\Http\Controllers\API\UserImportController::class, 'userHeaderIndex']);
Route::post('/v1/organization/{organization}/userimport', [App\Http\Controllers\API\UserImportController::class, 'userFileImport']);
Route::post('/v1/organization/{organization}/user-auto-import', [App\Http\Controllers\API\UserImportController::class, 'userFileAutoImport']);
Route::get('/v1/organization/{organization}/userimport', [App\Http\Controllers\API\UserImportController::class, 'index']);
Route::get('/v1/organization/{organization}/userimport/{csvImport}', [App\Http\Controllers\API\UserImportController::class, 'show']);

Route::post('/v1/organization/{organization}/csv-import-setting', [App\Http\Controllers\API\CsvImportSettingController::class, 'store']);
Route::get('/v1/organization/{organization}/csv-import-setting/{type?}', [App\Http\Controllers\API\CsvImportSettingController::class, 'index']);

Route::post('/v1/organization/{organization}/addawarduserimportheaders', [App\Http\Controllers\API\UserImportController::class, 'addAwardUserHeaderIndex']);
Route::post('/v1/organization/{organization}/awarduserimportheaders', [App\Http\Controllers\API\UserImportController::class, 'awardUserHeaderIndex']);

// Route::post('/v1/organization/{organization}/addawarduserimport', [App\Http\Controllers\API\AddAwardUserImportController::class, 'addAwardUserFileImport']);
// Route::get('/v1/organization/{organization}/addawarduserimport', [App\Http\Controllers\API\AddAwardUserImportController::class, 'index']);
// Route::get('/v1/organization/{organization}/addawarduserimport/{csvImport}', [App\Http\Controllers\API\AddAwardUserImportController::class, 'show']);

Route::post('/v1/organization/{organization}/eventimportheaders', [App\Http\Controllers\API\EventImportController::class, 'eventHeaderIndex']);
Route::post('/v1/organization/{organization}/eventimport', [App\Http\Controllers\API\EventImportController::class, 'eventFileImport']);
Route::get('/v1/organization/{organization}/eventimport', [App\Http\Controllers\API\EventImportController::class, 'index']);
Route::get('/v1/organization/{organization}/eventimport/{csvImport}', [App\Http\Controllers\API\EventImportController::class, 'show']);

Route::post('/v1/organization/{organization}/programimportheaders', [App\Http\Controllers\API\ProgramImportController::class, 'programHeaderIndex']);
Route::post('/v1/organization/{organization}/programimport', [App\Http\Controllers\API\ProgramImportController::class, 'programFileImport']);
Route::get('/v1/organization/{organization}/programimport', [App\Http\Controllers\API\ProgramImportController::class, 'index']);
Route::get('/v1/organization/{organization}/programimport/{csvImport}', [App\Http\Controllers\API\ProgramImportController::class, 'show']);



Route::get('/v1/organization/{organization}/event_icons', [App\Http\Controllers\API\EventIconController::class, 'index'])->name('api.v1.event_icons.index');
Route::get('/v1/organization/{organization}/eventicondefault', [App\Http\Controllers\API\EventIconController::class, 'default']);

Route::post('/v1/organization/{organization}/event_icons', [App\Http\Controllers\API\EventIconController::class, 'store'])->name('api.v1.event_icons.store');
Route::delete('/v1/organization/{organization}/event_icons/{eventIcon}', [App\Http\Controllers\API\EventIconController::class, 'delete']);

Route::get('/v1/organization/{organization}/participantgroup', [App\Http\Controllers\API\ParticipantGroupController::class, 'index'])->name('api.v1.organization.participantgroup.index');
Route::get('/v1/organization/{organization}/participantgroup/{participantGroup}', [App\Http\Controllers\API\ParticipantGroupController::class, 'show'])->name('api.v1.organization.participantgroup.show');
Route::post('/v1/organization/{organization}/participantgroup', [App\Http\Controllers\API\ParticipantGroupController::class, 'store'])->name('api.v1.organization.participantgroup.store');
Route::put('/v1/organization/{organization}/participantgroup/{participantGroup}', [App\Http\Controllers\API\ParticipantGroupController::class, 'update'])->name('api.v1.organization.participantgroup.update');
//Route::delete('/v1/organization/{organization}/participantgroup/{participantGroup}', [App\Http\Controllers\API\ParticipantGroupController::class, 'destroy'])->name('api.v1.organization.participantgroup.destroy');

Route::get('/v1/organization/{organization}/participantgroup/{participantGroup}/user', [App\Http\Controllers\API\ParticipantGroupUserController::class, 'index'])->name('api.v1.organization.participantgroup.user.index');
Route::post('/v1/organization/{organization}/participantgroup/{participantGroup}/user', [App\Http\Controllers\API\ParticipantGroupUserController::class, 'store'])->name('api.v1.organization.participantgroup.user.store');
Route::delete('/v1/organization/{organization}/participantgroup/{participantGroup}/user', [App\Http\Controllers\API\ParticipantGroupUserController::class, 'destroy'])->name('api.v1.organization.participantgroup.user.destroy');

Route::group([
    'prefix' => '/v1/organization/{organization}/program/{program}',
], function ()
{
    Route::group([
        'prefix' => '/event',
    ], function ()
    {
        Route::get('', [App\Http\Controllers\API\EventController::class, 'index'])->name('api.v1.organization.program.event.index')->middleware('can:view,App\ProgramEvent,organization,program');
        Route::get('/{event}', [App\Http\Controllers\API\EventController::class, 'show'])->name('api.v1.organization.program.event.show')->middleware('can:view,App\ProgramEvent,organization,program,event');
        Route::post('', [App\Http\Controllers\API\EventController::class,'store'])->name('api.v1.organization.program.event.store')->middleware('can:create,App\ProgramEvent,organization,program');
        Route::put('{event}', [App\Http\Controllers\API\EventController::class,'update'])->name('api.v1.organization.program.event.update')->middleware('can:update,App\ProgramEvent,organization,program,event');
        Route::delete('{event}', [App\Http\Controllers\API\EventController::class,'delete'])->name('api.v1.organization.program.event.delete')->middleware('can:delete,App\ProgramEvent,organization,program,event');
    });
    Route::group([
        'prefix' => '/event-award-level',
    ], function ()
    {
        Route::put('/{event}', [App\Http\Controllers\API\EventController::class,'storeAwardLevel'])->name('api.v1.organization.program.event.storeAwardLevel');
        Route::delete('/{event}', [App\Http\Controllers\API\EventController::class,'deleteAwardLevel'])->name('api.v1.organization.program.event.deleteAwardLevel');
    });
});

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

    Route::post('/v1/sso-login', [App\Http\Controllers\API\AuthController::class, 'ssoLogin'])->name('api.v1.ssoLogin');
    Route::post('/v1/sso-add-token', [App\Http\Controllers\API\AuthController::class, 'ssoAddToken'])->name('api.v1.ssoAddToken');
    Route::post('/v1/login', [App\Http\Controllers\API\AuthController::class, 'login'])->name('api.v1.login');
    Route::post('/v1/admin/login', [App\Http\Controllers\API\AuthController::class, 'adminLogin'])->name('api.v1.adminLogin');
    Route::post('/v1/mobileapp-login', [App\Http\Controllers\API\AuthController::class, 'mobileAppLogin'])->name('api.v1.mobileLogin');
    Route::post('/v1/register', [App\Http\Controllers\API\AuthController::class, 'register'])->name('api.v1.register');
    Route::post('/v1/generate-2fa-secret', [App\Http\Controllers\API\AuthController::class, 'generate2faSecret'])->name('api.v1.generate2faSecret');
    Route::post('/v1/password/forgot', [App\Http\Controllers\API\PasswordController::class, 'forgotPassword']);
    Route::post('/v1/password/reset', [App\Http\Controllers\API\PasswordController::class, 'reset']);
    Route::post('/v1/forgot/code', [App\Http\Controllers\API\PasswordController::class, 'sendResetCode']);
    Route::post('/v1/forgot/verify-code', [App\Http\Controllers\API\PasswordController::class, 'verifyResetCode']);

    Route::get('/v1/domain', [App\Http\Controllers\API\DomainController::class, 'getProgram']);

    Route::post('/v1/invitation/accept', [App\Http\Controllers\API\InvitationController::class, 'accept']);
});

Route::middleware(['auth:api', 'json.response'])->group(function () {

    Route::post('/v1/logout', [App\Http\Controllers\API\AuthController::class, 'logout'])->name('api.v1.logout');

    Route::post('/v1/email/verification-notification', [App\Http\Controllers\API\EmailVerificationController::class, 'sendVerificationEmail']);
    Route::get('/v1/email/verify/{id}/{hash}', [App\Http\Controllers\API\EmailVerificationController::class, 'verify'])->name('verification.verify');

});


//ALL USERS WHO HAS VERIFIED THEIR EMAIL ACCOUNTS
Route::middleware(['auth:api', 'json.response', 'verified'])->group(function () {

    //User routes

    Route::get('/v1/organization/{organization}/user', [App\Http\Controllers\API\UserController::class, 'index'])->middleware('can:viewAny,App\User,organization');
    Route::get('/v1/organization/{organization}/user/{user}', [App\Http\Controllers\API\UserController::class, 'show'])->middleware('can:view,App\User,organization,user');
    Route::post('/v1/organization/{organization}/user', [App\Http\Controllers\API\UserController::class, 'store'])->middleware('can:create,App\User');
    Route::put('/v1/organization/{organization}/user/{user}', [App\Http\Controllers\API\UserController::class, 'update'])->middleware('can:update,App\User,organization,user');
    Route::put('/v1/organization/{organization}/users/create', [App\Http\Controllers\API\UserController::class, 'store'])->middleware('can:create,App\User,organization');
    Route::get('/v1/organization/{organization}/user/{user}/history', [App\Http\Controllers\API\UserController::class, 'history'])->middleware('can:view,App\User,organization,user');
    Route::get('/v1/organization/{organization}/user/{user}/{program}/reclaim-items', [App\Http\Controllers\API\UserController::class, 'reclaimItems'])->middleware('can:view,App\User,organization,user');
    Route::post('/v1/organization/{organization}/user/reclaim', [App\Http\Controllers\API\UserController::class, 'reclaim'])->middleware('can:view,App\User,organization,user');
    Route::get('/v1/organization/{organization}/user/{user}/gift-codes-redeemed', [App\Http\Controllers\API\UserController::class, 'giftCodesRedeemed'])->middleware('can:view,App\User,organization,user');
    Route::get('/v1/organization/{organization}/user/{user}/change-logs', [App\Http\Controllers\API\UserController::class, 'changeLogs'])->middleware('can:view,App\User,organization,user');
    //Route::delete('/v1/organization/{organization}/user/{user}', [App\Http\Controllers\API\UserController::class, 'destroy'])->name('api.v1.organization.user.destroy')->middleware('can:delete,user');

    // User Status
    Route::get('/v1/organization/{organization}/userstatus', [App\Http\Controllers\API\UserStatusController::class, 'index'])->middleware('can:viewAny,App\UserStatus,organization');
    Route::patch('/v1/organization/{organization}/user/{user}/status', [App\Http\Controllers\API\UserStatusController::class, 'update'])->middleware('can:update,App\UserStatus,organization,user');

    // Program Status
    Route::get('/v1/organization/{organization}/programstatus', [App\Http\Controllers\API\ProgramStatusController::class, 'index'])->middleware('can:listStatus,App\Program,organization');
    Route::patch('/v1/organization/{organization}/program/{program}/status', [App\Http\Controllers\API\ProgramStatusController::class, 'update'])->middleware('can:updateStatus,App\Program,organization,program');

    Route::get('/v1/organization', [App\Http\Controllers\API\OrganizationController::class, 'index'])->middleware('can:viewAny,App\Organization');
    Route::get('/v1/organization/{organization}', [App\Http\Controllers\API\OrganizationController::class, 'show'])->name('api.v1.organization.show')->middleware('can:view,organization');
    Route::post('/v1/organization', [App\Http\Controllers\API\OrganizationController::class, 'store'])->name('api.v1.organization.store')->middleware('can:create,App\Organization');
    Route::put('/v1/organization/{organization}', [App\Http\Controllers\API\OrganizationController::class, 'update'])->name('api.v1.organization.update')->middleware('can:update,organization');
    //Route::delete('/v1/organization/{organization}', [App\Http\Controllers\API\OrganizationController::class, 'destroy'])->name('api.v1.organization.destroy')->middleware('can:delete,organization');

    //ROLES & PERMISSIONS
    // Route::get('/v1/organization/{organization}/user/{user}/role', [App\Http\Controllers\API\RoleController::class, 'userRoleIndex'])->name('api.v1.organization.user.roles')->middleware('can:view,App\Role,user');
    // Route::put('/v1/organization/{organization}/user/{user}/role/{role}', [App\Http\Controllers\API\RoleController::class, 'assign'])->name('api.v1.organization.user.role.assign')->middleware('can:update,role');
    // Route::delete('/v1/organization/{organization}/user/{user}/role/{role}', [App\Http\Controllers\API\RoleController::class, 'revoke'])->name('api.v1.organization.user.role.revoke')->middleware('can:update,role');

    Route::get('/v1/organization/{organization}/role', [App\Http\Controllers\API\RoleController::class, 'index'])->name('api.v1.organization.role.index')->middleware('can:viewAny,App\Role,organization');
    Route::get('/v1/organization/{organization}/role/{role}', [App\Http\Controllers\API\RoleController::class, 'show'])->name('api.v1.organization.role.show')->middleware('can:view,role');
    Route::post('/v1/organization/{organization}/role', [App\Http\Controllers\API\RoleController::class, 'store'])->name('api.v1.organization.role.store')->middleware('can:create,App\Role');
    Route::put('/v1/organization/{organization}/role/{role}', [App\Http\Controllers\API\RoleController::class, 'update'])->name('api.v1.organization.role.update')->middleware('can:update,role');
    Route::delete('/v1/organization/{organization}/role/{role}', [App\Http\Controllers\API\RoleController::class, 'destroy'])->name('api.v1.organization.role.destroy')->middleware('can:delete,role');

    //Permission routes

    Route::get('/v1/organization/{organization}/permission', [App\Http\Controllers\API\PermissionController::class, 'index'])->name('api.v1.organization.permission.index')->middleware('can:viewAny,App\Permission');
    Route::get('/v1/organization/{organization}/permission/{permission}', [App\Http\Controllers\API\PermissionController::class, 'show'])->name('api.v1.organization.permission.view')->middleware('can:view,permission');
    Route::post('/v1/organization/{organization}/permission', [App\Http\Controllers\API\PermissionController::class, 'store'])->name('api.v1.organization.permission.store')->middleware('can:create');
    Route::put('/v1/organization/{organization}/permission/{permission}', [App\Http\Controllers\API\PermissionController::class, 'update'])->name('api.v1.organization.permission.update')->middleware('can:update,permission');
    Route::delete('/v1/organization/{organization}/permission/{permission}', [App\Http\Controllers\API\PermissionController::class, 'destroy'])->name('api.v1.organization.permission.delete')->middleware('can:delete,permission');

    //Domain Routes
    Route::get('/v1/organization/{organization}/domain',
    [App\Http\Controllers\API\DomainController::class, 'index'])->name('api.v1.domain.index')->middleware('can:viewAny,App\Domain,organization');
    Route::post('/v1/organization/{organization}/domain',
    [App\Http\Controllers\API\DomainController::class, 'store'])->name('api.v1.domain.store')->middleware('can:create,App\Domain,organization');
    Route::get('/v1/organization/{organization}/domain/{domain}',
    [App\Http\Controllers\API\DomainController::class, 'show'])->name('api.v1.domain.show')->middleware('can:view,App\Domain,organization,domain');
    Route::put('/v1/organization/{organization}/domain/{domain}',
    [App\Http\Controllers\API\DomainController::class, 'update'])->name('api.v1.domain.update')->middleware('can:update,App\Domain,organization,domain');
    Route::delete('/v1/organization/{organization}/domain/{domain}',
    [App\Http\Controllers\API\DomainController::class, 'delete'])->name('api.v1.domain.delete')->middleware('can:delete,App\Domain,organization,domain');
    Route::get('/v1/organization/{organization}/domain/{domain}/generateSecretKey',
    [App\Http\Controllers\API\DomainController::class, 'generateSecretKey'])->name('api.v1.domain.generateSecretKey')->middleware('can:generateSecretKey,App\Domain,organization,domain');
    Route::post('/v1/organization/{organization}/domain/{domain}/addip',
    [App\Http\Controllers\API\DomainIPController::class, 'store'])->name('api.v1.domain_ip.store')->middleware('can:addIp,App\Domain,organization,domain');
    Route::delete('/v1/organization/{organization}/domain/{domain}/domain_ip/{domain_ip}',
    [App\Http\Controllers\API\DomainIPController::class, 'delete'])->name('api.v1.domain_ip.delete')->middleware('can:deleteIp,App\Domain,organization,domain');
    Route::get('/v1/organization/{organization}/domain/{domain}/check-status',
        [App\Http\Controllers\API\DomainController::class, 'checkStatus'])->name('api.v1.domain.generateSecretKey')->middleware('can:checkStatus,App\Domain,organization,domain');

    //DomainProgram routes

    Route::get('/v1/organization/{organization}/domain/{domain}/program',[App\Http\Controllers\API\DomainProgramController::class, 'index'])->name('api.v1.domainProgram.index')->middleware('can:viewAny,App\DomainProgram,organization,domain');
    Route::get('/v1/organization/{organization}/domain/{domain}/listAvailableProgramsToAdd',[App\Http\Controllers\API\DomainProgramController::class, 'listAvailableProgramsToAdd'])->name('api.v1.domainProgram.listAvailableProgramsToAdd')->middleware('can:listAvailableProgramsToAdd,App\DomainProgram,organization,domain');
    Route::post('/v1/organization/{organization}/domain/{domain}/program',[App\Http\Controllers\API\DomainProgramController::class, 'store'])->name('api.v1.domainProgram.add')->middleware('can:create,App\DomainProgram,organization,domain');
    Route::delete('/v1/organization/{organization}/domain/{domain}/program/{program}',
    [App\Http\Controllers\API\DomainProgramController::class, 'delete'])->name('api.v1.domain.domainProgram')->middleware('can:delete,App\DomainProgram,organization,domain,program');

    //Merchant Routes
    Route::post('/v1/merchant', [App\Http\Controllers\API\MerchantController::class, 'store'])->middleware('can:create,App\Merchant');
    Route::get('/v1/organization/{organization}/merchant', [App\Http\Controllers\API\MerchantController::class, 'index'])->middleware('can:viewAny,App\Merchant,organization');
    Route::get('/v1/merchant/{merchant}', [App\Http\Controllers\API\MerchantController::class, 'show'])->middleware('can:view,merchant');
    Route::put('/v1/merchant/{merchant}', [App\Http\Controllers\API\MerchantController::class, 'update'])->middleware('can:udpate,merchant');
    Route::delete('/v1/merchant/{merchant}', [App\Http\Controllers\API\MerchantController::class, 'delete'])->middleware('can:delete,merchant');
    Route::patch('/v1/merchant/{merchant}/status', [App\Http\Controllers\API\MerchantController::class, 'changeStatus'])->middleware('can:update,merchant');

    Route::put('/v1/merchant/{merchant}/toa/{toa}', [App\Http\Controllers\API\MerchantController::class, 'updateToa'])->middleware('can:udpate,merchant');

    //Submerchant Routes
    Route::get('/v1/merchant/{merchant}/not-in-hierarchy', [App\Http\Controllers\API\SubmerchantController::class, 'notInHierarchy'])->middleware('can:view,merchant');
    Route::get('/v1/merchant/{merchant}/in-hierarchy', [App\Http\Controllers\API\SubmerchantController::class, 'inHierarchy'])->middleware('can:view,merchant');
    Route::get('/v1/merchant/{merchant}/submerchant', [App\Http\Controllers\API\SubmerchantController::class, 'index'])->middleware('can:viewAny,App\Submerchant,merchant');
    Route::post('/v1/merchant/{merchant}/submerchant', [App\Http\Controllers\API\SubmerchantController::class, 'store'])->middleware('can:add,App\Submerchant,merchant');
    Route::delete('/v1/merchant/{merchant}/submerchant/{submerchant}', [App\Http\Controllers\API\SubmerchantController::class, 'delete'])->middleware('can:remove,App\Submerchant,merchant');

    // Program routes
    Route::get('/v1/organization/{organization}/program/{program}/hierarchy', [App\Http\Controllers\API\ProgramController::class, 'hierarchyByProgram'])->middleware('can:viewAny,App\Program,organization');
    Route::get('/v1/organization/{organization}/program', [App\Http\Controllers\API\ProgramController::class, 'index'])->middleware('can:viewAny,App\Program,organization');
    Route::get('/v1/programs-all', [App\Http\Controllers\API\ProgramController::class, 'all'])->middleware('can:viewAny,App\Program,organization');
    Route::get('/v1/organization/{organization}/program/{program}/getBalance', [App\Http\Controllers\API\ProgramController::class, 'getBalanceInformation'] )->middleware('can:viewAny,App\Program,organization');
    Route::get('/v1/organization/{organization}/program/hierarchy', [App\Http\Controllers\API\ProgramController::class, 'hierarchy'])->middleware('can:viewAny,App\Program,organization');
    Route::post('/v1/organization/{organization}/program', [App\Http\Controllers\API\ProgramController::class, 'store'])->middleware('can:create,App\Program,organization');
    Route::get('/v1/organization/{organization}/program/{program}', [App\Http\Controllers\API\ProgramController::class, 'show'])->middleware('can:view,App\Program,organization,program');
    Route::put('/v1/organization/{organization}/program/{program}', [App\Http\Controllers\API\ProgramController::class, 'update'])->middleware('can:update,App\Program,organization,program');
    Route::patch('/v1/organization/{organization}/program/{program}/move', [App\Http\Controllers\API\ProgramController::class, 'move'])->middleware('can:move,App\Program,organization,program');
    Route::patch('/v1/organization/{organization}/program/{program}/restore', [App\Http\Controllers\API\ProgramController::class, 'restore'])->middleware('can:restore,App\Program,organization,program')->withTrashed();
    Route::delete('/v1/organization/{organization}/program/{program}', [App\Http\Controllers\API\ProgramController::class, 'delete'])->middleware('can:delete,App\Program,organization,program');
    Route::get('/v1/organization/{organization}/program/{program}/prepare-live-mode', [App\Http\Controllers\API\ProgramController::class, 'prepareLiveMode'])->middleware('can:liveMode,App\Program,organization,program');
    Route::post('/v1/organization/{organization}/program/{program}/live-mode', [App\Http\Controllers\API\ProgramController::class, 'liveMode'])->middleware('can:liveMode,App\Program,organization,program');
    Route::put('/v1/organization/{organization}/program/{program}/save-selected-reports', [App\Http\Controllers\API\ProgramController::class, 'saveSelectedReports']);
    Route::get('/v1/reports', [App\Http\Controllers\API\ReportsController::class, 'getAllReports'])->middleware('auth:api');
    Route::get('/v1/reports/{program}', [App\Http\Controllers\API\ReportsController::class, 'getReportsByProgramId'])->middleware(['auth:api', 'reports.available']);
    Route::get('/v1/reports/{program}/selected', [App\Http\Controllers\API\ReportsController::class, 'getSelectedReportsByProgramId'])->middleware('auth:api');

    // Subprogram Routes
    Route::get('/v1/organization/{organization}/program/{program}/subprogram', [App\Http\Controllers\API\SubprogramController::class, 'index'])->middleware('can:viewAny,App\Subprogram,organization,program');
    Route::get('/v1/organization/{organization}/subprogram/{program}/available/{action}', [App\Http\Controllers\API\SubprogramController::class, 'available'])->middleware('can:viewAny,App\Subprogram,organization,program');
    Route::patch('/v1/organization/{organization}/subprogram/{program}/unlink', [App\Http\Controllers\API\SubprogramController::class, 'unlink'])->middleware('can:unlink,App\Subprogram,organization,program');
    Route::get('/v1/organization/{organization}/program/{program}/descendents', [App\Http\Controllers\API\SubprogramController::class, 'getDescendents'])->middleware('can:viewAny,App\Subprogram,organization,program');

    //ProgramMerchant routes

    // Route::get('/v1/organization/{organization}/program/{program}/merchant',[App\Http\Controllers\API\ProgramMerchantController::class, 'index'])->name('api.v1.program.merchant.index')->middleware('can:viewAny,App\ProgramMerchant,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/merchant/{merchant}',
    [App\Http\Controllers\API\ProgramMerchantController::class, 'view'])->name('api.v1.program.merchant.view')->middleware('can:view,App\ProgramMerchant,organization,program,merchant');

    Route::get('/v1/organization/{organization}/program/{program}/merchant/{merchant}/giftcode',
    [App\Http\Controllers\API\ProgramMerchantController::class, 'giftcodes'])->name('api.v1.program.merchant.giftcodes')->middleware('can:viewGiftcodes,App\ProgramMerchant,organization,program,merchant');

    Route::post('/v1/organization/{organization}/program/{program}/merchant',
    [App\Http\Controllers\API\ProgramMerchantController::class, 'store'])->name('api.v1.program.merchant.add')->middleware('can:add,App\ProgramMerchant,organization,program');

    Route::delete('/v1/organization/{organization}/program/{program}/merchant/{merchant}',
    [App\Http\Controllers\API\ProgramMerchantController::class, 'delete'])->name('api.v1.program.merchant.delete')->middleware('can:remove,App\ProgramMerchant,organization,program,merchant');

    Route::get('/v1/organization/{organization}/program/{program}/merchant/{merchant}/redeemable', [App\Http\Controllers\API\ProgramMerchantController::class, 'redeemable'])->middleware('can:viewRedeemable,App\ProgramMerchant,organization,program,merchant');

    //ProgramUser routes
    Route::get('/v1/organization/{organization}/program/{program}/digital-media-type',
        [App\Http\Controllers\API\ProgramMediaTypeController::class, 'index'])->middleware('can:viewAny,App\ProgramMediaType,organization,program,user');

    Route::post(
        '/v1/organization/{organization}/program/{program}/digital-media-type',
        [App\Http\Controllers\API\ProgramMediaTypeController::class, 'store']
    )->middleware('can:add,App\ProgramMediaType,organization,program');

    Route::post(
        '/v1/organization/{organization}/program/{program}/digital-media-type-iframe',
        [App\Http\Controllers\API\ProgramMediaTypeController::class, 'updateLink']
    )->middleware('can:add,App\ProgramMediaType,organization,program');

    // Route::post(
    //     '/v1/organization/{organization}/program/{program}/digital-media-type-url-delete',
    //     [App\Http\Controllers\API\ProgramMediaTypeController::class, 'delete']
    // )->middleware('can:add,App\ProgramMediaType,organization,program');

    // Route::put(
    //     '/v1/organization/{organization}/program/{program}/digital-media-type',
    //     [App\Http\Controllers\API\ProgramMediaTypeController::class, 'saveLink']
    // )->middleware('can:add,App\ProgramMediaType,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/media/{programMediaType}',
        [App\Http\Controllers\API\ProgramMediaController::class, 'index'])->middleware('can:view,App\ProgramMedia,organization,program');


    Route::delete('/v1/organization/{organization}/program/{program}/digital-media-type/{programMediaType}',
    [App\Http\Controllers\API\ProgramMediaTypeController::class, 'delete'])->middleware('can:remove,App\ProgramMediaType,organization,program');
//
//    Route::get('/v1/organization/{organization}/program/{program}/merchant/{merchant}',
//        [App\Http\Controllers\API\ProgramMerchantController::class, 'view'])->name('api.v1.program.merchant.view')->middleware('can:view,App\ProgramMerchant,organization,program,merchant');
//
//    Route::get('/v1/organization/{organization}/program/{program}/merchant/{merchant}/giftcode',
//        [App\Http\Controllers\API\ProgramMerchantController::class, 'giftcodes'])->name('api.v1.program.merchant.giftcodes')->middleware('can:viewGiftcodes,App\ProgramMerchant,organization,program,merchant');
    Route::post('/v1/organization/{organization}/program/{program}/digital-media',
        [App\Http\Controllers\API\ProgramMediaController::class, 'store'])->middleware('can:add,App\ProgramMedia,organization,program');

    Route::delete('/v1/organization/{organization}/program/{program}/programMedia/{programMedia}/digital-media',
    [App\Http\Controllers\API\ProgramMediaController::class, 'delete'])->middleware('can:remove,App\ProgramMedia,organization,program');


    Route::post('/v1/organization/{organization}/program/{program}/digital-media/upload',
        [App\Http\Controllers\API\ProgramMediaController::class, 'upload'])->middleware('can:add,App\ProgramMedia,organization,program');


    Route::get('/v1/organization/{organization}/program/{program}/user', [App\Http\Controllers\API\ProgramUserController::class, 'index'])->middleware('can:viewAny,App\ProgramUser,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}',[App\Http\Controllers\API\ProgramUserController::class, 'show'])->middleware('can:view,App\ProgramUser,organization,program,user');

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/history',[App\Http\Controllers\API\ProgramUserController::class, 'history'])->middleware('can:view,App\ProgramUser,organization,program,user');
    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/gift-codes-redeemed',[App\Http\Controllers\API\ProgramUserController::class, 'giftCodesRedeemed'])->middleware('can:view,App\ProgramUser,organization,program,user');
    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/change-logs',[App\Http\Controllers\API\ProgramUserController::class, 'changeLogs'])->middleware('can:view,App\ProgramUser,organization,program,user');

    Route::post('/v1/organization/{organization}/program/{program}/user',[App\Http\Controllers\API\ProgramUserController::class, 'store'])->middleware('can:add,App\ProgramUser,organization,program');

    Route::put('/v1/organization/{organization}/program/{program}/user/{user}',[App\Http\Controllers\API\ProgramUserController::class, 'update']);

    Route::delete('/v1/organization/{organization}/program/{program}/user/{user}',
    [App\Http\Controllers\API\ProgramUserController::class, 'delete'])->middleware('can:remove,App\ProgramUser,organization,program,user');

    Route::get('/v1/organization/{organization}/program/{program}/userToAssign', [App\Http\Controllers\API\ProgramUserController::class, 'userToAssign'])->middleware('can:viewAny,App\ProgramUser,organization,program');

    Route::patch('/v1/organization/{organization}/program/{program}/user/{user}/assignRole', [App\Http\Controllers\API\ProgramUserController::class, 'assignRole'])->middleware('can:assignRole,App\ProgramUser,organization,program,user');

    Route::post('/v1/program-user', [App\Http\Controllers\API\ProgramUserController::class, 'storeRaw'])->middleware('can:manage,App\ProgramUser');
    Route::put('/v1/program-user', [App\Http\Controllers\API\ProgramUserController::class, 'updateRaw'])->middleware('can:manage,App\ProgramUser');
    Route::patch('/v1/program-user/status', [App\Http\Controllers\API\ProgramUserController::class, 'changeStatusRaw'])->middleware('can:manage,App\ProgramUser');
    Route::post('/v1/program', [App\Http\Controllers\API\ProgramController::class, 'storeRaw'])->middleware('can:manage,App\ProgramUser');
    Route::post('/v1/award',[App\Http\Controllers\API\AwardController::class, 'storeRaw'])->middleware('can:manage,App\ProgramUser');

    //UserProgram routes

    Route::get('/v1/organization/{organization}/user/{user}/program', [App\Http\Controllers\API\UserProgramController::class, 'index'])->middleware('can:viewAny,App\UserProgram,organization,user');

    Route::post('/v1/organization/{organization}/user/{user}/program',[App\Http\Controllers\API\UserProgramController::class, 'store'])->middleware('can:add,App\UserProgram,organization,user');

    Route::delete('/v1/organization/{organization}/user/{user}/program/{program}',
    [App\Http\Controllers\API\UserProgramController::class, 'delete'])->middleware('can:remove,App\UserProgram,organization,user,program');

    Route::get('/v1/organization/{organization}/user/{user}/program/{program}/role',
    [App\Http\Controllers\API\UserProgramController::class, 'getRole'])->middleware('can:getRoles,App\UserProgram,organization,user,program');

    //Reports routes
    Route::post('/v1/organization/{organization}/report',[App\Http\Controllers\API\ReportController::class, 'index'])->middleware('can:viewAny,App\Report');
    //Route::get('/v1/organization/{organization}/reports/{type}',[App\Http\Controllers\API\ReportController::class, 'index'])->middleware('can:viewAny,App\Report');
    Route::get('/v1/organization/{organization}/report/{title}',[App\Http\Controllers\API\ReportController::class, 'show'])->middleware('can:viewAny,App\Report');
    Route::get('/v1/program/{program}/report/{title}',[App\Http\Controllers\API\ReportController::class, 'show'])->middleware('can:viewAny,App\Report');

    Route::get('/v1/organization/{organization}/report/order/{order}',[App\Http\Controllers\API\ReportOrderController::class, 'show'])->middleware('can:viewAny,App\Report');

    //MerchantGiftcodes

    Route::get('/v1/merchant/{merchant}/giftcode', [App\Http\Controllers\API\MerchantGiftcodeController::class, 'index'])->middleware('can:viewAny,App\MerchantGiftcode,merchant');

    Route::post('/v1/merchant/{merchant}/giftcode', [App\Http\Controllers\API\MerchantGiftcodeController::class, 'store'])->middleware('can:add,App\MerchantGiftcode,merchant');
    Route::post('/v1/merchant/{merchant}/giftcode-virtual', [App\Http\Controllers\API\MerchantGiftcodeController::class, 'storeVirtual'])->middleware('can:add,App\MerchantGiftcode,merchant');

    // GiftCode
    Route::post('/v1/giftcode/purchase-from-v2', [App\Http\Controllers\API\GiftcodeController::class, 'purchaseFromV2'])->middleware('can:purchaseFromV2,App\Giftcode');
    Route::post('/v1/giftcode/purchase-codes', [App\Http\Controllers\API\GiftcodeController::class, 'purchaseCodes'])->middleware('can:viewAny,App\Giftcode');

    // Cron Jobs
    Route::get('/v1/cron-jobs/read-list', [App\Http\Controllers\API\CronJobsController::class, 'readList'])->middleware('can:viewAny');
    Route::get('/v1/cron-jobs/run/{key}', [App\Http\Controllers\API\CronJobsController::class, 'run'])->middleware('can:viewAny');

    //MerchantOptimalValues

    Route::get('/v1/merchant/{merchant}/optimalvalue', [App\Http\Controllers\API\MerchantOptimalValueController::class, 'index'])->middleware('can:viewAny,App\MerchantOptimalValue,merchant');

    Route::post('/v1/merchant/{merchant}/optimalvalue', [App\Http\Controllers\API\MerchantOptimalValueController::class, 'store'])->middleware('can:add,App\MerchantOptimalValue,merchant');

    Route::put('/v1/merchant/{merchant}/optimalvalue/{optimalValue}',[App\Http\Controllers\API\MerchantOptimalValueController::class, 'update'])->middleware('can:update,App\MerchantOptimalValue,merchant,optimalValue');

    Route::delete('/v1/merchant/{merchant}/optimalvalue/{optimalValue}',[App\Http\Controllers\API\MerchantOptimalValueController::class, 'destroy'])->middleware('can:delete,App\MerchantOptimalValue,merchant,optimalValue');

    // Tango API
    Route::get('/v1/tango-api/index',[App\Http\Controllers\API\TangoApiController::class, 'index'])->middleware('can:viewAny,App\TangoApi,organization,program');

    //ProgramLogin

    Route::post('/v1/organization/{organization}/program/{program}/login',[App\Http\Controllers\API\ProgramLoginController::class, 'login'])->middleware('can:login,App\ProgramLogin,organization,program');

    //EventType

    Route::get('/v1/organization/{organization}/program/{program}/eventtype',[App\Http\Controllers\API\EventTypeController::class, 'index'])->middleware('can:viewAny,App\EventType,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/eventtype-milestone-frequency',[App\Http\Controllers\API\EventTypeController::class, 'milestoneFrequency'])->middleware('can:viewAny,App\EventType,organization,program');

    //EmailTemplate

    //Route::get('/v1/emailtemplate',[App\Http\Controllers\API\EmailTemplateController::class, 'index'])->middleware('can:viewAny,App\EmailTemplate');

    Route::get('/v1/organization/{organization}/program/{program}/emailtemplate',[App\Http\Controllers\API\EmailTemplateController::class, 'index'])->middleware('can:viewAny,App\EmailTemplate,organization,program');

    Route::post('/v1/organization/{organization}/program/{program}/emailtemplate/{emailTemplate}',[App\Http\Controllers\API\EmailTemplateController::class, 'update'])->middleware('can:update,App\EmailTemplate,organization,program,emailTemplate');

    Route::get('/v1/organization/{organization}/program/{program}/emailtemplate/typeList',[App\Http\Controllers\API\EmailTemplateController::class, 'typeList'])->middleware('can:listType,App\EmailTemplate,organization,program');

    //Award

    Route::post('/v1/organization/{organization}/program/{program}/award',[App\Http\Controllers\API\AwardController::class, 'store'])->middleware('can:create,App\Award,organization,program');

    //ProgramParticipants

    Route::get('/v1/organization/{organization}/program/{program}/participant',[App\Http\Controllers\API\ProgramParticipantController::class, 'index'])->middleware('can:viewAny,App\ProgramParticipant,organization,program');

    Route::patch('/v1/organization/{organization}/program/{program}/participant/status',[App\Http\Controllers\API\ProgramParticipantController::class, 'changeStatus'])->middleware('can:changeStatus,App\ProgramParticipant,organization,program');

    //Get User Point Balance

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/balance',[App\Http\Controllers\API\ProgramUserController::class, 'readBalance'])->middleware('can:readBalance,App\ProgramUser,organization,program,user');

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/event-history',[App\Http\Controllers\API\ProgramUserController::class, 'readEventHistory'])->middleware('can:readEventHistory,App\ProgramUser,organization,program,user');

    // Reclaim

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/reclaim-peer-points',[App\Http\Controllers\API\AwardController::class, 'readListReclaimablePeerPoints'])->middleware('can:readListReclaimablePeerPoints,App\Award,organization,program,user');

    Route::get('/v1/organization/{organization}/program/{program}/program-award-levels',[App\Http\Controllers\API\AwardController::class, 'programAwardLevels'])->name('programAwardLevels');
    Route::post('/v1/organization/{organization}/program/{program}/create-award-level',[App\Http\Controllers\API\AwardController::class, 'createAwardLevel'])->name('createAwardLevel');
    Route::post('/v1/organization/{organization}/program/{program}/award-level-participants',[App\Http\Controllers\API\AwardController::class, 'awardLevelParticipants'])->name('awardLevelParticipants');

    Route::post('/v1/organization/{organization}/program/{program}/user/{user}/reclaim-peer-points',[App\Http\Controllers\API\AwardController::class, 'reclaimPeerPoints'])->middleware('can:reclaimPeerPoints,App\Award,organization,program,user');

    // Participant
    // Participant

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/mypoints',[App\Http\Controllers\API\ParticipantController::class, 'myPoints'])->middleware('can:readPoints,App\Participant,organization,program,user');

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/point-history',[App\Http\Controllers\API\ParticipantController::class, 'pointHistory'])->middleware('can:readPoints,App\Participant,organization,program,user');

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/unread-notifications-count',[App\Http\Controllers\API\ParticipantController::class, 'unreadNotificationCount'])->middleware('can:readPoints,App\Participant,organization,program,user');

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/mark-notifications-read',[App\Http\Controllers\API\ParticipantController::class, 'markNotificationsRead'])->middleware('can:markNotificationRead,App\Participant,organization,program,user');

    // Giftcodes
    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/gift-codes',[App\Http\Controllers\API\ProgramParticipantGiftCodeController::class, 'index'])->middleware('can:viewAny,App\ProgramParticipantGiftCode,organization,program,user');

    //Statuses

    // Route::get('/v1/status',[App\Http\Controllers\API\StatusController::class, 'index'])->middleware('can:viewAny,App\Status');

    //Checkout

    Route::post('/v1/organization/{organization}/program/{program}/checkout',[App\Http\Controllers\API\CheckoutController::class, 'store'])->middleware('can:checkout,App\Checkout,organization,program');

    // ProgramTemplate

    Route::get('/v1/organization/{organization}/program/{program}/template',[App\Http\Controllers\API\ProgramTemplateController::class, 'getTemplate'])->middleware('can:getTemplate,App\ProgramTemplate,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/template/{programTemplate}',[App\Http\Controllers\API\ProgramTemplateController::class, 'show'])->middleware('can:view,App\ProgramTemplate,organization,program,programTemplate')->whereNumber('programTemplate');

    Route::get('/v1/organization/{organization}/program/{program}/template/{name}',[App\Http\Controllers\API\ProgramTemplateController::class, 'showByName'])->middleware('can:view,App\ProgramTemplate,organization,program,programTemplate')->whereAlphaNumeric('name');

    Route::post('/v1/organization/{organization}/program/{program}/template',[App\Http\Controllers\API\ProgramTemplateController::class, 'store'])->middleware('can:create,App\ProgramTemplate,organization,program');

    Route::put('/v1/organization/{organization}/program/{program}/template/{programTemplate}',[App\Http\Controllers\API\ProgramTemplateController::class, 'update'])->middleware('can:update,App\ProgramTemplate,organization,program,programTemplate');

    Route::delete('/v1/organization/{organization}/program/{program}/template/{programTemplate}',[App\Http\Controllers\API\ProgramTemplateController::class, 'delete'])->middleware('can:delete,App\ProgramTemplate,organization,program,programTemplate');

    Route::delete('/v1/organization/{organization}/program/{program}/template/{programTemplate}/media/{mediaName}',[App\Http\Controllers\API\ProgramTemplateController::class, 'deleteMedia'])->middleware('can:update,App\ProgramTemplate,organization,program,programTemplate');

    Route::put('/v1/organization/{organization}/program/{program}/invite', [App\Http\Controllers\API\InvitationController::class, 'invite'])->middleware('can:invite,App\Invitation,organization,program');
    Route::post('/v1/organization/{organization}/program/{program}/inviteResend', [App\Http\Controllers\API\InvitationController::class, 'resend'])->middleware('can:resend,App\Invitation,organization,program');

    // Leaderboard

    Route::get('/v1/organization/{organization}/program/{program}/leaderboard',[App\Http\Controllers\API\LeaderboardController::class, 'index'])->middleware('can:viewAny,App\Leaderboard,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}',[App\Http\Controllers\API\LeaderboardController::class, 'show'])->middleware('can:view,App\Leaderboard,organization,program,leaderboard');

    Route::post('/v1/organization/{organization}/program/{program}/leaderboard',[App\Http\Controllers\API\LeaderboardController::class, 'store'])->middleware('can:create,App\Leaderboard,organization,program');

    Route::put('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}',[App\Http\Controllers\API\LeaderboardController::class, 'update'])->middleware('can:update,App\Leaderboard,organization,program,leaderboard');

    Route::delete('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}',[App\Http\Controllers\API\LeaderboardController::class, 'delete'])->middleware('can:delete,App\Leaderboard,organization,program,leaderboard');

    // Leaderboard Leaders
    Route::get('/v1/organization/{organization}/program/{program}/leaderboard-leaders',[App\Http\Controllers\API\LeaderboardLeadersController::class, 'index'])->middleware('can:viewAny,App\LeaderboardLeaders,organization,program');
    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/leaderboard/{leaderboard}/event-leaders-awards',[App\Http\Controllers\API\LeaderboardLeadersController::class, 'readEventLeadersAwardsByUser'])->middleware('can:viewAny,App\LeaderboardLeaders,organization,program');

    // LeaderboardType

    Route::get('/v1/organization/{organization}/program/{program}/leaderboardType',[App\Http\Controllers\API\LeaderboardTypeController::class, 'index'])->middleware('can:viewAny,App\LeaderboardType,organization,program');

    // LeaderboardEvent

    Route::get('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}/event',[App\Http\Controllers\API\LeaderboardEventController::class, 'index'])->middleware('can:viewAny,App\LeaderboardEvent,organization,program,leaderboard');
    Route::get('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}/goal-plan',[App\Http\Controllers\API\LeaderboardGoalPlanController::class, 'index'])->middleware('can:viewAny,App\LeaderboardGoalPlan,organization,program,leaderboard');

    Route::get('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}/assignableEvent',[App\Http\Controllers\API\LeaderboardEventController::class, 'assignable'])->middleware('can:viewAny,App\LeaderboardEvent,organization,program,leaderboard');
    Route::get('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}/assignableGoalPlan',[App\Http\Controllers\API\LeaderboardGoalPlanController::class, 'assignable'])->middleware('can:viewAny,App\LeaderboardGoalPlan,organization,program,leaderboard');

    Route::patch('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}/event',[App\Http\Controllers\API\LeaderboardEventController::class, 'assign'])->middleware('can:assign,App\LeaderboardEvent,organization,program,leaderboard');
    Route::patch('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}/goal-plan',[App\Http\Controllers\API\LeaderboardGoalPlanController::class, 'assign'])->middleware('can:assign,App\LeaderboardGoalPlan,organization,program,leaderboard');

    Route::get('/v1/goalplantype',[App\Http\Controllers\API\GoalPlanTypeController::class, 'index'])->middleware('can:viewAny,App\GoalPlanType');

    // Goal plans

   Route::post('/v1/organization/{organization}/program/{program}/goalplan', [App\Http\Controllers\API\GoalPlanController::class, 'store'])->middleware('can:create,App\GoalPlan,organization,program');
    //->name('api.v1.organization.program.goalplan.store')
   Route::get('/v1/organization/{organization}/program/{program}/goalplan', [App\Http\Controllers\API\GoalPlanController::class, 'index'])->name('api.v1.organization.program.goalplan.index')->middleware('can:viewAny,App\GoalPlan,organization,program');

   Route::get('/v1/organization/{organization}/program/{program}/goalplan/{goalPlan}', [App\Http\Controllers\API\GoalPlanController::class, 'show'])->name('api.v1.organization.program.goalplan.show')->middleware('can:view,App\GoalPlan,organization,program,goalPlan');

   Route::put('/v1/organization/{organization}/program/{program}/goalplan/{goalPlan}', [App\Http\Controllers\API\GoalPlanController::class, 'update'])->name('api.v1.organization.program.goalplan.update')->middleware('can:update,App\GoalPlan,organization,program,goalPlan');

   Route::delete('/v1/organization/{organization}/program/{program}/goalplan/{goalPlan}', [App\Http\Controllers\API\GoalPlanController::class, 'destroy'])->middleware('can:delete,App\GoalPlan,organization,program,goalPlan');

    // Program Email templates

    Route::get('/v1/organization/{organization}/program/{program}/programemailtemplate', [App\Http\Controllers\API\ProgramEmailTemplateController::class, 'index'])->name('api.v1.organization.program.programemailtemplate.viewAny')->middleware('can:viewAny,App\ProgramEmailTemplate,organization,program');

    // Expiration rules

    Route::get('/v1/expirationrule',[App\Http\Controllers\API\ExpirationRuleController::class, 'index'])->middleware('can:viewAny,App\ExpirationRule');

    // Invoice
    Route::get('/v1/organization/{organization}/program/{program}/invoice',[App\Http\Controllers\API\InvoiceController::class, 'index'])->middleware('can:viewAny,App\Invoice,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/invoice/{invoice}',[App\Http\Controllers\API\InvoiceController::class, 'show'])->middleware('can:view,App\Invoice,organization,program,invoice');

    Route::get('/v1/organization/{organization}/program/{program}/invoice/{invoice}/download',[App\Http\Controllers\API\InvoiceController::class, 'download'])->middleware('can:download,App\Invoice,organization,program,invoice');

    Route::post('/v1/organization/{organization}/program/{program}/invoice/on-demand',[App\Http\Controllers\API\InvoiceController::class, 'createOnDemand'])->middleware('can:createOnDemand,App\Invoice,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/invoice/{invoice}/pay',[App\Http\Controllers\API\InvoiceController::class, 'payView'])->middleware('can:pay,App\Invoice,organization,program,invoice');

    Route::post('/v1/organization/{organization}/program/{program}/invoice/{invoice}/pay',[App\Http\Controllers\API\InvoiceController::class, 'paySubmit'])->middleware('can:pay,App\Invoice,organization,program,invoice');

    Route::get('/v1/organization/{organization}/program/{program}/payments',[App\Http\Controllers\API\ProgramController::class, 'getPayments'])->middleware('can:listPayments,App\Program,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/balance',[App\Http\Controllers\API\ProgramController::class, 'getBalance'])->middleware('can:viewBalance,App\Program,organization,program');

    Route::post('/v1/organization/{organization}/program/{program}/payments',[App\Http\Controllers\API\ProgramController::class, 'submitPayments'])->middleware('can:updatePayments,App\Program,organization,program');

    Route::post('/v1/organization/{organization}/program/{program}/invoice/{invoice}/reversepayment',[App\Http\Controllers\API\ProgramController::class, 'reversePayment'])->middleware('can:reversePayments,App\Program,organization,program,invoice');

    Route::get('/v1/organization/{organization}/program/{program}/ledgercode',[App\Http\Controllers\API\EventLedgerCodeController::class, 'index'])->middleware('can:listLedgerCodes,App\Program,organization,program');

    Route::post('/v1/organization/{organization}/program/{program}/ledgercode',[App\Http\Controllers\API\EventLedgerCodeController::class, 'store'])->middleware('can:createLedgerCodes,App\Program,organization,program');

    Route::put('/v1/organization/{organization}/program/{program}/ledgercode/{eventLedgerCode}',[App\Http\Controllers\API\EventLedgerCodeController::class, 'update'])->middleware('can:updateLedgerCodes,App\Program,organization,program');

    Route::delete('/v1/organization/{organization}/program/{program}/ledgercode/{eventLedgerCode}',[App\Http\Controllers\API\EventLedgerCodeController::class, 'delete'])->middleware('can:deleteLedgerCodes,App\Program,organization,program');
    // Deposit

    Route::post('/v1/organization/{organization}/program/{program}/creditcardDeposit',[App\Http\Controllers\API\ProgramController::class, 'deposit'])->middleware('can:updatePayments,App\Program,organization,program');

    // Statements

    Route::get('/v1/organization/{organization}/program/{program}/statement',[App\Http\Controllers\API\StatementController::class, 'show'])->middleware('can:view,App\Statement,organization,program');

    // Program > TransferMonies

    Route::get('/v1/organization/{organization}/program/{program}/transferMonies',[App\Http\Controllers\API\ProgramController::class, 'getTransferMonies'])->middleware('can:transferMonies,App\Program,organization,program');

    Route::post('/v1/organization/{organization}/program/{program}/transferMonies',[App\Http\Controllers\API\ProgramController::class, 'submitTransferMonies'])->middleware('can:transferMonies,App\Program,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/transferMonies/template',[App\Http\Controllers\API\ProgramController::class, 'downloadMoneyTranferTemplate'])->middleware('can:transferMonies,App\Program,organization,program');

    Route::post('/v1/organization/{organization}/program/{program}/transferMonies/template',[App\Http\Controllers\API\ProgramController::class, 'transferMoniesByTemplate'])->middleware('can:transferMonies,App\Program,organization,program');

    Route::post('/v1/organization/{organization}/program/{program}/payment',[App\Http\Controllers\API\ProgramController::class, 'submitTransferMonies'])->middleware('can:transferMonies,App\Program,organization,program');

    // Country
    Route::get('/v1/country/{country}/state',[App\Http\Controllers\API\CountryController::class, 'listStates']);

    //Team Routes
    /*Route::post('/v1/organization/{organization}/program/{program}/team',
    [App\Http\Controllers\API\TeamController::class, 'store'])->middleware('can:create,App\Team,organization,program');*/
    Route::post('/v1/organization/{organization}/program/{program}/team', [App\Http\Controllers\API\TeamController::class, 'store'])->middleware('can:create,App\Team,organization,program');

     Route::get('/v1/organization/{organization}/program/{program}/team',
     [App\Http\Controllers\API\TeamController::class, 'index'])->name('api.v1.team.index')->middleware('can:viewAny,App\Team,organization,program');

     Route::get('/v1/organization/{organization}/program/{program}/team/{team}',
     [App\Http\Controllers\API\TeamController::class, 'show'])->name('api.v1.team.show')->middleware('can:view,App\Team,organization,program,team');
     Route::put('/v1/organization/{organization}/program/{program}/team/{team}',
     [App\Http\Controllers\API\TeamController::class, 'update'])->name('api.v1.team.update')->middleware('can:update,App\Team,organization,program,team');
     Route::delete('/v1/organization/{organization}/program/{program}/team/{team}',
     [App\Http\Controllers\API\TeamController::class, 'delete'])->name('api.v1.team.delete')->middleware('can:delete,App\Team,organization,program,team');

    //Goal plans
     Route::get('/v1/organization/{organization}/program/{program}/read-active-goalplans-by-program', [App\Http\Controllers\API\GoalPlanController::class, 'readActiveByProgram'])->name('api.v1.organization.program.goalplan.readActiveByProgram')->middleware('can:readActiveByProgram,App\GoalPlan,organization,program');
     //Referrals
     Route::post('/v1/organization/{organization}/program/{program}/referral-notification-recipient', [App\Http\Controllers\API\ReferralNotificationRecipientController::class, 'store'])->middleware('can:create,App\ReferralNotificationRecipient,organization,program');

     Route::get('/v1/organization/{organization}/program/{program}/referral-notification-recipient',
     [App\Http\Controllers\API\ReferralNotificationRecipientController::class, 'index'])->name('api.v1.referralNotificationRecipient.index')->middleware('can:viewAny,App\ReferralNotificationRecipient,organization,program');

     Route::get('/v1/organization/{organization}/program/{program}/referral-notification-recipient/{referralNotificationRecipient}',
     [App\Http\Controllers\API\ReferralNotificationRecipientController::class, 'show'])->name('api.v1.referralNotificationRecipient.show')->middleware('can:view,App\ReferralNotificationRecipient,organization,program,referralNotificationRecipient');

     Route::put('/v1/organization/{organization}/program/{program}/referral-notification-recipient/{referralNotificationRecipient}',
     [App\Http\Controllers\API\ReferralNotificationRecipientController::class, 'update'])->name('api.v1.referralNotificationRecipient.update')->middleware('can:update,App\ReferralNotificationRecipient,organization,program,referralNotificationRecipient');

     Route::delete('/v1/organization/{organization}/program/{program}/referral-notification-recipient/{referralNotificationRecipient}',
     [App\Http\Controllers\API\ReferralNotificationRecipientController::class, 'delete'])->name('api.v1.referralNotificationRecipient.delete')->middleware('can:delete,App\ReferralNotificationRecipient,organization,program,referralNotificationRecipient');

    // Referrals - Send

    Route::post('/v1/organization/{organization}/program/{program}/refer', [App\Http\Controllers\API\ReferralController::class, 'store'])->middleware('can:create,App\Referral,organization,program');
    
    // Feeling
    Route::post('/v1/organization/{organization}/program/{program}/feeling-survey', [App\Http\Controllers\API\FeelingSurveyController::class, 'store'])->middleware('can:create,App\FeelingSurvey,organization,program');

    //User goal
    Route::post('/v1/organization/{organization}/program/{program}/create-user-goals', [App\Http\Controllers\API\UserGoalController::class, 'createUserGoalPlans'])->middleware('can:createUserGoalPlans,App\UserGoal,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/readListByProgramAndUser',
     [App\Http\Controllers\API\UserGoalController::class, 'readListByProgramAndUser'])->name('api.v1.readListByProgramAndUser')->middleware('can:readListByProgramAndUser,App\UserGoal,organization,program,user');

     Route::get('/v1/organization/{organization}/program/{program}/user/{user}/readActiveByProgramAndUser',
     [App\Http\Controllers\API\UserGoalController::class, 'readActiveByProgramAndUser'])->name('api.v1.readActiveByProgramAndUser')->middleware('can:readListByProgramAndUser,App\UserGoal,organization,program,user');

     Route::get('/v1/organization/{organization}/program/{program}/usergoal/{userGoal}',
     [App\Http\Controllers\API\UserGoalController::class, 'show'])->name('api.v1.userGoal.show')->middleware('can:view,App\UserGoal,organization,program,userGoal');

     Route::get('/v1/organization/{organization}/program/{program}/readUserGoalProgressDetail/{userGoal}',
     [App\Http\Controllers\API\UserGoalController::class, 'readUserGoalProgressDetail'])->name('api.v1.readUserGoalProgressDetail')->middleware('can:readUserGoalProgressDetail,App\UserGoal,organization,program,userGoal');

    //External Callback

    Route::get('/v1/external-callback',[App\Http\Controllers\API\ExternalCallbackController::class, 'index'])->middleware('can:viewAny,App\ExternalCallback');

    Route::get('/v1/organization/{organization}/program/{program}/getGoalMetProgramCallbacks',
     [App\Http\Controllers\API\ExternalCallbackController::class, 'getGoalMetProgramCallbacks'])->name('api.v1.getGoalMetProgramCallbacks')->middleware('can:getGoalMetProgramCallbacks,App\ExternalCallback,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/getGoalExceededProgramCallbacks',
     [App\Http\Controllers\API\ExternalCallbackController::class, 'getGoalExceededProgramCallbacks'])->name('api.v1.getGoalExceededProgramCallbacks')->middleware('can:getGoalExceededProgramCallbacks,App\ExternalCallback,organization,program');

    Route::post('/v1/organization/{organization}/program/{program}/user/{user}/ReclaimPeerPoints',[App\Http\Controllers\API\ProgramUserController::class, 'ReclaimPeerPoints'])->middleware('can:reclaimPeerPoints,App\ProgramUser,organization,program,user');

    Route::group([
        'prefix' => '/v1/organization/{organization}/program/{program}',
    ], function ()
    {
        Route::group([
            'prefix' => '/social-wall-post',
        ], function ()
        {
            Route::get('', [SocialWallPostController::class, 'index']);
            Route::post('create', [SocialWallPostController::class,'store']);
            Route::post('uploadImage', [SocialWallPostController::class,'uploadImage']);
            Route::post('like',[SocialWallPostController::class, 'like']);
            Route::post('mentions',[SocialWallPostController::class, 'mentions']);
            Route::delete('{socialWallPost}',[App\Http\Controllers\API\SocialWallPostController::class, 'delete'])
                ->middleware('can:delete, App\SocialWallPost,organization,program,socialWallPost');
        });

        Route::group([
            'prefix' => '/social-wall-post-type',
        ], function ()
        {
            Route::get('event', [SocialWallPostTypeController::class, 'event']);
            Route::get('message', [SocialWallPostTypeController::class, 'message']);
            Route::get('comment', [SocialWallPostTypeController::class, 'comment']);
        });
    });

    //Imports

    Route::get('/v1/organization/{organization}/import', [App\Http\Controllers\API\ImportController::class, 'index'])->middleware('can:viewAny,App\Import,organization');

    // Dashboard
    Route::get('/v1/organization/{organization}/program/{program}/dashboard',[App\Http\Controllers\API\DashboardController::class, 'index'])->middleware('can:viewAny,App\Dashboard,organization,program');
    Route::get('/v1/organization/{organization}/program/{program}/dashboard/top-merchants/{duration}/{unit}',[App\Http\Controllers\API\DashboardController::class, 'topMerchants'])->middleware('can:viewAny,App\Dashboard,organization,program');
    Route::get('/v1/organization/{organization}/program/{program}/dashboard/top-awards/{duration}/{unit}',[App\Http\Controllers\API\DashboardController::class, 'topAwards'])->middleware('can:viewAny,App\Dashboard,organization,program');
    Route::get('/v1/organization/{organization}/program/{program}/dashboard/award-detail/{duration}/{unit}',[App\Http\Controllers\API\DashboardController::class, 'awardDetail'])->middleware('can:viewAny,App\Dashboard,organization,program');
    Route::get('/v1/organization/{organization}/program/{program}/dashboard/award-peer-detail/{duration}/{unit}',[App\Http\Controllers\API\DashboardController::class, 'awardPeerDetail'])->middleware('can:viewAny,App\Dashboard,organization,program');

    //Manager > Manage Account
    Route::get('/v1/organization/{organization}/program/{program}/monies-available-postings',[App\Http\Controllers\API\ManagerController::class, 'getMoniesAvailablePostings'])->middleware('can:transferMonies,App\Program,organization,program');

    // v2 Routes
    Route::get('/v1/v2-deprecated/program', [App\Http\Controllers\API\V2DeprecatedProgramController::class, 'index'])->middleware('can:viewAny,App\V2Deprecated');
    Route::get('/v1/v2-deprecated/migrate/{account_holder_id}', [App\Http\Controllers\API\MigrationController::class, 'run'])->middleware('can:viewAny,App\V2Deprecated');

    //Push notification for mobileApp
    Route::post('/v1/organization/{organization}/program/{program}/push-notification-token',[App\Http\Controllers\API\PushNotificationController::class, 'store'])->middleware('can:create,App\PushNotification,organization,program');
    //Push notification for mobileApp
    Route::post('/v1/organization/{organization}/program/{program}/send-push-notification',[App\Http\Controllers\API\PushNotificationController::class, 'send'])->middleware('can:create,App\PushNotification,organization,program');
    Route::get('/v1/v2-deprecated/migrate/{account_holder_id}/{step}', [App\Http\Controllers\API\MigrationController::class, 'run'])->middleware('can:viewAny,App\V2Deprecated')->name('runMigrations');
    Route::get('/v1/v2-deprecated/migrate-global/{step}', [App\Http\Controllers\API\MigrationController::class, 'runGlobal'])->middleware('can:viewAny,App\V2Deprecated');
    Route::get('/v1/v2-deprecated/migrate-artisan', [App\Http\Controllers\API\MigrationController::class, 'runArtisanMigrate'])->middleware('can:viewAny,App\V2Deprecated');
    
// UnitNumber
    Route::get('/v1/organization/{organization}/program/{program}/unitnumber',[App\Http\Controllers\API\UnitNumberController::class, 'index'])->middleware('can:viewAny,App\UnitNumber,organization,program');
    Route::post('/v1/organization/{organization}/program/{program}/unitnumber',[App\Http\Controllers\API\UnitNumberController::class, 'store'])->middleware('can:create,App\UnitNumber,organization,program');
    Route::get('/v1/organization/{organization}/program/{program}/unitnumber/{unitNumber}',[App\Http\Controllers\API\UnitNumberController::class, 'show'])->middleware('can:view,App\UnitNumber,organization,program,unitNumber');
    Route::put('/v1/organization/{organization}/program/{program}/unitnumber/{unitNumber}',[App\Http\Controllers\API\UnitNumberController::class, 'update'])->middleware('can:update,App\UnitNumber,organization,program,unitNumber');
    Route::delete('/v1/organization/{organization}/program/{program}/unitnumber/{unitNumber}',[App\Http\Controllers\API\UnitNumberController::class, 'delete'])->middleware('can:delete,App\UnitNumber,organization,program,unitNumber');
    Route::post('/v1/organization/{organization}/program/{program}/unitnumber/{unitNumber}/assign',[App\Http\Controllers\API\UnitNumberController::class, 'assign'])->middleware('can:assign,App\UnitNumber,organization,program,unitNumber');
    Route::post('/v1/organization/{organization}/program/{program}/unitnumber/{unitNumber}/unassign',[App\Http\Controllers\API\UnitNumberController::class, 'unassign'])->middleware('can:assign,App\UnitNumber,organization,program,unitNumber');

// PositionLevel
Route::post('/v1/organization/{organization}/program/{program}/positionlevel',[App\Http\Controllers\API\PositionLevelController::class, 'store'])->middleware('can:create,App\PositionLevel,organization,program');

Route::get('/v1/organization/{organization}/program/{program}/positionlevel',[App\Http\Controllers\API\PositionLevelController::class, 'index'])->middleware('can:viewAny,App\PositionLevel,organization,program');

 Route::put('/v1/organization/{organization}/program/{program}/positionlevel/{positionLevel}',[App\Http\Controllers\API\PositionLevelController::class, 'update'])->middleware('can:update,App\PositionLevel,organization,program,positionLevel');

 Route::get('/v1/organization/{organization}/program/{program}/positionlevel/{positionLevel}',[App\Http\Controllers\API\PositionLevelController::class, 'show'])->middleware('can:view,App\PositionLevel,organization,program,positionLevel');

 Route::delete('/v1/organization/{organization}/program/{program}/positionlevel/{positionLevel}',[App\Http\Controllers\API\PositionLevelController::class, 'delete'])->middleware('can:delete,App\PositionLevel,organization,program,positionLevel');

//Position Permission Assignment
 Route::get('/v1/organization/{organization}/program/{program}/positionpermissions',[App\Http\Controllers\API\PositionPermissionAssignmentController::class, 'getPositionPermission']);

 Route::post('/v1/organization/{organization}/program/{program}/positionlevel/{positionLevel}/assign-permissions',[App\Http\Controllers\API\PositionPermissionAssignmentController::class, 'assignPermissionToPosition'])->middleware('can:assign,App\PositionPermissionAssignment,organization,program');

 Route::get('/v1/organization/{organization}/program/{program}/positionpermissions/{positionPermissionAssignment}',[App\Http\Controllers\API\PositionLevelController::class, 'show'])->middleware('can:view,App\PositionPermissionAssignment,organization,program,positionPermissionAssignment');

//Budget Type
Route::get('/v1/organization/{organization}/program/{program}/budgettypes',[App\Http\Controllers\API\BudgetProgramController::class, 'getBudgetTypes']);

//Budget Program
Route::post('/v1/organization/{organization}/program/{program}/budgetprogram',[App\Http\Controllers\API\BudgetProgramController::class, 'store'])->middleware('can:create,App\BudgetProgram,organization,program');

});
