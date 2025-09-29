<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogAdminAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'action_type',
        'model_type',
        'model_id',
        'target_username',
        'target_user_id',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];
}
