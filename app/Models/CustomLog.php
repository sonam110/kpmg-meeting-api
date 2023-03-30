<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;


class CustomLog extends Model
{
    use HasFactory;
    protected $fillable = ['created_by','type','event','ip_address','location','status','last_record_before_edition','failure_reason'];

    public function getLovationAttribute($value)
    {
        return json_decode($value);
    }

    public function createdBy()
    {
         return $this->belongsTo(User::class, 'created_by', 'id');
    }
}
