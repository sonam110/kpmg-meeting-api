<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\Admin\UserController;
use App\Http\Controllers\API\Common\MeetingController;
use App\Http\Controllers\API\Common\NotesController;
use App\Http\Controllers\API\Common\ActionItemController;
use App\Http\Controllers\API\Common\RoleController;
use App\Http\Controllers\API\Common\DashboardController;
use App\Http\Controllers\API\Admin\AppSettingController;
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

/*Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});*/



Route::namespace('App\Http\Controllers\API\Common')->group(function () {

    Route::controller(AuthController::class)->group(function () {
        Route::get('unauthorized', 'unauthorized')->name('unauthorized');
        Route::post('login', 'login')->name('login');
        Route::post('forgot-password', 'forgotPassword')->name('forgot-password');
        Route::get('authentication/reset-password/{token}','resetPassword')->name('password.reset');
        Route::post('update-password', 'updatePassword')->name('update-password');
    });
    Route::group(['middleware' => 'auth:api'],function () {
        Route::controller(AuthController::class)->group(function () {
            Route::post('logout', 'logout')->name('logout');
            Route::post('change-password', 'changePassword')->name('changePassword');
        });

        Route::post('dashboard',[DashboardController::class, 'dashboard'])->name('dashboard');

        /*----------Roles------------------------------*/
        Route::post('roles',[RoleController::class, 'roles'])->name('roles');
        Route::resource('role', RoleController::class)->only([
            'store','destroy','show', 'update'
        ]);

        Route::controller(FileUploadController::class)->group(function () {
            Route::post('file-uploads', 'fileUploads')->name('file-uploads');
            Route::post('file-upload', 'store')->name('file-upload');
        });
        Route::post('meetings',[MeetingController::class, 'meetings'])->name('meetings');
        Route::resource('meeting', MeetingController::class)->only([
            'store','destroy','show', 'update'
        ]);
        Route::post('meeting-action', [MeetingController::class, 'action'])->name('meeting-action');

        Route::post('notes',[NotesController::class, 'notes'])->name('notes');
        Route::resource('note', NotesController::class)->only([
            'store','destroy','show', 'update'
        ]);
        Route::post('note-action', [NotesController::class, 'action'])->name('note-action');
        Route::post('action-items',[ActionItemController::class, 'actionItems'])->name('action-items');
        Route::resource('action-item', ActionItemController::class)->only([
            'store','destroy','show', 'update'
        ]);
        Route::post('action-item-action', [ActionItemController::class, 'action'])->name('action-item-action');
       

    });

}); 

Route::namespace('App\Http\Controllers\API\Admin')->group(function () {
    Route::group(['middleware' => 'auth:api'],function () {
        Route::post('users',[UserController::class, 'users'])->name('users');
        Route::post('user-action',[UserController::class, 'userAction'])->name('user-action');
        Route::resource('user', UserController::class)->only([
            'store','destroy','show', 'update'
        ]);

        Route::get('app-setting',[AppSettingController::class, 'appSetting'])->name('app-setting');
        Route::post('update-setting',[AppSettingController::class, 'updateSetting'])->name('update-setting');

    });
});    




