<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Meeting;
use App\Models\User;
class ActionItem extends Model
{
    use HasFactory;

    public function meeting()
    {
        return $this->belongsTo(Meeting::class, 'meeting_id', 'id');
    }
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }
   
}
