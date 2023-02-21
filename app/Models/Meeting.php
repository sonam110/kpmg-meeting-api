<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Attendee;
use App\Models\MeetingDocument;
class Meeting extends Model
{
    use HasFactory;

    public function Attendees()
    {
         return $this->hasMany(Attendee::class, 'meeting_id', 'id');
    }
    public function documents()
    {
         return $this->hasMany(MeetingDocument::class, 'meeting_id', 'id');
    }
}
