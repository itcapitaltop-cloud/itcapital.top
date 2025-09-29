<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSummary extends Model
{
    use HasFactory;
    protected $table = 'user_summary';

    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'investments_sum',
        'reinvests_sum',
        'partner_balance',
        'partners_count',
        'first_package_at',
    ];
}
