<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;
use Auth;
use DB;
use App\Models\ActionItem;
use App\Models\MeetingMailLog;
use App\Models\Meeting;
use App\Models\MeetingDocument;
use App\Models\Attendee;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Mail\TaskReminderMail;
use Mail;
use App\Models\ActionItemController;
class DashboardController extends Controller
{
    //DashBoard--Report-Data
    public function dashboard()
    {
        try {
            $user = getUser();
            $data = [];
            if($user->role_id == 1)
            {
                $data['userCount'] = User::where('role_id','!=','1')->count();
                $data['meetingCount'] = Meeting::count();
                $data['todayMeetingCount'] = Meeting::whereDate('meeting_date',date('Y-m-d'))->count();
            }
            elseif($user->role_id == 2)
            {
                
            }
           
            return prepareResult(true,'Dashboard' ,$data, config('httpcodes.success'));    
        } catch(Exception $exception) {
                return prepareResult(false, $exception->getMessage(),$exception->getMessage(), config('httpcodes.internal_server_error'));   
        }  
    }
}
