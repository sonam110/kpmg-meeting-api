<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MeetingNote;
use Validator;
use Auth;
use Exception;
use DB;
class NotesController extends Controller
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
    public function notes(Request $request)
    {
        try {
            $query = MeetingNote::orderby('id','DESC')->with('meeting','documents');
            
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
            'meeting_id'      => 'required|exists:meetings,id',
            'duration'   => 'required',
            'notes'   => 'required',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), trans('translate.validation_failed')), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $meetingNote = new MeetingNote;
            $meetingNote->meeting_id = $request->meeting_id;
            $meetingNote->duration = $request->duration;
            $meetingNote->notes  = $request->notes;
            $meetingNote->decision = $request->decision;
            $meetingNote->created_by = auth()->user()->id;
            $meetingNote->save();
            
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
                ->with('meeting','documents')
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
            'meeting_id'      => 'required|exists:meetings,id',
            'duration'   => 'required',
            'notes'   => 'required',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), trans('translate.validation_failed')), config('httpcodes.bad_request'));
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
            $meetingNote->save();
            
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
}
