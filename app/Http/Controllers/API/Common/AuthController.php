<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Auth;
use DB;
use Exception;
use Mail;
use Spatie\Permission\Models\Permission;
use App\Mail\ForgotPasswordMail;
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

        try {
            $email = $request->email;
            $user = User::select('*')->where('email', $email)->first();
            if (!$user)  {
                return response()->json(prepareResult(true, [], trans('translate.user_not_exist')), config('httpcodes.not_found'));
            }

            if(in_array($user->status, [0,2])) {
                return response()->json(prepareResult(true, [], trans('translate.account_is_inactive')), config('httpcodes.unauthorized'));
            }

            if(Hash::check($request->password, $user->password)) {
                $accessToken = $user->createToken('authToken')->accessToken;
                $user['access_token'] = $accessToken;
                $role   = Role::where('id', $user->role_id)->first();
                $user['roles']    = $role;
                $user['permissions']  = $role->permissions()->select('id','name as action','group_name as subject','se_name')->get();

                return response()->json(prepareResult(false, $user, trans('translate.request_successfully_submitted')),config('httpcodes.success'));
            } else {
                return response()->json(prepareResult(true, [], trans('translate.invalid_username_and_password')),config('httpcodes.unauthorized'));
            }
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

        try {
            $user = User::where('email',$request->email)->first();
            if (!$user) {
                return response()->json(prepareResult(true, [], trans('translate.user_not_exist')), config('httpcodes.not_found'));
            }

            if(in_array($user->status, [0,2])) {
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

            $baseRedirURL = env('APP_URL');
            $content = [
                "name" => $user->fullname,
                "passowrd_link" => $baseRedirURL.'/authentication/reset-password/'.$token
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
            'password'  => 'required|string|min:6',
            'token'     => 'required'
        ]);

        if ($validation->fails()) {
            return response()->json(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        try {
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

            if(in_array($user->status, [0,2])) {
                return response()->json(prepareResult(true, [], trans('translate.account_is_inactive')), config('httpcodes.unauthorized'));
            }

            $user = User::where('email', $tokenExist->email)
                    ->update(['password' => Hash::make($request->password),'password_last_updated' => date('Y-m-d')]);
 
            DB::table('password_resets')->where(['email'=> $tokenExist->email])->delete();

            ////////notification and mail//////////
            /*$variable_data = [
                '{{name}}' => $user->name
            ];*/
           // notification('password-changed', $user, $variable_data);
            /////////////////////////////////////


            return response()->json(prepareResult(false, $tokenExist->email, trans('translate.password_changed')),config('httpcodes.success'));

        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function changePassword(Request $request)
    {
        $validation = \Validator::make($request->all(),[ 
            'old_password'  => 'required|string|min:6',
            'password'      => 'required|string|min:6'
        ]);

        if ($validation->fails()) {
            return response()->json(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        try {
            
            $user = User::where('email', Auth::user()->email)->first();
            
            if(in_array($user->status, [0,2])) {
                return response()->json(prepareResult(true, [], trans('translate.account_is_inactive')), config('httpcodes.unauthorized'));
            }
            if(Hash::check($request->old_password, $user->password)) {
                $user = User::where('email', Auth::user()->email)
                    ->update(['password' => Hash::make($request->password),'password_last_updated' => date('Y-m-d')]);

                ////////notification and mail//////////
                /*$variable_data = [
                    '{{name}}' => $user->name
                ];*/
                //notification('password-changed', $user, $variable_data);
                /////////////////////////////////////
            }
            else
            {
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
            return prepareResult(true,'Token',$usertoken,config('httpcodes.success'));
        } else {
            return prepareResult(false,'Token not found',[],config('httpcodes.bad_request'));
        }

    }
    
    public function unauthorized(Request $request)
    {
       return prepareResult(false,[],'Unauthorized. Please login.', config('httpcodes.unauthorized'));
    }

}
