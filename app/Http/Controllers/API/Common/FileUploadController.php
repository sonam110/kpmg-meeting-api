<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Image;
class FileUploadController extends Controller
{
   

    public function store(Request $request)
    {
        if($request->is_multiple==1)
        {
            $validation = \Validator::make($request->all(),[ 
                'file'     => 'required|array|max:20000|min:1'
            ]);
        }
        else
        {
            $validation = \Validator::make($request->all(),[ 
                'file'     => 'required|max:10000',
            ]);
        }
        if ($validation->fails()) {
            return response(prepareResult(true, $validation->messages(), trans('translate.validation_failed')), config('httpcodes.bad_request'));
        }
        try
        {
            $file = $request->file;
            $destinationPath = 'uploads/';
            $fileArray = array();
            $formatCheck = ['doc','docx','png','jpeg','jpg','pdf','svg','mp4','tif','tiff','bmp','gif','eps','raw','jfif','webp','pem','csv'];

            if($request->is_multiple==1)
            {
                foreach ($file as $key => $value) 
                {
                    $extension = strtolower($value->getClientOriginalExtension());
                    if(!in_array($extension, $formatCheck))
                    {
                        return response()->json(prepareResult(true, [], trans('translate.file_not_allowed').'Only allowed : doc,docx,png,jpeg,jpg,pdf,svg,mp4,tif,tiff,bmp,gif,eps,raw,jfif,webp,pem,csv'), config('httpcodes.internal_server_error'));
                    }

                    $fileName   = time().'-'.rand(0,99999).'.' . $value->getClientOriginalExtension();
                    $extension = $value->getClientOriginalExtension();
                    $fileSize = $value->getSize();

                    if($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png')
                    {
                        //Thumb image generate
                        $imgthumb = Image::make($value->getRealPath());
                        $imgthumb->resize(100, null, function ($constraint) {
                            $constraint->aspectRatio();
                        });
                        $imgthumb->save($destinationPath.$fileName);
                    }
                    else
                    {
                        $value->move($destinationPath, $fileName);
                    }

                    
                    
                    $fileArray[] = [
                        'file_name'         => env('CDN_DOC_URL').$destinationPath.$fileName,
                        'file_extension'    => $value->getClientOriginalExtension(),
                        'uploading_file_name' => $value->getClientOriginalName(),
                    ];
                }

                return response()->json(prepareResult(false, $fileArray, trans('translate.created')),config('httpcodes.created'));
            }
            else
            {
                $fileName   = time().'-'.rand(0,99999).'.' . $file->getClientOriginalExtension();
                $extension = strtolower($file->getClientOriginalExtension());
                $fileSize = $file->getSize();
                if(!in_array($extension, $formatCheck))
                {
                    return response()->json(prepareResult(true, [], trans('translate.file_not_allowed').'Only allowed : doc,docx,png,jpeg,jpg,pdf,svg,mp4,tif,tiff,bmp,gif,eps,raw,jfif,webp,pem,csv'), config('httpcodes.internal_server_error'));
                }

                if($extension == 'jpg' || $extension == 'jpeg' || $extension == 'png')
                {
                    //Thumb image generate
                    $imgthumb = Image::make($file->getRealPath());
                    $imgthumb->resize(100, null, function ($constraint) {
                        $constraint->aspectRatio();
                    });
                    $imgthumb->save($destinationPath.$fileName);
                }
                else
                {
                    $file->move($destinationPath, $fileName);
                }

                
                $fileInfo = [
                    'file_name'         => env('CDN_DOC_URL').$destinationPath.$fileName,
                    'file_extension'    => $file->getClientOriginalExtension(),
                    'uploading_file_name' => $file->getClientOriginalName(),
                ];
                return response()->json(prepareResult(false, $fileInfo, trans('translate.created')),config('httpcodes.created'));
            }   
        }
        catch (\Throwable $e) {
            \Log::error($e);
            return response()->json(prepareResult(true, $e->getMessage(), trans('translate.something_went_wrong')), config('httpcodes.internal_server_error'));
        }
    }

    
}
