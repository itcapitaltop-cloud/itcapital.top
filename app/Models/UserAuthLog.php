<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAuthLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip',
        'device',
        'browser',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
