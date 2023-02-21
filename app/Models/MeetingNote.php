<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Meeting;
use App\Models\MeetingDocument;
class MeetingNote extends Model
{
    use HasFactory;

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id', 'id');
    }
    public function documents()
    {
        return $this->hasMany(MeetingDocument::class, 'meeting_id', 'meeting_id');
    }
}
