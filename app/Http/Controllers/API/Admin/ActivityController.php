<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Validator;
use Auth;
use Exception;
use DB;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:logs-browse');
    }

    public function activities(Request $request)
    {
        try {
            $query = Activity::select('activity_log.*','users.name')->orderBy('activity_log.created_at', 'DESC')
                ->join('users', function($join){
                    $join->on('activity_log.causer_id', '=', 'users.id');
                })
                ->with('causer:id,name');
            if(!empty($request->search))
            {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('properties', 'LIKE', '%'.$search.'%')
                        ->orWhere('log_name', 'LIKE', '%'.$search.'%');
                });
            }

            if(!empty($request->properties))
            {
                $properties = $request->properties;
                $query->where(function($q) use ($properties) {
                    $q->where('properties', 'LIKE', '%'.$properties.'%')
                        ->orWhere('log_name', 'LIKE', '%'.$properties.'%');
                });
            }

            if(!empty($request->user_id))
            {
                $query->where('causer_id', $request->user_id);
            }

            if(!empty($request->date_from))
            {
                $query->whereDate('activity_log.created_at', '>=', $request->date_from);
            }

            if(!empty($request->date_to))
            {
                $query->whereDate('activity_log.created_at', '<=', $request->date_to);
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
            return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function activityInfo($activity_id)
    {
        try {
            $query = Activity::select('activity_log.*','users.name')
                ->join('users', function($join){
                    $join->on('activity_log.causer_id', '=', 'users.id');
                })
                ->find($activity_id);
            if($query)
            {
                return response(prepareResult(false, $query, trans('translate.fetched_records')), config('httpcodes.success'));
            }
            return response(prepareResult(true, [], trans('translate.record_not_found')), config('httpcodes.not_found'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
