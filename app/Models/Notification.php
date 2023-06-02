<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Notification extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'user_id','status_code','title','message','data_id','read_at','sender_id','read_status'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->useLogName('Notification')
            ->setDescriptionForEvent(fn(string $eventName) => "Notification has been {$eventName}");
    }

    public function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }
}