<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Str;
use DB;
use Auth;
use Log;
use Edujugon\PushNotification\PushNotification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        try
        {
            $query =  Notification::where('user_id',Auth::id())->orderBy('id','DESC');
            if($request->mark_all_as_read == 'true' || $request->mark_all_as_read == 1)
            {
                Notification::where('user_id',Auth::id())->update(['read_status' => 1]);
            }
            if($request->read_status)
            {
                $query->where('read_status',$request->read_status);
            }
            if($request->type)
            {
                $query->where('type',$request->type);
            }
            if(!empty($request->perPage))
            {
                $perPage = $request->perPage;
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
       
    }

    public function show(Notification $notification)
    {
        return response(prepareResult(false, $userinfo, trans('translate.fetched_detail')), config('httpcodes.success'));
    }

    public function destroy(Notification $notification)
    {
        try
        {
            $notification->delete();
            return response(prepareResult(false, [], trans('translate.deleted')), config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function read($id)
    {
        try
        {
            $notification = Notification::find($id);
            $notification->update(['read_status' => true]);
            return response(prepareResult(false, $notification, trans('translate.read')), config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function userNotificationReadAll()
    {
        try
        {
            Notification::where('user_id', Auth::id())->update(['read_status' => true]);
            return response(prepareResult(false, [], trans('translate.all_read')), config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function userNotificationDelete()
    {
        try
        {
            Notification::where('user_id', Auth::id())->delete();
            return response(prepareResult(false, [], trans('translate.deleted')), config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function unreadNotificationsCount()
    {
        try
        {
            
            $count = Notification::where('user_id',Auth::id())->where('read_status',0)->count();
            return response(prepareResult(false, $query, trans('translate.fetched_count')), config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function notificationCheck(Request $request)
    {
        $push = new PushNotification('fcm');
        $push->setMessage([
            "notification"=>[
                'title' => 'Testing Title',
                'body'  => 'Testing Body',
                'sound' => 'default',
                'android_channel_id' => '1',
                //'timestamp' => date('Y-m-d G:i:s')
            ],
            'data'=>[
                'id'  => 1,
                'user_type'  => 'Company',
                'module'  => 'Activity',
                'screen'  => 'home'
            ]                        
        ])
        ->setApiKey(env('FIREBASE_KEY'))
        ->setDevicesToken($request->device_token)
        ->send();

        return prepareResult(true,$push->getFeedback(), [],config('httpcodes.success'));
    }
}