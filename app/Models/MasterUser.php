<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class MasterUser extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'users';
    protected $connection = 'kpmg_master_db';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['*'])
            ->logOnlyDirty()
            ->useLogName('Master user')
            ->setDescriptionForEvent(fn(string $eventName) => "Master user has been {$eventName}");
    }

    /*
        // encryption AES-256-CBC
        'key' => env('APP_KEY'),
        'cipher' => 'AES-256-CBC',
    */
    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => decrypt($value),
            set: fn ($value) => encrypt($value),
        );
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => decrypt($value),
            set: fn ($value) => encrypt($value),
        );
    }
}
