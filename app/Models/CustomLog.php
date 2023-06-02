<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CustomLog extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['created_by','type','event','status','last_record_before_edition','failure_reason'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->useLogName('Custom Log')
            ->setDescriptionForEvent(fn(string $eventName) => "Custom Log has been {$eventName}");
    }

    public function getLovationAttribute($value)
    {
        return json_decode($value);
    }

    public function createdBy()
    {
         return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
