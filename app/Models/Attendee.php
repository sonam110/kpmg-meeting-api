<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
class Attendee extends Model
{
    use HasFactory;
    protected $fillable = [
        'meeting_id',
        'user_id',

    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id')->where('status', 1);
    }
}
