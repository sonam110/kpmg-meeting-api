<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AppSetting;
use App\Models\CustomLog;
use Validator;
use Auth;
use Exception;
use DB;
class AppSettingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function appSetting(Request $request)
    {
    	try {
    		$appSetting = AppSetting::select('*')->where('id','1')->first();
    		if($appSetting)
    		{
    			return response(prepareResult(false, $appSetting, trans('translate.fetched_records')), config('httpcodes.success'));
    		}
    		return response(prepareResult(true, [], trans('translate.record_not_found')), config('httpcodes.not_found'));
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

    public function updateSetting(Request $request)
    {
    	$validation = \Validator::make($request->all(), [
    		'app_name'      => 'required',
    		'app_logo'   => 'required',
    		'email'   => 'required',
    	]);

    	if ($validation->fails()) {
    		return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
    	}

    	DB::beginTransaction();
    	try {
    		$appSetting = AppSetting::where('id','1')->first(); 
            //create-log
    		$customLog = new CustomLog;
    		$customLog->created_by = auth()->id();
    		$customLog->type = 'app-setting';
    		$customLog->event = 'update';
    		$customLog->ip_address = $request->ip();
    		$customLog->location = json_encode(\Location::get($request->ip()));

    		if(!$appSetting)
    		{
    			$customLog->status = 'failed';
    			$customLog->failure_reason = 'No App setting found';
    			$customLog->save();
    			DB::commit();
    			return response()->json(prepareResult(true, [],'No App setting found', config('httpcodes.not_found')));
    		}

    		$customLog->status = 'success';
    		$customLog->last_record_before_edition = json_encode($appSetting);
    		$customLog->save();

    		$appSetting->app_name = $request->app_name;
    		$appSetting->description = $request->description;
    		$appSetting->app_logo  = $request->app_logo;
    		$appSetting->email = $request->email;
    		$appSetting->mobile_no = $request->mobile_no;
    		$appSetting->address = $request->address;
    		$appSetting->log_expiry_days = $request->log_expiry_days;
    		$appSetting->save();


    		DB::commit();
    		return response()->json(prepareResult(false, $appSetting, trans('translate.updated')),config('httpcodes.success'));
    	} catch (\Throwable $e) {
    		\Log::error($e);
    		DB::rollback();
    		return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
    	}
    }
    
}
