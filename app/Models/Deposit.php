<?php

namespace App\Models;

use App\Enums\Transactions\CurrencyEnum;
use App\Traits\Models\TransactionChildTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 *
 *
 * @property int $id
 * @property string $uuid
 * @property string $commission
 * @property CurrencyEnum $currency
 * @property string $transaction_hash
 * @property string $wallet_address
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit query()
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereTransactionHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Deposit whereWalletAddress($value)
 * @property-read Transaction|null $transaction
 * @mixin \Eloquent
 */
class Deposit extends Model
{
    use HasFactory, TransactionChildTrait;

    protected $fillable = [
        'uuid', 'commission', 'currency', 'transaction_hash', 'wallet_address', 'payment_source_id'
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(
            Transaction::class,
            'uuid',    // foreign key on deposits table
            'uuid'     // owner key on transactions table
        );
    }

    public function paymentSource(): BelongsTo
    {
        return $this->belongsTo(PaymentSource::class);
    }

    protected $casts = [
        'currency' => CurrencyEnum::class
    ];
}
