<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssigneModule extends Model
{
    use HasFactory;
    protected $table = 'assigne_modules';
    protected $connection = 'kpmg_master_db';

    protected $fillable = [
        'module_id',
        'user_id',
    ];

}
