<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MeetingDocument;
use App\Models\User;
use App\Models\Notification;
use App\Models\MeetingNote;
use Validator;
use Auth;
use Exception;
use DB;
class NotesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:notes-browse',['only' => ['notes']]);
        $this->middleware('permission:notes-add', ['only' => ['store']]);
        $this->middleware('permission:notes-edit', ['only' => ['update','action']]);
        $this->middleware('permission:notes-read', ['only' => ['show']]);
        $this->middleware('permission:notes-delete', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function notes(Request $request)
    {
        try {
            $query = MeetingNote::orderby('id','DESC')->with('meeting','documents','createdBy:id,name,email','editedBy:id,name,email','actionItems.owner:id,name,email');
            
            if(!empty($request->meeting_id))
            {
                $query->where('meeting_id',$request->meeting_id);
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
            'duration'   => 'required',
            'notes'   => 'required|regex:/^[a-zA-Z0-9-_ @#]+$/',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $meetingNote = new MeetingNote;
            $meetingNote->meeting_id = $request->meeting_id;
            $meetingNote->duration = $request->duration;
            $meetingNote->notes  = $request->notes;
            $meetingNote->decision = $request->decision;
            $meetingNote->status = $request->status ? $request->status : 1;
            $meetingNote->created_by = auth()->user()->id;
            $meetingNote->save();

             /*------------Documents---------------------*/
            $documents = $request->documents;
            if(is_array(@$documents) && count(@$documents) >0 ){
                foreach ($documents as $key => $document) {
                    $meeting_id = $request->meeting_id;
                    $notes_id = MeetingNote::where('meeting_id',$meeting_id)->pluck('id')->toArray();
                    $checkDoc = MeetingDocument::where('uploading_file_name',$document['uploading_file_name'])
                    ->where(function($q) use ($meeting_id,$notes_id) {
                        $q->where('meeting_id',$meeting_id)
                        ->orWhereIn('note_id', $notes_id);
                    })
                    ->count();
                    if($checkDoc > 0)
                    {
                        return response()->json(prepareResult(true, [], trans('translate.document_dublicacy')), config('httpcodes.bad_request'));
                    }
                    $doc = new MeetingDocument;
                    $doc->note_id = $meetingNote->id;
                    $doc->type = 'note';
                    $doc->document = $document['file'];
                    $doc->file_extension = $document['file_extension'];
                    $doc->file_name = $document['file_name'];
                    $doc->uploading_file_name = $document['uploading_file_name'];
                    $doc->save();
                }

            }

            //Notify Organiser About Meeting Note
            $notification = new Notification;
            $notification->user_id              = $meetingNote->meeting->organised_by ? $meetingNote->meeting->organised_by : User::first()->id;
            $notification->sender_id            = auth()->id();
            $notification->type                 = 'note';
            $notification->status_code          = 'success';
            $notification->title                = 'A New Meeting Note Has Been Created';
            $notification->message              = 'A new note on meeting '.$meetingNote->meeting->meeting_title.' has been added. Please click here for the details.';
            $notification->read_status          = false;
            $notification->data_id              = $request->meeting_id;
            $notification->save();

            $attendees = $meetingNote->meeting->attendees;
            foreach ($attendees as $key => $value) {
                $notification = new Notification;
                $notification->user_id              = $value->user_id;
                $notification->sender_id            = auth()->id();
                $notification->type                 = 'note';
                $notification->status_code          = 'success';
                $notification->title                = 'A New Meeting Note Has Been Created';
                $notification->message              = 'A new note on meeting '.$meetingNote->meeting->meeting_title.' has been added. Please click here for the details.';
                $notification->read_status          = false;
                $notification->data_id              = $request->meeting_id;
                $notification->save();
            }
            
            DB::commit();
            return response()->json(prepareResult(false, $meetingNote, trans('translate.created')),config('httpcodes.created'));
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
            $meetingNote = MeetingNote::select('*')
                ->with('meeting','documents','createdBy:id,name,email','editedBy:id,name,email','actionItems')
                ->find($id);
            if($meetingNote)
            {
                return response(prepareResult(false, $meetingNote, trans('translate.fetched_records')), config('httpcodes.success'));
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
            'duration'   => 'required',
            'notes'   => 'required|regex:/^[a-zA-Z0-9-_ @#]+$/',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), $validation->messages()->first()), config('httpcodes.bad_request'));
        }
        $meetingNote = MeetingNote::where('id',$id)->first();
        if(!$meetingNote)
        {
            return response()->json(prepareResult(true, [],'No meeting notes found', config('httpcodes.not_found')));
        }

        DB::beginTransaction();
        try {
            $meetingNote->meeting_id = $request->meeting_id;
            $meetingNote->duration = $request->duration;
            $meetingNote->notes  = $request->notes;
            $meetingNote->decision = $request->decision;
            $meetingNote->edited_by = auth()->user()->id;
            $meetingNote->edited_date = date('Y-m-d');
            $meetingNote->status = $request->status ? $request->status : 1;
            $meetingNote->save();

             /*------------Documents---------------------*/
            $documents = $request->documents;
            $deleteOldDoc = MeetingDocument::where('note_id',$meetingNote->id)->where('type','note')->delete();
            if(is_array(@$documents) && count(@$documents) >0 ){
                foreach ($documents as $key => $document) {
                    $meeting_id = $request->meeting_id;
                    $notes_id = MeetingNote::where('meeting_id',$meeting_id)->pluck('id')->toArray();
                    $checkDoc = MeetingDocument::where('uploading_file_name',$document['uploading_file_name'])
                    ->where(function($q) use ($meeting_id,$notes_id) {
                        $q->where('meeting_id',$meeting_id)
                        ->orWhereIn('note_id', $notes_id);
                    })
                    ->count();
                    if($checkDoc > 0)
                    {
                        return response()->json(prepareResult(true, [], trans('translate.document_dublicacy')), config('httpcodes.bad_request'));
                    }
                    
                    $doc = new MeetingDocument;
                    $doc->note_id = $meetingNote->id;
                    $doc->type = 'note';
                    $doc->document = $document['file'];
                    $doc->file_extension = $document['file_extension'];
                    $doc->file_name = $document['file_name'];
                    $doc->uploading_file_name = $document['uploading_file_name'];
                    $doc->save();
                }
            }
            //Notify Organizer about Meeting note
            $notification = new Notification;
            $notification->user_id              = $meetingNote->meeting->organised_by ? $meetingNote->meeting->organised_by : User::first()->id;
            $notification->sender_id            = auth()->id();
            $notification->type                 = 'note';
            $notification->status_code          = 'success';
            $notification->title                = 'A New Meeting Note Has Been Created';
            $notification->message              = 'A new note on meeting '.$meetingNote->meeting->meeting_title.' has been added. Please click here for the details.';
            $notification->read_status          = false;
            $notification->data_id              = $request->meeting_id;
            $notification->save();

            $attendees = $meetingNote->meeting->attendees;
            foreach ($attendees as $key => $value) {
                $notification = new Notification;
                $notification->user_id              = $value->user_id;
                $notification->sender_id            = auth()->id();
                $notification->type                 = 'note';
                $notification->status_code          = 'success';
                $notification->title                = 'A New Meeting Note Has Been Created';
                $notification->message              = 'A new note on meeting '.$meetingNote->meeting->meeting_title.' has been added. Please click here for the details.';
                $notification->read_status          = false;
                $notification->data_id              = $request->meeting_id;
                $notification->save();
            }
            
            DB::commit();
            return response()->json(prepareResult(false, $meetingNote, trans('translate.updated')),config('httpcodes.success'));
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
            $meetingNote = MeetingNote::where('id',$id)->first();
            if(!$meetingNote)
            {
                return response()->json(prepareResult(true, [], 'Meeting not found', config('httpcodes.not_found')));
            }
            $isDeleted = $meetingNote->delete();
            return response()->json(prepareResult(false, [], trans('translate.deleted')), config('httpcodes.success'));
        } catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    //Action Performed
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
                $meetingNotes = MeetingNote::whereIn('id',$ids)->delete();
                $message = trans('translate.deleted');
            }
            elseif($request->action == 'inactive')
            {
                MeetingNote::whereIn('id',$ids)->update(['status'=>"2"]);
                $message = trans('translate.inactive');
            }
            elseif($request->action == 'active')
            {
                MeetingNote::whereIn('id',$ids)->update(['status'=>"1"]);
                $message = trans('translate.active');
            }
            $meetingNotes = MeetingNote::whereIn('id',$ids)->get();
            DB::commit();
            return response()->json(prepareResult(false, $meetingNotes, $message), config('httpcodes.success'));
        }
        catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
