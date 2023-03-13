<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use DB;
use Str;
use App\Models\User;

class RoleController extends Controller
{
   
    public function __construct()
    {


        $this->middleware('permission:role-browse',['except' => ['show']]);
        $this->middleware('permission:role-add', ['only' => ['store']]);
        $this->middleware('permission:role-edit', ['only' => ['update']]);
        $this->middleware('permission:role-read', ['only' => ['show']]);
        $this->middleware('permission:role-delete', ['only' => ['destroy']]);

    }
    
    public function roles(Request $request)
    {
        try {
            $query = Role::select('*')->with('permissions');

            if(!empty($request->name))
            {
                $query->where('name', 'LIKE', '%'.$request->name.'%');
            }

            if(!empty($request->se_name))
            {
                $query->where('se_name', 'LIKE', '%'.$request->se_name.'%');
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
        } catch(Exception $exception) {
            \Log::error($exception);
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function store(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'se_name'   => 'required',
            'permissions' => 'required'
        ]);
        if ($validator->fails()) {
            return response(prepareResult(true, $validator->messages(), $validator->messages()->first()), config('httpcodes.bad_request'));
        }
        
        DB::beginTransaction();
        try {
            $role = new Role;
            $role->name = \Str::slug(substr($request->se_name, 0, 20));
            $role->se_name  = $request->se_name;
            $role->guard_name  = 'api';
            $role->save();
            DB::commit();
            if($role) {
                $role->syncPermissions($request->permissions);
            }
            
            return response()->json(prepareResult(false, $role, trans('translate.created')),config('httpcodes.created'));
        } catch(Exception $exception) {
            \Log::error($exception);
            DB::rollback();
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function show(Role $role)
    {
        try {
            $roleInfo = Role::with('permissions');

            $roleInfo = $roleInfo->find($role->id);
            if($roleInfo)
            {
                return response(prepareResult(false, $roleInfo, trans('translate.fetched_records')), config('httpcodes.success'));
            }
            return response(prepareResult(true, [], trans('translate.record_not_found')), config('httpcodes.not_found'));

        } catch(Exception $exception) {
             \Log::error($exception);
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function update(Request $request, Role $role)
    {
        $validator = \Validator::make($request->all(), [
            'se_name'   => 'required',
            'permissions' => 'required'
        ]);
        if ($validator->fails()) {
             return response(prepareResult(true, $validator->messages(), $validator->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $roleInfo = Role::select('*');
            $roleInfo = $roleInfo->find($role->id);
            if($roleInfo)
            {
                $roleInfo->se_name  = $request->se_name;
                $roleInfo->save();
                DB::commit();
                if($roleInfo) {
                    $roleInfo->syncPermissions($request->permissions);
                }
                return response()->json(prepareResult(false, $roleInfo, trans('translate.updated')),config('httpcodes.success'));
            }
           return response()->json(prepareResult(true, [],'No Role found', config('httpcodes.not_found')));
        } catch(Exception $exception) {
            \Log::error($exception);
            DB::rollback();
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function destroy(Role $role)
    {
        try {
            $roleInfo = Role::select('*');
            
            $roleInfo = $roleInfo->find($role->id);
            if($roleInfo)
            {
                $roleInfo->delete();
                return response()->json(prepareResult(false, [], trans('translate.deleted')), config('httpcodes.success'));
            }
            return response()->json(prepareResult(true, [],'No Role found', config('httpcodes.not_found')));
            
        } catch(Exception $exception) {
            return response()->json(prepareResult(true, $exception->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
            
        }
    }
}
