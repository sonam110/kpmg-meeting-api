<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MeetingDocument;
use App\Models\ActionItem;
use App\Models\ActionSubItem;
use App\Models\Notification;
use Validator;
use Auth;
use Exception;
use DB;
use App\Mail\ManualMail;
use App\Models\ManualMailLog;
use Mail;
class ActionItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:action-items-browse',['only' => ['actionItems']]);
        $this->middleware('permission:action-items-add', ['only' => ['store']]);
        $this->middleware('permission:action-items-edit', ['only' => ['update','action']]);
        $this->middleware('permission:action-items-read', ['only' => ['show']]);
        $this->middleware('permission:action-items-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function actionItems(Request $request)
    {
        try {
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

            if($user->role_id == 1){
                $query = ActionItem::orderby($column,$dir)->with('meeting','documents');
            } else{
                $query = ActionItem::where('owner_id',$user->id)->orderby('id','DESC')->with('meeting','documents');
            }
            if(!empty($request->meeting_id))
            {
                $query->where('meeting_id', $request->meeting_id);
            }
            if(!empty($request->owner_id))
            {
                $query->where('owner_id', $request->owner_id);
            }
            if(!empty($request->task))
            {
                $query->where('task', 'LIKE', '%'.$request->task.'%');
            }
            if(!empty($request->comment))
            {
                $query->where('comment', 'LIKE', '%'.$request->comment.'%');
            }
            if(!empty($request->mm_ref_id))
            {
                $query->where('mm_ref_id', 'LIKE', '%'.$request->mm_ref_id.'%');
            }
            if(!empty($request->due_date))
            {
                $query->where('due_date', $request->due_date);
            }
            if(!empty($request->priority))
            {
                $query->where('priority', $request->priority);
            }
            if(!empty($request->status))
            {
                $query->where('status', $request->status);
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
            'meeting_id'      => 'required|exists:meetings,id',
            'task'   => 'required|regex:/^[a-zA-Z0-9-_ @#]+$/',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $actionItem = new ActionItem;
            $actionItem->meeting_id = $request->meeting_id;
            $actionItem->note_id = $request->note_id;
            $actionItem->owner_id  = $request->owner_id;
            $actionItem->mm_ref_id =  generateRandomString(14);
            $actionItem->date_opened = $request->date_opened;
            $actionItem->task  = $request->task;
            $actionItem->priority  = $request->priority;
            $actionItem->due_date =  $request->due_date;
            $actionItem->complete_percentage =  $request->complete_percentage;
            $actionItem->image =  $request->image;
            $actionItem->complete_date =  $request->complete_date;
            $actionItem->comment =  $request->comment;
            $actionItem->created_by =  auth()->id();
            $actionItem->status =  (!empty($request->status)) ? $request->status : 'pending';
            $actionItem->save();

             /*------------Documents---------------------*/
            $documents = $request->documents;
            if(is_array(@$documents) && count(@$documents) >0 ){
                foreach ($documents as $key => $document) {
                    $doc = new MeetingDocument;
                    $doc->action_id = $actionItem->id;
                    $doc->type = 'action';
                    $doc->document = $document['file'];
                    $doc->file_extension = $document['file_extension'];
                    $doc->file_name = $document['file_name'];
                    $doc->uploading_file_name = $document['uploading_file_name'];
                    $doc->save();
                }

            }

            //---Notify User For Task Assigned----//
            $notification = new Notification;
            $notification->user_id              = $request->owner_id;
            $notification->sender_id            = auth()->id();
            $notification->type                 = 'action';
            $notification->status_code          = 'success';
            $notification->title                = 'A new task has been assigned to you';
            $notification->message              = 'Hello '.@$actionItem->owner->name.', a new task has been assigned to you. Please check Action Menu to view the details';
            $notification->data_id              = $actionItem->id;
            $notification->read_status          = false;
            $notification->save();
            
            DB::commit();
            return response()->json(prepareResult(false, $actionItem, trans('translate.created')),config('httpcodes.created'));
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
            $actionItem = ActionItem::select('*')
                ->with('meeting','owner')
                ->find($id);
            if($actionItem)
            {
                return response(prepareResult(false, $actionItem, trans('translate.fetched_records')), config('httpcodes.success'));
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
            'meeting_id'      => 'required|exists:meetings,id',
            'task'   => 'required|regex:/^[a-zA-Z0-9-_ @#]+$/',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }
        $actionItem = ActionItem::where('id',$id)->first();
        if(!$actionItem)
        {
            return response()->json(prepareResult(true, [],'No Action item found', config('httpcodes.not_found')));
        }

        DB::beginTransaction();
        try {
            $actionItem->meeting_id = $request->meeting_id;
            $actionItem->note_id = $request->note_id;
            $actionItem->owner_id  = $request->owner_id;
            $actionItem->date_opened = $request->date_opened;
            $actionItem->task  = $request->task;
            $actionItem->priority  = $request->priority;
            $actionItem->due_date =  $request->due_date;
            $actionItem->complete_percentage =  $request->complete_percentage;
            $actionItem->image =  $request->image;
            $actionItem->complete_date =  $request->complete_date;
            $actionItem->comment =  $request->comment;
            $actionItem->created_by =  auth()->id();
            $actionItem->status =  (!empty($request->status)) ? $request->status : 'pending';
            $actionItem->save();

             /*------------Documents---------------------*/
            $documents = $request->documents;
            $deleteOldDoc = MeetingDocument::where('action_id',$actionItem->id)->where('type','action')->delete();
            if(is_array(@$documents) && count(@$documents) >0 ){
                foreach ($documents as $key => $document) {
                    $doc = new MeetingDocument;
                    $doc->action_id = $actionItem->id;
                    $doc->type = 'action';
                    $doc->document = $document['file'];
                    $doc->file_extension = $document['file_extension'];
                    $doc->file_name = $document['file_name'];
                    $doc->uploading_file_name = $document['uploading_file_name'];
                    $doc->save();
                }

            }
            
            DB::commit();
            return response()->json(prepareResult(false, $actionItem, trans('translate.updated')),config('httpcodes.success'));
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
            if($request->percent == 100)
            {
                $action = 'completed';
            }
            else
            {
                $action = $request->action;
            }

            
            if($action == 'in_progress')
            {
                ActionItem::whereIn('id',$ids)->update(['status'=>"in_progress"]);
                $message = trans('translate.in_process');
            }
            elseif($action == 'completed')
            {
                ActionItem::whereIn('id',$ids)->update(['status'=>"completed","complete_date"=>date("Y-m-d"),"complete_percentage"=>"100"]);
                $message = trans('translate.completed');
            }
            elseif($action == 'on_hold')
            {
                ActionItem::whereIn('id',$ids)->update(['status'=>"on_hold"]);
                $message = trans('translate.on_hold');
            }
            elseif($action == 'cancelled')
            {
                ActionItem::whereIn('id',$ids)->update(['status'=>"cancelled"]);
                $message = trans('translate.cancelled');
            }
            elseif($action == 'pending')
            {
                ActionItem::whereIn('id',$ids)->update(['status'=>"pending"]);
                $message = trans('translate.pending');
            }
            elseif($action == 'percentage')
            {
                if($request->percent == 100)
                {
                    ActionItem::whereIn('id',$ids)->update(['status'=>"completed","complete_date"=>date("Y-m-d"),"complete_percentage"=>"100"]);
                }
                else
                {
                    ActionItem::whereIn('id',$ids)->update(['complete_percentage'=>$request->percent]);
                }
                $message = trans('translate.percentage');
            }
            elseif($action == 'verified')
            {
                if(auth()->user()->role_id==1)
                {
                    ActionItem::whereIn('id',$ids)
                    ->where('status', 'completed')
                    ->update([
                        'status' => 'verified',
                        'verified_by' => auth()->id(),
                        'verified_date' => date('Y-m-d'),
                    ]);
                }
                else
                {
                    ActionItem::whereIn('id',$ids)
                    ->where('owner_id', auth()->id())
                    ->where('status', 'completed')
                    ->update([
                        'status' => 'verified',
                        'verified_by' => auth()->id(),
                        'verified_date' => date('Y-m-d'),
                    ]);
                }
                $message = trans('translate.verified');
            }
            else
            {
                return response()->json(prepareResult(true, [], 'Provide a valid Action'), config('httpcodes.success'));
            }

            $actionItems = ActionItem::whereIn('id',$ids)->get();
            foreach ($actionItems as $key => $value) {
                $notification = new Notification;
                $notification->user_id              = $value->owner_id;
                $notification->sender_id            = auth()->id();
                $notification->type                 = 'action';
                $notification->status_code          = 'success';
                if($action == 'percentage')
                {
                    $notification->title                = 'Action Percentage  Updated';
                    $notification->message              = 'Your action has been marked as '.$request->percent.'% completed. Please check Action Menu to find more details';
                }
                else
                {
                    $notification->title                = 'Action Status Updated';
                    $notification->message              = 'Your action has been marked as '.$action.'. Please check Action Menu to find more details';
                }
                $notification->data_id              = $value->id;
                $notification->read_status          = false;
                $notification->save();


                $notification = new Notification;
                $notification->user_id              = $value->created_by;
                $notification->sender_id            = auth()->id();
                $notification->type                 = 'action';
                $notification->status_code          = 'success';
                if($action == 'percentage')
                {
                    $notification->title                = 'Action Percentage  Updated';
                    $notification->message              = 'Your action has been marked as '.$request->percent.'% completed. Please check Action Menu to find more details';
                }
                else
                {
                    $notification->title                = 'Action Status Updated';
                    $notification->message              = 'Your action has been marked as '.$action.'. Please check Action Menu to find more details';
                }
                $notification->data_id              = $value->id;
                $notification->read_status          = false;
                $notification->save();
            }
            DB::commit();
            return response()->json(prepareResult(false, $actionItems, $message), config('httpcodes.success'));
        }
        catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function sendMail(Request $request)
    {
        $validation = \Validator::make($request->all(), [
            'id'      => 'required',
            'subject'      => 'required',
            'body'      => 'required',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }
        DB::beginTransaction();
        try 
        {
            $subject = $request->subject;
            $body = $request->body;
            $actionItem = ActionItem::find($request->id);
            if(!$actionItem)
            {
                return response()->json(prepareResult(true, [],'No Action item found', config('httpcodes.not_found')));
            }
            $meeting = $actionItem->meeting;
            $attendees = $meeting->attendees;
            foreach ($attendees as $key => $attendee) {
                if(!empty($attendee->user) && !empty(@$attendee->user->email))
                {
                    // if (env('IS_MAIL_ENABLE', false) == true) {
                        $content = [
                            "name" =>$attendee->user->name,
                            "subject" => $subject,
                            "body" => $body,
                        ];
                        $recevier = Mail::to($attendee->user->email)->send(new ManualMail($content));
                        if($recevier == true)
                        {
                            $manualMailLog = new ManualMailLog;
                            $manualMailLog->action_item_id = $request->id;
                            $manualMailLog->user_id = $attendee->user_id;
                            $manualMailLog->subject = $subject;
                            $manualMailLog->body = $body;
                            $manualMailLog->save();
                        }
                    // }
                }
            }
            DB::commit();
            return response()->json(prepareResult(false, $actionItem, trans('translate.mail_sent')), config('httpcodes.success'));
        }
        catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    public function mailLogs(Request $request)
    {
        try {
            $user = getUser();

            $query = ManualMailLog::orderby('id','DESC');
            if(!empty($request->action_item_id))
            {
                $query->where('action_item_id', $request->action_item_id);
            }
            if(!empty($request->subject))
            {
                $query->where('subject', 'LIKE', '%'.$request->subject.'%');
            }
            if(!empty($request->body))
            {
                $query->where('body', 'LIKE', '%'.$request->body.'%');
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
}
