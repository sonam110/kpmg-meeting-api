<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class AssigneModule extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'assigne_modules';
    protected $connection = 'kpmg_master_db';

    protected $fillable = [
        'module_id',
        'user_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->useLogName('Assign module')
            ->setDescriptionForEvent(fn(string $eventName) => "Assign module has been {$eventName}");
    }

}
