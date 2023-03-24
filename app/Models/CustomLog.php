<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomLog extends Model
{
    use HasFactory;
    protected $fillable = ['created_by','type','event','ip_address','location','status','last_record_before_edition','failure_reason'];
}
