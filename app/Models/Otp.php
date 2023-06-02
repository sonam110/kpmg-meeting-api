<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Otp extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'email',
        'otp',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->useLogName('OTP')
            ->setDescriptionForEvent(fn(string $eventName) => "OTP has been {$eventName}");
    }
}
