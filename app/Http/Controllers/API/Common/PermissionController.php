<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\PermissionExtend;
use DB;

class PermissionController extends Controller
{
    protected $intime;
    public function __construct()
    {
        $this->intime = \Carbon\Carbon::now();
    }
    public function permissions(Request $request)
    {
        try 
        {
            $query = Permission::select('*');
            if(auth()->user()->role_id!='1')
            {
                $query->whereIn('belongs_to', [2,3]);
            }
            if(!empty($request->belongs_to))
            {
                $query->where('belongs_to',$request->belongs_to);
            }
            
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
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function store(Request $request)
    {
        $validation = \Validator::make($request->all(),[
            'name'      => 'required|unique:permissions,name',
            'se_name'   => 'required|unique:permissions,se_name',
            'group_name'=> 'required'
        ]);
        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $permission = new Permission;
            $permission->group_name  = $request->group_name;
            $permission->guard_name    = 'api';
            $permission->name = $request->name;
            $permission->se_name  = $request->se_name;
            $permission->belongs_to  = empty($request->belongs_to) ? 1 : $request->belongs_to;
            $permission->save();

            if($request->belongs_to == '3')
            {
            	$roleUsers = DB::table('model_has_roles')->get();
            }
            else
            {
            	$roleUsers = DB::table('model_has_roles')->where('role_id',$request->belongs_to)->get();
            }
            DB::commit();
            return response()->json(prepareResult(false, $permission, trans('translate.created')),config('httpcodes.created'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function show(Permission $permission)
    {
        try 
        {
            if($permission)
            {
                if(auth()->user()->role_id != 1 && $permission->belongs_to == 1)
                {
                    return response(prepareResult(true, [], trans('translate.record_not_found')), config('httpcodes.not_found'));
                }
                return response(prepareResult(false, $permission, trans('translate.fetched_detail')), config('httpcodes.success'));
            }
            return response(prepareResult(true, [], trans('translate.record_not_found')), config('httpcodes.not_found'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function update(Request $request, Permission $permission)
    {
        $validation = \Validator::make($request->all(),[
            'name'      => 'required|unique:permissions,name,'.$permission->id,
            'se_name'   => 'required|unique:permissions,se_name,'.$permission->id,
            'group_name'=> 'required'
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {

            if(auth()->user()->role_id != 1 && $permission->belongs_to == 1)
            {
                return response(prepareResult(true, [], trans('translate.record_not_found')), config('httpcodes.not_found'));
            }
            $permission->group_name  = $request->group_name;
            $permission->name = $request->name;
            $permission->se_name  = $request->se_name;
            $permission->belongs_to  = empty($request->belongs_to) ? 1 : $request->belongs_to;
            $permission->save();
            DB::commit();
            return response()->json(prepareResult(false, $permission, trans('translate.updated')),config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function destroy(Permission $permission)
    {
        //Temporary enabled, after deployment removed this function
        try {
            
            if(auth()->user()->role_id != 1 && $permission->belongs_to == 1)
            {
                return response(prepareResult(true, [], trans('translate.record_not_found')), config('httpcodes.not_found'));
            }
            $permission->delete();
            return response()->json(prepareResult(false, [], trans('translate.deleted')), config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
