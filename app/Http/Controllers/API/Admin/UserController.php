<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MasterUser;
use App\Models\Module;
use App\Models\AssigneModule;
use Validator;
use Auth;
use Exception;
use DB;
use Mail;
use Str;
use App\Mail\WelcomeMail;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user-browse',['only' => ['users']]);
        $this->middleware('permission:user-add', ['only' => ['store']]);
        $this->middleware('permission:user-edit', ['only' => ['update','userAction']]);
        $this->middleware('permission:user-read', ['only' => ['show']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    
    public function users(Request $request)
    {
        try {
            $query = User::with('role:id,name,se_name')->where('status', 1);
            
            if(auth()->user()->role_id!=1)
            {
                $query->where('role_id','!=','1');
            }

            if(!empty($request->email))
            {
                $query->where('email', 'LIKE', '%'.$request->email.'%');
            }

            if(!empty($request->name))
            {
                $query->where('name', 'LIKE', '%'.$request->name.'%');
            }
            if(!empty($request->designation))
            {
                $query->where('designation', 'LIKE', '%'.$request->designation.'%');
            }
            if(!empty($request->mobile_number))
            {
                $query->where('mobile_number', 'LIKE', '%'.$request->mobile_number.'%');
            }
            if($request->status=='active')
            {
                $query->where('status', 1);
            }
            elseif($request->status=='inactive')
            {
                $query->where('status', 0);
            }

            if(!empty($request->role_id))
            {
                $query->where('role_id', $request->role_id);
            }

            if(!empty($request->per_page_record))
            {
                $perPage = $request->per_page_record;
                $page = $request->input('page', 1);
                $total = $query->count();
                $result = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

                $pagination =  [
                    'data' => $result,
                    'total' => $total,
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'last_page' => ceil($total / $perPage)
                ];
                $query = $pagination;
            }
            else
            {
                $query = $query->get();
            }

            return response(prepareResult(false, $query, trans('translate.fetched_records')), config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validation = \Validator::make($request->all(), [
            'name'      => 'required',
            'email'     => 'required|email|unique:users,email',
            'password'  => 'required|string|min:6',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $masterUser = new MasterUser;
            $masterUser->name = $request->name;
            $masterUser->email  = $request->email;
            $masterUser->password =  Hash::make($request->password);
            $masterUser->save();

            $user = new User;
            $user->id = $masterUser->id;
            $user->role_id = $request->role_id;
            $user->name = $request->name;
            $user->email  = $request->email;
            $user->password =  Hash::make($request->password);
            $user->mobile_number = $request->mobile_number;
            $user->address = $request->address;
            $user->designation = $request->designation;
            $user->created_by = auth()->user()->id;
            $user->save();
            $user['id'] = $masterUser->id;



            /*-------Assigne Meeting module for this user*/
            $assigneModule = new AssigneModule;
            $assigneModule->module_id  = '1';
            $assigneModule->user_id  = $masterUser->id;
            $assigneModule->save();

        
            //Role and permission sync
            $role = Role::where('id', $request->role_id)->first();
            $permissions = $role->permissions->pluck('name');
            
            $user->assignRole($role->name);
            foreach ($permissions as $key => $permission) {
                $user->givePermissionTo($permission);
            }

            //Delete if entry exists
            DB::table('password_resets')->where('email', $request->email)->delete();

            $token = \Str::random(64);
            DB::table('password_resets')->insert([
              'email' => $request->email, 
              'token' => $token, 
              'created_at' => \Carbon\Carbon::now()
            ]);

            $baseRedirURL = env('FRONT_URL');
            // Login credentials are following - email:'.$user->email.' , password:'.$randomNo.'.
            $content = [
                "name" => $user->name,
                "body" => 'You have been registered.<br>To reset your password Please click on the link -> <a href='.$baseRedirURL.'/reset-password/'.$token.' style="color: #000;font-size: 18px;text-decoration: underline, font-family: Roboto Condensed, sans-serif;"  target="_blank">Reset your password </a>',
            ];

            if (env('IS_MAIL_ENABLE', false) == true) {
               
                $recevier = Mail::to($request->email)->send(new WelcomeMail($content));
            }

            DB::commit();
            return response()->json(prepareResult(false, $user, trans('translate.created')),config('httpcodes.created'));
        } catch (\Throwable $e) {
            \Log::error($e);
            DB::rollback();
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $userinfo = User::select('*')
                ->where('role_id','!=','1')
                ->find($id);
            if($userinfo)
            {
                return response(prepareResult(false, $userinfo, trans('translate.fetched_records')), config('httpcodes.success'));
            }
            return response(prepareResult(true, [], trans('translate.record_not_found')), config('httpcodes.not_found'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validation = \Validator::make($request->all(), [
            'name'      => 'required',
            'email'     => 'email|required|unique:users,email,'.$id,
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try 
        {
            $user = User::where('id',$id)->first();
        
            if(!$user)
            {
                return response()->json(prepareResult(true, [], trans('translate.user_not_exist')), config('httpcodes.not_found'));
            }
            if($user->role_id=='1')
            {
                $masterUser = MasterUser::find($id);
                $masterUser->name = $request->name;          
                $masterUser->save();
                
                $user->name = $request->name;
                $user->mobile_number = $request->mobile_number;
                $user->designation = $request->designation;
                $user->save();
            }
            else
            {
                $masterUser = MasterUser::find($id);
                $masterUser->name = $request->name;
                $masterUser->email  = $request->email;           
                $masterUser->save();

                $user->role_id = $request->role_id;
                $user->name = $request->name;
                $user->email  = $request->email;
                $user->mobile_number = $request->mobile_number;
                $user->address = $request->address;
                $user->designation = $request->designation;
                $user->save();
            }

            //delete old role and permissions
            DB::table('model_has_roles')->where('model_id', $user->id)->delete();
            DB::table('model_has_permissions')->where('model_id', $user->id)->delete();

            //Role and permission sync
            $role = Role::where('id', $request->role_id)->first();
            $permissions = $role->permissions->pluck('name');
            
            $user->assignRole($role->name);
            foreach ($permissions as $key => $permission) {
                $user->givePermissionTo($permission);
            }
           
            DB::commit();
            return response()->json(prepareResult(false, $user, trans('translate.updated')),config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            DB::rollback();
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    /**
     * Action performed on the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param Request  $request
     * @return \Illuminate\Http\Response
     */
    public function userAction(Request $request)
    {
        try {
            $validation = \Validator::make($request->all(), [
                'action'   => 'required',
                "ids"    => "required|array|min:1",
                "ids.*"  => "required|distinct|min:1|exists:users,id",

            ]);
           
            if ($validation->fails()) {
                return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
            }
            if($request->action =='active'){

                $userDelete = User::whereIn('id',$request->ids)->update(['status'=>'1']);
                return response()->json(prepareResult(false, [],trans('translate.active'), config('httpcodes.success')));
            }
            if($request->action =='inactive'){

                $userDelete = User::whereIn('id',$request->ids)->update(['status'=>'0']);
                return response()->json(prepareResult(false, [], trans('translate.inactive'), config('httpcodes.success')));
            }
            
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
