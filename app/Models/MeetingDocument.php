<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MeetingDocument extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['meeting_id','document','file_extension','file_name','uploading_file_name'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->useLogName('Meeting document')
            ->setDescriptionForEvent(fn(string $eventName) => "Meeting document has been {$eventName}");
    }
}
