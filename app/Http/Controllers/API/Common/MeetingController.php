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
            $user = getUser();
            if($user->role_id == 2){
                $attendee = Attendee::where('user_id',$user->id)->pluck('meeting_id')->toArray();
                $query = Meeting::where('id',$attendee)->orderby('id','DESC')->with('Attendees','Douments');
            } else{
                $query = Meeting::orderby('id','DESC')->with('Attendees','Douments');
            }
        
            if(!empty($request->meeting_title))
            {
                $query->where('meeting_title', 'LIKE', '%'.$request->meeting_title.'%');
            }
            if(!empty($request->search_keyword))
            {
                $query->where('meeting_title', 'LIKE', '%'.$request->search_keyword.'%')->orWhere('agenda_of_meeting', 'LIKE', '%'.$request->search_keyword.'%');
            }
            if(!empty($request->start_date) && !empty($request->end_date))
            {
                $query->whereDate('metting_date', '>=', $request->start_date)->whereDate('metting_date', '<=', $request->end_date);
            }
            elseif(!empty($request->start_date) && empty($request->end_date))
            {
                $query->whereDate('metting_date', ">=" ,$request->start_date);
            }
            elseif(empty($request->start_date) && !empty($request->end_date))
            {
                $query->whereDate('metting_date', '<=', $request->end_date);
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
            'metting_date'   => 'required',
            'metting_time'   => 'required',
            "attendees"    => "required|array|min:1",
            "attendees.*"  => "required|distinct|min:1",
            'attendees.*.email' => 'required|email'

        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), trans('translate.validation_failed')), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $meeting = new Meeting;
            $meeting->meetRandomId = generateRandomNumber(14);
            $meeting->meeting_title = $request->meeting_title;
            $meeting->meeting_ref_no = $request->meeting_ref_no;
            $meeting->agenda_of_meeting  = $request->agenda_of_meeting;
            $meeting->metting_date = $request->metting_date;
            $meeting->metting_time = $request->metting_time;
            $meeting->is_repeat = ($request->is_repeat== true) ? 1:0;
            $meeting->save();

            /*------------Attendees---------------------*/
            if(is_array(@$request->attendees) && count(@$request->attendees) >0 ){
                foreach ($request->attendees as $key => $attendee) {
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
                            "metting_date" => $request->metting_date,
                            "metting_time" => $request->metting_time,
                            "agenda_of_meeting" => $request->agenda_of_meeting,
                   
                        ];
                       
                        $recevier = Mail::to($attendee['email'])->send(new MeetingMail($content));
                    }
                }
            }
            
            /*------------Documents---------------------*/
            if(is_array(@$request->documents) && count(@$request->documents) >0 ){
                foreach ($request->documents as $key => $document) {
                    $doument = new MeetingDocument;
                    $doument->meeting_id = $meeting->id;
                    $doument->document = $document['file'];
                    $doument->save();
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
                ->with('Attendees','Douments')
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
            'metting_date'   => 'required',
            'metting_time'   => 'required',
            "attendees"    => "required|array|min:1",
            "attendees.*"  => "required|distinct|min:1",
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), trans('translate.validation_failed')), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $meeting = Meeting::where('id',$id)->first();
            if(!$meeting)
            {
                return response()->json(prepareResult(true, [],'No meeting found', config('httpcodes.not_found')));
            }
            $meeting->meeting_title = $request->meeting_title;
            $meeting->meeting_ref_no = $request->meeting_ref_no;
            $meeting->agenda_of_meeting  = $request->agenda_of_meeting;
            $meeting->metting_date = $request->metting_date;
            $meeting->metting_time = $request->metting_time;
            $meeting->is_repeat = ($request->is_repeat== true) ? 1:0;
            $meeting->save();
            /*------------Attendees---------------------*/
             if(is_array(@$request->attendees) && count(@$request->attendees) >0 ){
                $deleteOldAtt = Attendee::where('meeting_id',$meeting->id)->delete();
                foreach ($request->attendees as $key => $attendee) {
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
            if(is_array(@$request->documents) && count(@$request->documents) >0 ){
                $deleteOldDoc = MeetingDocument::where('meeting_id',$meeting->id)->delete();
                foreach ($request->documents as $key => $document) {
                    $doument = new MeetingDocument;
                    $doument->meeting_id = $meeting->id;
                    $doument->document = $document['file'];
                    $doument->save();
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
}
