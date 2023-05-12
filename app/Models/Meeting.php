<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendee;
use App\Models\User;
use App\Models\MeetingDocument;
class Meeting extends Model
{
    use HasFactory;

    public function attendees()
    {
         return $this->hasMany(Attendee::class, 'meeting_id', 'id');
    }
    public function documents()
    {
         return $this->hasMany(MeetingDocument::class, 'meeting_id', 'id')->where('type','meeting');
    }
    public function notes()
    {
         return $this->hasMany(MeetingNote::class, 'meeting_id', 'id');
    }
    public function organiser()
    {
         return $this->belongsTo(User::class, 'organised_by', 'id');
    }
}
