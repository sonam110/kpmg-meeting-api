<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Meeting;
use App\Models\User;
use App\Models\MeetingDocument;
class ActionItem extends Model
{
    use HasFactory;
    protected $fillable = ['status','complete_date','complete_percentage','meeting_id','note_id','owner_id','mm_ref_id','date_opened','task','priority','due_date','image','comment'];

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id', 'id');
    }

   	public function documents()
    {
        return $this->hasMany(MeetingDocument::class, 'action_id', 'id')->where('type','action');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }
   
}
