<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Attendee extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'meeting_id',
        'user_id',

    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->useLogName('Attendee')
            ->setDescriptionForEvent(fn(string $eventName) => "Attendee has been {$eventName}");
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
