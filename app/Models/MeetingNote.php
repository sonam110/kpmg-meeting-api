<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Meeting;
use App\Models\MeetingDocument;
use App\Models\User;
use App\Models\ActionItem;

class MeetingNote extends Model
{
    use HasFactory;

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id', 'id');
    }
    // public function documents()
    // {
    //     return $this->hasMany(MeetingDocument::class, 'meeting_id', 'meeting_id');
    // }
    public function documents()
    {
        return $this->hasMany(MeetingDocument::class, 'note_id', 'id')->where('type','note');
    }
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }
    public function editedBy()
    {
        return $this->belongsTo(User::class, 'edited_by', 'id');
    }
    public function actionItems()
    {
        return $this->hasMany(ActionItem::class, 'note_id', 'id');
    }
}
