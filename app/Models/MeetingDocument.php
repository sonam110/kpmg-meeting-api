<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingDocument extends Model
{
    use HasFactory;
    protected $fillable = ['meeting_id','document','file_extension','file_name','uploading_file_name'];
}
