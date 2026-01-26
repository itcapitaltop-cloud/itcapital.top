<?php

declare(strict_types=1);

namespace App\Models\User;

use Illuminate\Database\Eloquent\Model;

final class Session extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'ip_address',
    ];
}
