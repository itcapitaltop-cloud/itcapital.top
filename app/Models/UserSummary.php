<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UserSummary extends Model
{
    use HasFactory;

    protected $table = 'user_summary';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'investments_sum',
        'buy_packages_sum',
        'reinvests_sum',
        'partner_balance',
        'partners_count',
        'first_package_at',
    ];
}
