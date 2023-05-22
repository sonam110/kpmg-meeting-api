<?php

use Illuminate\Support\Facades\Route;
use App\Mail\VerifyOtpMail;
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
    return 'Application working fine.';
    //return view('welcome');
});

Route::get('/optimize-command', function () {
    \Artisan::call('optimize:clear');
    \Artisan::call('cache:forget spatie.permission.cache');
    return redirect('/');
});

Route::get('/test-mail', function () {
    $otpSend = rand(100000,999999);
    $content = [
        "name" => 'Testing Name',
        "body" => 'your verification otp is : '.$otpSend,
    ];
    $recevier = Mail::to('ashok@nrt.co.in')->send(new VerifyOtpMail($content));
    print_r($recevier);

});

Route::get('/meeting-sync', function () {
    \Artisan::call('ical-mail:sync');
    return redirect('/');
});

Route::get('/mail-sync', function () {
    \Artisan::call('mail:sync');
    return redirect('/');
});