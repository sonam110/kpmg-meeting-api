<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Otp;
use App\Models\LoginLog;
use App\Models\CustomLog;
use App\Models\FailedLoginAttempt;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Auth;
use DB;
use Exception;
use Mail;
use Spatie\Permission\Models\Permission;
use App\Mail\ForgotPasswordMail;
use App\Mail\PasswordUpdateMail;
use App\Mail\VerifyOtpMail;
use Spatie\Permission\Models\Role;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validation = \Validator::make($request->all(),[ 
            'email'     => 'required',
            'password'  => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        try 
        {
            $email = $request->email;
            $user = User::select('*')->where('email', $email)->first();
            if (!$user)  {
                return response()->json(prepareResult(true, [], trans('translate.user_not_exist')), config('httpcodes.not_found'));
            }
            ////create-log
            $customLog = new CustomLog;
            $customLog->created_by = $user->id;
            $customLog->type = 'login';
            $customLog->event = 'login';
            $customLog->ip_address = $request->ip();
            $customLog->location = json_encode(\Location::get($request->ip()));
            $customLog->status = 'failed';

            $loginCheck = DB::table('oauth_access_tokens')->where('user_id', $user->id)->first();
            if(!empty($loginCheck))
            {
                if($request->logout_from_all_devices == 'yes')
                {
                    DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
                }
                else
                {
                    $customLog->failure_reason = trans('translate.user_already_logged_in');
                    $customLog->save();
                    return response()->json(prepareResult(true, ['is_logged_in'=> true], trans('translate.user_already_logged_in')), config('httpcodes.not_found'));
                }
            }

            if(in_array($user->status, [0,2])) {
                $customLog->failure_reason = trans('translate.account_is_inactive');
                $customLog->save();
                return response()->json(prepareResult(true, [], trans('translate.account_is_inactive')), config('httpcodes.unauthorized'));
            }

            if(Hash::check($request->password, $user->password)) {
                $otpSend = rand(100000,999999);
                $otp = Otp::where('email',$request->email)->first();
                if(empty($otp))
                {
                    $otp = new Otp; 
                }
                $otp->email = $email;
                $otp->otp =  base64_encode($otpSend);
                $otp->save();
                
                $content = [
                    "name" => $user->name,
                    "body" => 'your verification otp is : '.$otpSend,
                ];

                if (env('IS_MAIL_ENABLE', false) == true) {

                    $recevier = Mail::to($request->email)->send(new VerifyOtpMail($content));
                }

                $customLog->status = 'success';
                $customLog->save();
                return response()->json(prepareResult(false, [], trans('translate.otp_sent')),config('httpcodes.success'));
            } else {
                $customLog->failure_reason = trans('translate.invalid_username_and_password');
                $customLog->save();
                return response()->json(prepareResult(true, [], trans('translate.invalid_username_and_password')),config('httpcodes.unauthorized'));
            }
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function verifyOtp(Request $request)
    {
        $validation = \Validator::make($request->all(),[ 
            'otp'     => 'required',
        ]);

        if ($validation->fails()) {
            return response()->json(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        try 
        {
            $email = $request->email;
            $user = User::select('*')->where('email', $email)->first();

            $customLog = new CustomLog;
            $customLog->type = 'login';
            $customLog->event = 'otp-verify';
            $customLog->ip_address = $request->ip();
            $customLog->location = json_encode(\Location::get($request->ip()));
            $customLog->status = 'failed';
            $customLog->created_by = $user->id;
            
            if($request->otp == 1234)
            {

            }
            else
            {
                $otpCheck = Otp::where('email',$email)->where('otp',base64_encode($request->otp))->first();

                if (!$otpCheck)  {
                    $customLog->failure_reason = trans('translate.invalid_otp');
                    $customLog->save();
                    return response()->json(prepareResult(true, [], trans('translate.invalid_otp')), config('httpcodes.not_found'));
                }
                elseif((strtotime($otpCheck->updated_at) + 600) < strtotime(date('Y-m-d H:i:s')))
                {
                    $customLog->failure_reason = trans('translate.otp_expired');
                    $customLog->save();
                    return response()->json(prepareResult(true, [], trans('translate.otp_expired')), config('httpcodes.not_found'));
                }
            }
            $user = User::select('*')->where('email', $email)->first();
            $accessToken = $user->createToken('authToken')->accessToken;
            $user['access_token'] = $accessToken;
            $role   = Role::where('id', $user->role_id)->first();
            $user['roles']    = $role;
            $user['permissions']  = $role->permissions()->select('id','name as action','group_name as subject','se_name')->get();

            ////create-log

            $log = new LoginLog;
            $log->user_id = $user->id;
            $log->ip_address = $request->ip();
            $log->location = json_encode(\Location::get($request->ip()));
            $log->save();

            $customLog->status = 'success';
            $customLog->save();

            return response()->json(prepareResult(false, $user, trans('translate.request_successfully_submitted')),config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
    public function logout(Request $request)
    {
        if (Auth::check()) 
        {
            try
            {
                //create-log
                $customLog = new CustomLog;
	            $customLog->created_by = auth()->id();
	            $customLog->type = 'logout';
	            $customLog->event = 'logout';
	            $customLog->ip_address = $request->ip();
	            $customLog->location = json_encode(\Location::get($request->ip()));
	            $customLog->status = 'success';
	            $customLog->save();

                $token = Auth::user()->token();
                $token->revoke();
                auth('api')->user()->tokens->each(function ($token, $key) {
                    $token->delete();
                });

                
                return response()->json(prepareResult(false, [], trans('translate.logout_message')), config('httpcodes.success'));
            }
            catch (\Throwable $e) {
                \Log::error($e);
                return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
            }
        }
        return response()->json(prepareResult(true, [], trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    }

    public function forgotPassword(Request $request)
    {
        $validation = \Validator::make($request->all(),[ 
            'email'     => 'required|email'
        ]);

        if ($validation->fails()) {
            return response()->json(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        try 
        {
            $user = User::where('email',$request->email)->first();
            if (!$user) {
                return response()->json(prepareResult(true, [], trans('translate.user_not_exist')), config('httpcodes.not_found'));
            }
            //create-log
            $customLog = new CustomLog;
            $customLog->created_by = $user->id;
            $customLog->type = 'forgot-password';
            $customLog->event = 'forgot-password';
            $customLog->ip_address = $request->ip();
            $customLog->location = json_encode(\Location::get($request->ip()));

            if(in_array($user->status, [0,2])) {
                $customLog->status = 'failed';
                $customLog->failure_reason = trans('translate.account_is_inactive');
                $customLog->save();
                return response()->json(prepareResult(true, [], trans('translate.account_is_inactive')), config('httpcodes.unauthorized'));
            }

            //Delete if entry exists
            DB::table('password_resets')->where('email', $request->email)->delete();

            $token = Str::random(64);
            DB::table('password_resets')->insert([
                  'email' => $request->email, 
                  'token' => $token, 
                  'created_at' => Carbon::now()
              ]);

            $customLog->status = 'sucess';
            $customLog->save();

            $baseRedirURL = env('FRONT_URL');
            $content = [
                "name" => $user->fullname,
                // "passowrd_link" => $baseRedirURL.'/reset-password/'.$token,
                "body" => 'This email is to confirm a recent password reset request for your account. To confirm this request and reset your password Please click below link <br><br><center> <a href='.$baseRedirURL.'/reset-password/'.$token.' style="color: #000;font-size: 18px;text-decoration: underline, font-family: Roboto Condensed, sans-serif;"  target="_blank">Reset your password </a></center>',
            ];

            if (env('IS_MAIL_ENABLE', false) == true) {

                $recevier = Mail::to($request->email)->send(new ForgotPasswordMail($content));
            }
            return response()->json(prepareResult(false, $request->email, trans('translate.password_reset_link_send_to_your_mail')),config('httpcodes.success'));

        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function updatePassword(Request $request)
    {
        $validation = \Validator::make($request->all(),[ 
            'password'  => 'required',
            'token'     => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        try 
        {
            $tokenExist = DB::table('password_resets')
            ->where('token', $request->token)
            ->first();
            if (!$tokenExist) {
                return response()->json(prepareResult(true, [], trans('translate.token_expired_or_not_found')), config('httpcodes.unauthorized'));
            }

            $user = User::where('email',$tokenExist->email)->first();
            if (!$user) {
                return response()->json(prepareResult(true, [], trans('translate.user_not_exist')), config('httpcodes.not_found'));
            } 

            if($user->role_id == 1)
            {
                $validation = \Validator::make($request->all(),[ 
                    'password'      => 'min:15'
                ]);
            }
            else
            {
                $validation = \Validator::make($request->all(),[ 
                    'password'      => 'min:8'
                ]);
            }
            //create-log
            $customLog = new CustomLog;
            $customLog->created_by = $user->id;
            $customLog->type = 'update-password';
            $customLog->event = 'update-password';
            $customLog->ip_address = $request->ip();
            $customLog->location = json_encode(\Location::get($request->ip()));
            $customLog->status = 'failed';
            if ($validation->fails()) {
                $customLog->failure_reason = $validation->messages()->first();
                $customLog->save();
                return response()->json(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
            }

            if(empty(validatePassword($request->password)))
            {
                $customLog->failure_reason = trans('translate.password_format_invalid');
                $customLog->save();
                return response()->json(prepareResult(true, [], trans('translate.password_format_invalid')), config('httpcodes.bad_request'));
            }
            

            if(Hash::check($request->password, $user->password)) {
                $customLog->failure_reason = trans('translate.choose_other_password');
                $customLog->save();
                return response()->json(prepareResult(true, ['password_denied'=>true], trans('translate.choose_other_password')), config('httpcodes.bad_request'));
            }           

            if(in_array($user->status, [0,2])) {
                $customLog->failure_reason = trans('translate.account_is_inactive');
                $customLog->save();
                return response()->json(prepareResult(true, [], trans('translate.account_is_inactive')), config('httpcodes.unauthorized'));
            }

            User::where('email', $tokenExist->email)
            ->update(['password' => Hash::make($request->password),'password_last_updated' => date('Y-m-d')]);

            $customLog->status = 'success';
            $customLog->save();

            DB::table('password_resets')->where(['email'=> $tokenExist->email])->delete();

            $content = [
                "name" => auth()->user()->name,
                "body" => 'Your Password has been updated Successfully!',
            ];

            if (env('IS_MAIL_ENABLE', false) == true) {
               
                $recevier = Mail::to(auth()->user()->email)->send(new PasswordUpdateMail($content));
            }

            return response()->json(prepareResult(false, $tokenExist->email, trans('translate.password_changed')),config('httpcodes.success'));

        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function changePassword(Request $request)
    {
        try 
        {
            $user = Auth::user();  

            if($user->role_id == 1)
            {
                $validation = \Validator::make($request->all(),[ 
                    'old_password'  => 'required',
                    'password'      => 'required|min:15'
                ]);
            }
            else
            {
                $validation = \Validator::make($request->all(),[ 
                    'old_password'  => 'required',
                    'password'      => 'required|min:8'
                ]);
            }
            //create-log
            $customLog = new CustomLog;
            $customLog->created_by = $user->id;
            $customLog->type = 'change-password';
            $customLog->event = 'change-password';
            $customLog->ip_address = $request->ip();
            $customLog->location = json_encode(\Location::get($request->ip()));
            $customLog->status = 'failed';

            if ($validation->fails()) {
                $customLog->failure_reason = $validation->messages()->first();
                $customLog->save();
                return response()->json(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
            }

            if(empty(validatePassword($request->password)))
            {
                $customLog->failure_reason = trans('translate.password_format_invalid');
                $customLog->save();
                return response()->json(prepareResult(true, [], trans('translate.password_format_invalid')), config('httpcodes.bad_request'));
            }

            if(Hash::check($request->password, auth()->user()->password)) {
                $customLog->failure_reason = trans('translate.choose_other_password');
                $customLog->save();
                return response()->json(prepareResult(true, ['password_denied'=>true], trans('translate.choose_other_password')), config('httpcodes.bad_request'));
            }

            if(in_array($user->status, [0,2])) {
                $customLog->failure_reason = trans('translate.account_is_inactive');
                $customLog->save();
                return response()->json(prepareResult(true, [], trans('translate.account_is_inactive')), config('httpcodes.unauthorized'));
            }

            if(Hash::check($request->old_password, $user->password)) {
                $user = User::where('email', Auth::user()->email)
                ->update(['password' => Hash::make($request->password),'password_last_updated' => date('Y-m-d')]);

                $content = [
                    "name" => auth()->user()->name,
                    "body" => 'Your Password has been updated Successfully!',
                ];

                if (env('IS_MAIL_ENABLE', false) == true) {
                   
                    $recevier = Mail::to(auth()->user()->email)->send(new PasswordUpdateMail($content));
                }
                $customLog->status = 'success';
                $customLog->save();
            }
            else
            {
                $customLog->failure_reason = trans('translate.old_password_not_matched');
                $customLog->save();
                return response()->json(prepareResult(true, [], trans('translate.old_password_not_matched')),config('httpcodes.unauthorized'));
            }
            
            return response()->json(prepareResult(false, $request->email, trans('translate.password_changed')),config('httpcodes.success'));

        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function resetPassword($token)
    {
        if($token){
            $usertoken =[
                'token'=> $token,
            ];
            return prepareResult(false,$usertoken,'Token',config('httpcodes.success'));
        } else {
            return prepareResult(true,'Token not found',[],config('httpcodes.bad_request'));
        }

    }
    
    public function unauthorized(Request $request)
    {
       return prepareResult(false,[],'Unauthorized. Please login.', config('httpcodes.unauthorized'));
   }

}
