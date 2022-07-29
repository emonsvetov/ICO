<?php

use App\Http\Controllers\API\SocialWallPostController;
use App\Http\Controllers\API\SocialWallPostTypeController;
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

/*
WARNING WARNING WARNING
NEED TO CREATE MIDDLEWARE THAT CONFIRMS THE CURRENT USER BELONGS TO THE REQUESTED ORGANIZATION
*/

Route::get('/v1/organization/{organization}/event_icons', [App\Http\Controllers\API\EventIconController::class, 'index'])->name('api.v1.event_icons.index');
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

Route::get('/v1/organization/{organization}/program/{program}/event', [App\Http\Controllers\API\EventController::class, 'index'])->name('api.v1.organization.program.event.index')->middleware('can:viewAny,App\ProgramEvent,organization,program');
Route::get('/v1/organization/{organization}/program/{program}/event/{event}', [App\Http\Controllers\API\EventController::class, 'show'])->name('api.v1.organization.program.event.show')->middleware('can:view,App\ProgramEvent,organization,program,event');
Route::post('/v1/organization/{organization}/program/{program}/event', [App\Http\Controllers\API\EventController::class, 'store'])->name('api.v1.organization.program.event.store')->middleware('can:create,App\ProgramEvent,organization,program');
Route::put('/v1/organization/{organization}/program/{program}/event/{event}', [App\Http\Controllers\API\EventController::class, 'update'])->name('api.v1.organization.program.event.update')->middleware('can:update,App\ProgramEvent,organization,program,event');
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
    Route::post('/v1/admin/login', [App\Http\Controllers\API\AuthController::class, 'adminLogin'])->name('api.v1.adminLogin');
    Route::post('/v1/register', [App\Http\Controllers\API\AuthController::class, 'register'])->name('api.v1.register');

    Route::post('/v1/password/forgot', [App\Http\Controllers\API\PasswordController::class, 'forgotPassword']);
    Route::post('/v1/password/reset', [App\Http\Controllers\API\PasswordController::class, 'reset']);

    Route::get('/v1/domain', [App\Http\Controllers\API\DomainController::class, 'getProgram']);

    Route::get('/v1/domain', [App\Http\Controllers\API\DomainController::class, 'getProgram']);

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
    //Route::delete('/v1/organization/{organization}/user/{user}', [App\Http\Controllers\API\UserController::class, 'destroy'])->name('api.v1.organization.user.destroy')->middleware('can:delete,user');

    Route::get('/v1/organization', [App\Http\Controllers\API\OrganizationController::class, 'index'])->middleware('can:viewAny,App\Organization');
    Route::get('/v1/organization/{organization}', [App\Http\Controllers\API\OrganizationController::class, 'show'])->name('api.v1.organization.show')->middleware('can:view,organization');
    Route::post('/v1/organization', [App\Http\Controllers\API\OrganizationController::class, 'store'])->name('api.v1.organization.store')->middleware('can:create,App\Organization');
    Route::put('/v1/organization/{organization}', [App\Http\Controllers\API\OrganizationController::class, 'update'])->name('api.v1.organization.update')->middleware('can:update,organization');
    //Route::delete('/v1/organization/{organization}', [App\Http\Controllers\API\OrganizationController::class, 'destroy'])->name('api.v1.organization.destroy')->middleware('can:delete,organization');

    //ROLES & PERMISSIONS
    Route::get('/v1/organization/{organization}/user/{user}/role', [App\Http\Controllers\API\RoleController::class, 'userRoleIndex'])->name('api.v1.organization.user.roles')->middleware('can:view,App\Role,user');
    Route::put('/v1/organization/{organization}/user/{user}/role/{role}', [App\Http\Controllers\API\RoleController::class, 'assign'])->name('api.v1.organization.user.role.assign')->middleware('can:update,role');
    Route::delete('/v1/organization/{organization}/user/{user}/role/{role}', [App\Http\Controllers\API\RoleController::class, 'revoke'])->name('api.v1.organization.user.role.revoke')->middleware('can:update,role');

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
    [App\Http\Controllers\API\DomainIPController::class, 'delete'])->name('api.v1.domain_ip.store')->middleware('can:deleteIp,App\Domain,organization,domain');

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

    //Submerchant Routes
    Route::get('/v1/merchant/{merchant}/submerchant', [App\Http\Controllers\API\SubmerchantController::class, 'index'])->middleware('can:viewAny,App\Submerchant,merchant');
    Route::post('/v1/merchant/{merchant}/submerchant', [App\Http\Controllers\API\SubmerchantController::class, 'store'])->middleware('can:add,App\Submerchant,merchant');
    Route::delete('/v1/merchant/{merchant}/submerchant/{submerchant}', [App\Http\Controllers\API\SubmerchantController::class, 'delete'])->middleware('can:remove,App\Submerchant,merchant');

    // Program routes
    Route::get('/v1/organization/{organization}/program', [App\Http\Controllers\API\ProgramController::class, 'index'])->middleware('can:viewAny,App\Program,organization');
    Route::post('/v1/organization/{organization}/program', [App\Http\Controllers\API\ProgramController::class, 'store'])->middleware('can:create,App\Program,organization');
    Route::get('/v1/organization/{organization}/program/{program}', [App\Http\Controllers\API\ProgramController::class, 'show'])->middleware('can:view,App\Program,organization,program');
    Route::put('/v1/organization/{organization}/program/{program}', [App\Http\Controllers\API\ProgramController::class, 'update'])->middleware('can:update,App\Program,organization,program');
    Route::patch('/v1/organization/{organization}/program/{program}/move', [App\Http\Controllers\API\ProgramController::class, 'move'])->middleware('can:move,App\Program,organization,program');
    Route::delete('/v1/organization/{organization}/program/{program}', [App\Http\Controllers\API\ProgramController::class, 'delete'])->middleware('can:delete,App\Program,organization,program');

    // Subprogram Routes
    Route::get('/v1/organization/{organization}/program/{program}/subprogram', [App\Http\Controllers\API\SubprogramController::class, 'index'])->middleware('can:viewAny,App\Subprogram,organization,program');
    Route::get('/v1/organization/{organization}/subprogram/{program}/available/{action}', [App\Http\Controllers\API\SubprogramController::class, 'available'])->middleware('can:viewAny,App\Subprogram,organization,program');
    Route::patch('/v1/organization/{organization}/subprogram/{program}/unlink', [App\Http\Controllers\API\SubprogramController::class, 'unlink'])->middleware('can:unlink,App\Subprogram,organization,program');
    Route::get('/v1/organization/{organization}/program/{program}/descendents', [App\Http\Controllers\API\SubprogramController::class, 'getDescendents'])->middleware('can:viewAny,App\Subprogram,organization,program');

    //ProgramMerchant routes

    Route::get('/v1/organization/{organization}/program/{program}/merchant',
    [App\Http\Controllers\API\ProgramMerchantController::class, 'index'])->name('api.v1.program.merchant.index')->middleware('can:viewAny,App\ProgramMerchant,organization,program');

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

    Route::get('/v1/organization/{organization}/program/{program}/user', [App\Http\Controllers\API\ProgramUserController::class, 'index'])->middleware('can:viewAny,App\ProgramUser,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}',[App\Http\Controllers\API\ProgramUserController::class, 'show'])->middleware('can:view,App\ProgramUser,organization,program,user');

    Route::post('/v1/organization/{organization}/program/{program}/user',[App\Http\Controllers\API\ProgramUserController::class, 'store'])->middleware('can:add,App\ProgramUser,organization,program');

    Route::put('/v1/organization/{organization}/program/{program}/user/{user}',[App\Http\Controllers\API\ProgramUserController::class, 'update'])->middleware('can:update,App\ProgramUser,organization,program,user');

    Route::delete('/v1/organization/{organization}/program/{program}/user/{user}',
    [App\Http\Controllers\API\ProgramUserController::class, 'delete'])->middleware('can:remove,App\ProgramUser,organization,program,user');

    //UserProgram routes

    Route::get('/v1/organization/{organization}/user/{user}/program', [App\Http\Controllers\API\UserProgramController::class, 'index'])->middleware('can:viewAny,App\UserProgram,organization,user');

    Route::post('/v1/organization/{organization}/user/{user}/program',[App\Http\Controllers\API\UserProgramController::class, 'store'])->middleware('can:add,App\UserProgram,organization,user');

    Route::delete('/v1/organization/{organization}/user/{user}/program/{program}',
    [App\Http\Controllers\API\UserProgramController::class, 'delete'])->middleware('can:remove,App\UserProgram,organization,user,program');

    Route::get('/v1/organization/{organization}/user/{user}/program/{program}/role',
    [App\Http\Controllers\API\UserProgramController::class, 'getRole'])->middleware('can:getRoles,App\UserProgram,organization,user,program');

    //Reports routes
    Route::get('/v1/organization/{organization}/reports/{type}',[App\Http\Controllers\API\ReportController::class, 'index'])->middleware('can:viewAny,App\Report');

    //MerchantGiftcodes

    Route::get('/v1/merchant/{merchant}/giftcode', [App\Http\Controllers\API\MerchantGiftcodeController::class, 'index'])->middleware('can:viewAny,App\MerchantGiftcode,merchant');

    Route::post('/v1/merchant/{merchant}/giftcode', [App\Http\Controllers\API\MerchantGiftcodeController::class, 'store'])->middleware('can:add,App\MerchantGiftcode,merchant');

    //MerchantOptimalValues

    Route::get('/v1/merchant/{merchant}/optimalvalue', [App\Http\Controllers\API\MerchantOptimalValueController::class, 'index'])->middleware('can:viewAny,App\MerchantOptimalValue,merchant');

    Route::post('/v1/merchant/{merchant}/optimalvalue', [App\Http\Controllers\API\MerchantOptimalValueController::class, 'store'])->middleware('can:add,App\MerchantOptimalValue,merchant');

    Route::put('/v1/merchant/{merchant}/optimalvalue/{optimalValue}',[App\Http\Controllers\API\MerchantOptimalValueController::class, 'update'])->middleware('can:update,App\MerchantOptimalValue,merchant,optimalValue');

    Route::delete('/v1/merchant/{merchant}/optimalvalue/{optimalValue}',[App\Http\Controllers\API\MerchantOptimalValueController::class, 'destroy'])->middleware('can:delete,App\MerchantOptimalValue,merchant,optimalValue');

    //ProgramLogin

    Route::post('/v1/organization/{organization}/program/{program}/login',[App\Http\Controllers\API\ProgramLoginController::class, 'login'])->middleware('can:login,App\ProgramLogin,organization,program');

    //EventType

    Route::get('/v1/eventtype',[App\Http\Controllers\API\EventTypeController::class, 'index'])->middleware('can:viewAny,App\EventType');

    //EmailTemplate

    Route::get('/v1/emailtemplate',[App\Http\Controllers\API\EmailTemplateController::class, 'index'])->middleware('can:viewAny,App\EmailTemplate');
    //Award

    Route::post('/v1/organization/{organization}/program/{program}/award',[App\Http\Controllers\API\AwardController::class, 'store'])->middleware('can:create,App\Award,organization,program');

    //ProgramParticipants

    Route::get('/v1/organization/{organization}/program/{program}/participant',[App\Http\Controllers\API\ProgramParticipantController::class, 'index'])->middleware('can:viewAny,App\ProgramParticipant,organization,program');

    Route::patch('/v1/organization/{organization}/program/{program}/participant/status',[App\Http\Controllers\API\ProgramParticipantController::class, 'changeStatus'])->middleware('can:changeStatus,App\ProgramParticipant,organization,program');

    //Get User Point Balance

    Route::get('/v1/organization/{organization}/program/{program}/user/{user}/balance',[App\Http\Controllers\API\ProgramUserController::class, 'readBalance'])->middleware('can:readBalance,App\ProgramUser,organization,program,user');

    //Statuses

    Route::get('/v1/status',[App\Http\Controllers\API\StatusController::class, 'index'])->middleware('can:viewAny,App\Status');

    //Checkout

    Route::post('/v1/organization/{organization}/program/{program}/checkout',[App\Http\Controllers\API\CheckoutController::class, 'store'])->middleware('can:checkout,App\Checkout,organization,program');

    // ProgramTemplate

    Route::post('/v1/organization/{organization}/program/{program}/template',[App\Http\Controllers\API\ProgramTemplateController::class, 'store'])->middleware('can:create,App\ProgramTemplate,organization,program');

    Route::put('/v1/organization/{organization}/program/{program}/template/{programTemplate}',[App\Http\Controllers\API\ProgramTemplateController::class, 'update'])->middleware('can:update,App\ProgramTemplate,organization,program');

	//Manager invite participant
    Route::put('/v1/organization/{organization}/program/{program}/invite', [App\Http\Controllers\API\InvitationController::class, 'invite'])->middleware('can:invite,App\Invitation,organization,program');
    Route::post('/v1/organization/{organization}/program/{program}/inviteResend', [App\Http\Controllers\API\InvitationController::class, 'resend'])->middleware('can:resend,App\Invitation,organization,program');

    // Leaderboard

    Route::get('/v1/organization/{organization}/program/{program}/leaderboard',[App\Http\Controllers\API\LeaderboardController::class, 'index'])->middleware('can:viewAny,App\Leaderboard,organization,program');

    Route::get('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}',[App\Http\Controllers\API\LeaderboardController::class, 'show'])->middleware('can:view,App\Leaderboard,organization,program,leaderboard');

    Route::post('/v1/organization/{organization}/program/{program}/leaderboard',[App\Http\Controllers\API\LeaderboardController::class, 'store'])->middleware('can:create,App\Leaderboard,organization,program');

    Route::put('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}',[App\Http\Controllers\API\LeaderboardController::class, 'update'])->middleware('can:update,App\Leaderboard,organization,program,leaderboard');

    Route::delete('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}',[App\Http\Controllers\API\LeaderboardController::class, 'delete'])->middleware('can:delete,App\Leaderboard,organization,program,leaderboard');

    // LeaderboardType

    Route::get('/v1/organization/{organization}/program/{program}/leaderboardType',[App\Http\Controllers\API\LeaderboardTypeController::class, 'index'])->middleware('can:viewAny,App\LeaderboardType,organization,program');

    // LeaderboardEvent

    Route::get('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}/event',[App\Http\Controllers\API\LeaderboardEventController::class, 'index'])->middleware('can:viewAny,App\LeaderboardEvent,organization,program,leaderboard');

    Route::get('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}/assignableEvent',[App\Http\Controllers\API\LeaderboardEventController::class, 'assignable'])->middleware('can:viewAny,App\LeaderboardEvent,organization,program,leaderboard');

    Route::patch('/v1/organization/{organization}/program/{program}/leaderboard/{leaderboard}/event',[App\Http\Controllers\API\LeaderboardEventController::class, 'assign'])->middleware('can:assign,App\LeaderboardEvent,organization,program,leaderboard');
});
