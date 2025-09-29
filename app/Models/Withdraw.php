<?php

namespace App\Models;

use App\Enums\Transactions\CurrencyEnum;
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
 * @property string $wallet_address
 * @property CurrencyEnum $currency
 * @property string|null $trx_hash
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw query()
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereCommission($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereTrxHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Withdraw whereWalletAddress($value)
 * @property-read \App\Models\Transaction|null $transaction
 * @mixin \Eloquent
 */
class Withdraw extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid', 'trx_hash', 'commission', 'wallet_address', 'currency', 'payment_source_id'
    ];

    public function transaction(): BelongsTo
    {
        return $this->BelongsTo(Transaction::class, 'uuid', 'uuid');
    }

    public function paymentSource(): BelongsTo
    {
        return $this->belongsTo(PaymentSource::class);
    }

    public function fiatDetail(): HasOne
    {
        return $this->hasOne(WithdrawFiatDetail::class, 'uuid', 'uuid');
    }

    public function getPayoutRequisiteAttribute(): string
    {
        if ($this->payment_source_id === 2 && $this->fiatDetail) {

            return collect([
                $this->fiatDetail->sbp_phone,
                $this->fiatDetail->bank_name,
                $this->fiatDetail->recipient_name,
            ])
                ->filter()
                ->implode(' · ');
        }

        return $this?->wallet_address ?? '';
    }

    // Чтобы атрибут был доступен в toArray(), toJson(), экспорте и т.д.
    protected $appends = ['payout_requisite'];

    protected $casts = [
        'currency' => CurrencyEnum::class
    ];
}
