<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Meeting;
use App\Models\MeetingDocument;
use App\Models\MeetingLog;
use App\Models\Attendee;
use App\Models\MasterUser;
use App\Models\Module;
use App\Models\AssigneModule;
use Illuminate\Support\Facades\Hash;
use App\Models\Notification;
use App\Models\User;
use Validator;
use Auth;
use Exception;
use DB;
use Mail;
use App\Mail\MeetingMail;
use App\Mail\WelcomeMail;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
class MeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }
    public function meetings(Request $request)
    {
        try {
            // return auth()->id();
            $column = 'id';
            $dir = 'Desc';
            if(!empty($request->sort))
            {
                if(!empty($request->sort['column']))
                {
                    $column = $request->sort['column'];
                }
                if(!empty($request->sort['dir']))
                {
                    $dir = $request->sort['dir'];
                }
            }
            $user = getUser();
            $query = Meeting::select('meetings.*')->orderby('meetings.'.$column,$dir)
            ->with('attendees.user:id,name,email','documents','organiser:id,name,email');
            if($user->role_id != '1'){
                $attendees = Attendee::where('user_id',$user->id)
                ->pluck('meeting_id')
                ->toArray();
                $query = $query->whereIn('meetings.id',$attendees);
            }
        
            if(!empty($request->meeting_title))
            {
                $query->where('meetings.meeting_title', 'LIKE', '%'.$request->meeting_title.'%');
            }
            if(!empty($request->meeting_ref_no))
            {
                $query->where('meetings.meeting_ref_no', 'LIKE', '%'.$request->meeting_ref_no.'%');
            }
            if(!empty($request->meeting_date))
            {
                $query->where('meetings.meeting_date', $request->meeting_date);
            }
            if(!empty($request->status))
            {
                $query->where('meetings.status', $request->status);
            }
            if(!empty($request->meeting_time_start))
            {
                $query->where('meetings.meeting_time_start', $request->meeting_time_start);
            }
            if(!empty($request->meeting_time_end))
            {
                $query->where('meetings.meeting_time_end', $request->meeting_time_end);
            }
            if(!empty($request->search_keyword))
            {
                $query->where('meetings.meeting_title', 'LIKE', '%'.$request->search_keyword.'%')->orWhere('meetings.agenda_of_meeting', 'LIKE', '%'.$request->search_keyword.'%');
            }
            if(!empty($request->start_date) && !empty($request->end_date))
            {
                $query->whereDate('meetings.meeting_date', '>=', $request->start_date)->whereDate('meetings.meeting_date', '<=', $request->end_date);
            }
            elseif(!empty($request->start_date) && empty($request->end_date))
            {
                $query->whereDate('meetings.meeting_date', ">=" ,$request->start_date);
            }
            elseif(empty($request->start_date) && !empty($request->end_date))
            {
                $query->whereDate('meetings.meeting_date', '<=', $request->end_date);
            }
            if(!empty($request->attendee_id))
            {
                $query->join('attendees', function($join) use ($request) {
                    $join->on('attendees.meeting_id', '=', 'meetings.id')
                    ->where('attendees.user_id',$request->attendee_id);
                });
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
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
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
            'meeting_title'      => 'required',
            // 'meeting_ref_no'   => 'required|unique:meetings',
            'meeting_date'   => 'required',
            'meeting_time_start'   => 'required',
            'meeting_time_end'   => 'required',
            "attendees"    => "required|array|min:1",
            "attendees.*"  => "required|distinct|min:1",
            'attendees.*.email' => 'required|email'

        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $meeting = new Meeting;
            $meeting->meetRandomId = generateRandomNumber(14);
            $meeting->meeting_title = $request->meeting_title;
            $meeting->meeting_ref_no = generateRandomNumber(14);
            $meeting->agenda_of_meeting  = $request->agenda_of_meeting;
            $meeting->meeting_date = $request->meeting_date;
            $meeting->meeting_time_start = $request->meeting_time_start;
            $meeting->meeting_time_end = $request->meeting_time_end;
            $meeting->meeting_link = $request->meeting_link;
            $meeting->organised_by = auth()->id();
            $meeting->is_repeat = ($request->is_repeat== true) ? 1:0;
            $meeting->status = $request->status ? $request->status : 1;
            $meeting->save();
            /*------------Attendees---------------------*/
            $attendees = $request->attendees;
            if(is_array(@$attendees) && count(@$attendees) >0 ){
                foreach ($attendees as $key => $attendee) {
                    $checkUser = User::where('email',$attendee['email'])->first();
                    /*---------Add User---------------------*/
                    if(empty($checkUser)){
                        $userInfo = $this->addUser($attendee['email']);
                        $user_id = $userInfo->id;
                        $name = $userInfo->name;
                    } else{
                        $user_id = $checkUser->id;
                        $name = $checkUser->name;
                    }

                    $attende = new Attendee;
                    $attende->meeting_id = $meeting->id;
                    $attende->user_id = $user_id;
                    $attende->save();
                    

                    if (env('IS_MAIL_ENABLE', false) == true) {
                        $content = [
                            "name" =>$name,
                            "meeting_title" => $request->meeting_title,
                            "meeting_date" => $request->meeting_date,
                            "meeting_time_start" => $request->meeting_time_start,
                            "meeting_time_end" => $request->meeting_time_end,
                            "agenda_of_meeting" => $request->agenda_of_meeting,
                   
                        ];
                        $recevier = Mail::to($attendee['email'])->send(new MeetingMail($content));
                    }

                    $notification = new Notification;
                    $notification->user_id              = $user_id;
                    $notification->sender_id            = auth()->id();
                    $notification->type                 = 'meeting';
                    $notification->status_code          = 'success';
                    $notification->title                = 'New Meeting Invitation';
                    $notification->message              = 'New Meting Invitation for Meeting '.$meeting->meeting_title.' which will be held on '.$meeting->meeting_date.' between '.$meeting->meeting_time_start.'-'.$meeting->meeting_time_end.'.';
                    $notification->read_status          = false;
                    $notification->data_id              = $meeting->id;
                    $notification->save();
                }
            }

            
            /*------------Documents---------------------*/
            $documents = $request->documents;
            if(is_array(@$documents) && count(@$documents) >0 ){
                foreach ($documents as $key => $document) {
                    $doc = new MeetingDocument;
                    $doc->meeting_id = $meeting->id;
                    $doc->type = 'meeting';
                    $doc->document = $document['file'];
                    $doc->file_extension = $document['file_extension'];
                    $doc->file_name = $document['file_name'];
                    $doc->uploading_file_name = $document['uploading_file_name'];
                    $doc->save();
                }

            }

            DB::commit();
            return response()->json(prepareResult(false, $meeting, trans('translate.created')),config('httpcodes.created'));
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
    public function addUser($email)
    {
        $randomNo = generateRandomNumber(10);
        $password = Hash::make($randomNo);
        $masterUser = new MasterUser;
        $masterUser->name = $email;
        $masterUser->email  = $email;
        $masterUser->password = $password;
        $masterUser->save();

        $user = new User;
        $user->id = $masterUser->id;
        $user->role_id = '2';
        $user->name = $email;
        $user->email  = $email;
        $user->password = $password;
        $user->created_by = auth()->user()->id;
        $user->save();

        //Delete if entry exists
        DB::table('password_resets')->where('email', $email)->delete();

        $token = \Str::random(64);
        DB::table('password_resets')->insert([
          'email' => $email, 
          'token' => $token, 
          'created_at' => Carbon::now()
        ]);

        $baseRedirURL = env('APP_URL');
        $content = [
            "name" => $user->name,
            "email" => $user->email,
            "password" => $randomNo,
            "passowrd_link" => $baseRedirURL.'/authentication/reset-password/'.$token
        ];

        if (env('IS_MAIL_ENABLE', false) == true) {
           
            $recevier = Mail::to($email)->send(new WelcomeMail($content));
        }

        /*-------Assigne Meeting module for this user*/
        $assigneModule = new AssigneModule;
        $assigneModule->module_id  = '1';
        $assigneModule->user_id  = $masterUser->id;
        $assigneModule->save();

    
        //Role and permission sync
        $role = Role::where('id','2')->first();
        $permissions = $role->permissions->pluck('name');
        
        $user->assignRole($role->name);
        foreach ($permissions as $key => $permission) {
            $user->givePermissionTo($permission);
        }
        return $user;

    }
    public function show($id)
    {
        try {
            $meeting = Meeting::select('*')
                ->with('attendees.user:id,name,email','documents','notes','organiser:id,name,email')
                ->find($id);
            if($meeting)
            {
                return response(prepareResult(false, $meeting, trans('translate.fetched_records')), config('httpcodes.success'));
            }
            return response(prepareResult(true, [], trans('translate.record_not_found')), config('httpcodes.not_found'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
            'meeting_title'      => 'required',
            // 'meeting_ref_no'   => 'required|unique:meetings,meeting_ref_no,'.$id,
            'meeting_date'   => 'required',
            'meeting_time_start'   => 'required',
            'meeting_time_end'   => 'required',
            "attendees"    => "required|array|min:1",
            "attendees.*"  => "required|distinct|min:1",
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $meeting = Meeting::where('id',$id)->first();
            if(!$meeting)
            {
                return response()->json(prepareResult(true, [],'No meeting found', config('httpcodes.not_found')));
            }
            $meeting->meeting_title = $request->meeting_title;
            // $meeting->meeting_ref_no = $request->meeting_ref_no;
            $meeting->agenda_of_meeting  = $request->agenda_of_meeting;
            $meeting->meeting_date = $request->meeting_date;
            $meeting->meeting_time_start = $request->meeting_time_start;
            $meeting->meeting_time_end = $request->meeting_time_end;
            $meeting->meeting_link = $request->meeting_link;
            // $meeting->organised_by = auth()->id();
            $meeting->is_repeat = ($request->is_repeat== true) ? 1:0;
            $meeting->status = $request->status ? $request->status : 1;
            $meeting->save();
            /*------------Attendees---------------------*/
            $attendees = $request->attendees;
             if(is_array(@$attendees) && count(@$attendees) >0 ){
                $deleteOldAtt = Attendee::where('meeting_id',$meeting->id)->delete();
                foreach ($attendees as $key => $attendee) {
                    $checkUser = User::where('email',$attendee['email'])->first();
                    /*---------Add User---------------------*/
                    if(empty($checkUser)){
                        $userInfo = $this->addUser($attendee['email']);
                        $user_id = $userInfo->id;
                    } else{
                        $user_id = $checkUser->id;
                    }
                    $attende = new Attendee;
                    $attende->meeting_id = $meeting->id;
                    $attende->user_id = $user_id;
                    $attende->save();
                }
            }
             /*------------Documents---------------------*/

            $deleteOldDoc = MeetingDocument::where('meeting_id',$meeting->id)->where('type','meeting')->delete();
            $documents = $request->documents;
            if(is_array(@$documents) && count(@$documents) >0 ){
                foreach ($documents as $key => $document) {
                    $doc = new MeetingDocument;
                    $doc->meeting_id = $meeting->id;
                    $doc->type = 'meeting';
                    $doc->document = $document['file'];
                    $doc->file_extension = $document['file_extension'];
                    $doc->file_name = $document['file_name'];
                    $doc->uploading_file_name = $document['uploading_file_name'];
                    $doc->save();
                }

            }

            DB::commit();
            return response()->json(prepareResult(false, $meeting, trans('translate.updated')),config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            DB::rollback();
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $meeting = Meeting::where('id',$id)->first();
            if(!$meeting)
            {
                return response()->json(prepareResult(true, [], 'Meeting not found', config('httpcodes.not_found')));
            }
            $isDeleted = $meeting->delete();
            if($isDeleted){
                $deleteOldAtt = Attendee::where('meeting_id',$id)->delete();
                $deleteOldDoc = MeetingDocument::where('meeting_id',$id)->delete();
            }
            return response()->json(prepareResult(false, [], trans('translate.deleted')), config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function action(Request $request)
    {
        $validation = \Validator::make($request->all(), [
            'ids'      => 'required',
            'action'      => 'required',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }
        DB::beginTransaction();
        try 
        {
            $ids = $request->ids;
            if($request->action == 'delete')
            {
                $meetings = Meeting::whereIn('id',$ids)->delete();
                $deleteOldAtt = Attendee::whereIn('meeting_id',$ids)->delete();
                $deleteOldDoc = MeetingDocument::whereIn('meeting_id',$ids)->delete();
                $message = trans('translate.deleted');
            }
            elseif($request->action == 'inactive')
            {
                Meeting::whereIn('id',$ids)->update(['status'=>"2"]);
                $message = trans('translate.inactive');
            }
            elseif($request->action == 'active')
            {
                Meeting::whereIn('id',$ids)->update(['status'=>"1"]);
                $message = trans('translate.active');
            }
            $meetings = Meeting::whereIn('id',$ids)->get();
            DB::commit();
            return response()->json(prepareResult(false, $meetings, $message), config('httpcodes.success'));
        }
        catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
