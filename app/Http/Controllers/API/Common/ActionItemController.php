<?php

namespace App\Http\Controllers\API\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ActionItem;
use App\Models\ActionSubItem;
use Validator;
use Auth;
use Exception;
use DB;
class ActionItemController extends Controller
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
    public function actionItems(Request $request)
    {
        try {
            $user = getUser();

            if($user->role_id == 1){
                $query = ActionItem::where('owner_id',$user->id)->orderby('id','DESC')->with('meeting');
            } else{
                $query = ActionItem::orderby('id','DESC')->with('meeting');
            }
            if(!empty($request->meeting_id))
            {
                $query->where('meeting_id', $request->meeting_id);
            }
            if(!empty($request->owner_id))
            {
                $query->where('owner_id', $request->owner_id);
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
            'task'   => 'required',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), trans('translate.validation_failed')), config('httpcodes.bad_request'));
        }

        DB::beginTransaction();
        try {
            $actionItem = new ActionItem;
            $actionItem->meeting_id = $request->meeting_id;
            $actionItem->note_id = $request->note_id;
            $actionItem->owner_id  = $request->owner_id;
            $actionItem->mm_ref_id =  generateRandomNumber(14);
            $actionItem->date_opened = $request->date_opened;
            $actionItem->task  = $request->task;
            $actionItem->priority  = $request->priority;
            $actionItem->due_date =  $request->due_date;
            $actionItem->complete_percentage =  $request->complete_percentage;
            $actionItem->status =  $request->status;
            $actionItem->image =  $request->image;
            $actionItem->complete_date =  $request->complete_date;
            $actionItem->comment =  $request->comment;
            $actionItem->status =  (!empty($request->status)) ? $request->status : 0;
            $actionItem->save();
            
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
            'task'   => 'required',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), trans('translate.validation_failed')), config('httpcodes.bad_request'));
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
            $actionItem->status =  $request->status;
            $actionItem->image =  $request->image;
            $actionItem->complete_date =  $request->complete_date;
            $actionItem->comment =  $request->comment;
            $actionItem->status =  (!empty($request->status)) ? $request->status : 0;
            $actionItem->save();
            
            DB::commit();
            return response()->json(prepareResult(false, $actionItem, trans('translate.updated')),config('httpcodes.success'));
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
        //
    }

    public function action(Request $request)
    {
        $validation = \Validator::make($request->all(), [
            'ids'      => 'required',
            'action'      => 'required',
        ]);

        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), trans('translate.validation_failed')), config('httpcodes.bad_request'));
        }
        DB::beginTransaction();
        try 
        {
            $ids = $request->ids;
            if($request->action == 'in-process')
            {
                ActionItem::whereIn('id',$ids)->update(['status'=>"1"]);
                $message = trans('translate.in_process');
            }
            elseif($request->action == 'completed')
            {
                ActionItem::whereIn('id',$ids)->update(['status'=>"2"]);
                $message = trans('translate.completed');
            }
            elseif($request->action == 'on-hold')
            {
                ActionItem::whereIn('id',$ids)->update(['status'=>"3"]);
                $message = trans('translate.on_hold');
            }
            elseif($request->action == 'cancelled')
            {
                ActionItem::whereIn('id',$ids)->update(['status'=>"4"]);
                $message = trans('translate.cancelled');
            }
            elseif($request->action == 'pending')
            {
                ActionItem::whereIn('id',$ids)->update(['status'=>"0"]);
                $message = trans('translate.pending');
            }
            $actionItems = ActionItem::whereIn('id',$ids)->get();
            DB::commit();
            return response()->json(prepareResult(false, $actionItems, $message), config('httpcodes.success'));
        }
        catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }
}
