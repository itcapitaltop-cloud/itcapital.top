<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Itc\PackageTypeEnum;
use App\Enums\Transactions\BalanceTypeEnum;
use App\Enums\Transactions\TrxTypeEnum;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 *
 *
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string $username
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property mixed $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Notifications\DatabaseNotificationCollection<int, \Illuminate\Notifications\DatabaseNotification> $notifications
 * @property-read int|null $notifications_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|User query()
 * @method static \Illuminate\Database\Eloquent\Builder|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereFirstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereLastName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|User whereUsername($value)
 * @property \Illuminate\Support\Carbon|null $banned_at
 * @method static \Illuminate\Database\Eloquent\Builder|User whereBannedAt($value)
 * @property int $rank
 * @method static \Illuminate\Database\Eloquent\Builder|User whereRank($value)
 * @mixin \Eloquent
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'rank',
        'password',
        'banned_at',
        'email_verified_at',
        'locale',
        'pending_email',
        'telegram',
        'extended_lines',
        'overridden_rank',
        'overridden_rank_from',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'banned_at' => 'datetime'
        ];
    }

    protected $appends = [
        'investments_sum',
        'reinvests_sum',
        'partner_balance',
        'first_package_at',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('notBanned', function (Builder $builder) {
            $builder->whereNull('banned_at');
        });
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function depositTransactions(): HasMany
    {
        return $this->transactions()
            ->where('trx_type', TrxTypeEnum::DEPOSIT->value)
            ->whereNotNull('accepted_at')
            ->whereNull('rejected_at');
    }

    public function buyPackageTransactions(): HasMany
    {
        return $this->transactions()
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE->value)
            ->whereNotNull('accepted_at')
            ->whereNull('rejected_at');
    }

    public function depositAndHiddenTransactions(): HasMany
    {
        return $this->transactions()
            ->whereIn('trx_type', [
                TrxTypeEnum::DEPOSIT->value,
                TrxTypeEnum::HIDDEN_DEPOSIT->value,
            ])
            ->whereNotNull('accepted_at')
            ->whereNull('rejected_at');
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class, 'partner_id', 'id');
    }

    protected function investmentsSum(): Attribute
    {
        return Attribute::make(
            get: fn(): float => (float) ($this->attributes['investments_sum'] ?? 0),
        );
    }

    public function investmentTransactions(): HasMany
    {
        return $this->transactions()
            ->whereIn('trx_type', [
                TrxTypeEnum::DEPOSIT->value,
                TrxTypeEnum::BUY_PACKAGE->value,
            ]);
    }

    public function reinvestProfits(): HasManyThrough
    {
        return $this->hasManyThrough(
            PackageProfitReinvest::class,
            Transaction::class,
            'user_id',
            'package_uuid',
            'id',
            'uuid'
        );
    }

    public function getActivePackagesAmount(): float
    {
        return $this->transactions()
            ->where('trx_type', TrxTypeEnum::BUY_PACKAGE)
            ->whereNotNull('accepted_at')
            ->whereHas('itcPackage', fn ($q) =>
            $q->whereNull('closed_at')
                ->where('type', '!=', PackageTypeEnum::ARCHIVE)
            )
            ->sum('amount');
    }

    public function summary(): HasOne
    {
        return $this->hasOne(UserSummary::class, 'user_id');
    }

    protected function reinvestsSum(): Attribute
    {
        return Attribute::make(
            get: fn(): float => (float) ($this->attributes['reinvests_sum'] ?? 0),
        );
    }

    protected function partnerBalance(): Attribute
    {
        return Attribute::make(
            get: fn(): float => (float) ($this->attributes['partner_balance'] ?? 0),
        );
    }

    protected function firstPackageAt(): Attribute
    {
        return Attribute::make(
            get: fn(): ?string => $this->attributes['first_package_at'] ?? null,
        );
    }

    public function referrer(): HasOneThrough
    {
        return $this->hasOneThrough(
            User::class,
            Partner::class,
            'user_id',
            'id',
            'id',
            'partner_id'
        );
    }

    public function deposits(): HasManyThrough
    {
        return $this->hasManyThrough(
            Deposit::class,
            Transaction::class,
            'user_id',    // foreign key on transactions
            'uuid',       // foreign key on deposits
            'id',         // local key on users
            'uuid'        // local key on transactions
        );
    }

    // первый депозит
    public function firstDeposit(): HasOneThrough
    {
        return $this->hasOneThrough(
            Deposit::class,
            Transaction::class,
            'user_id',
            'uuid',
            'id',
            'uuid'
        )
            ->orderBy('deposits.created_at', 'asc');
    }

    // последний депозит
    public function lastDeposit(): HasOneThrough
    {
        return $this->hasOneThrough(
            Deposit::class,
            Transaction::class,
            'user_id',
            'uuid',
            'id',
            'uuid'
        )
            ->orderBy('deposits.created_at', 'desc');
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(PartnerRank::class, 'rank_id');
    }

    public function levelOverride(): HasOne
    {
        return $this->hasOne(UserLevelOverride::class);
    }

    public function lineLimitOverride(): HasOne
    {
        return $this->hasOne(UserLineLimitOverride::class);
    }

    public function partnerRewards()
    {
        return $this->hasMany(PartnerReward::class, 'from_user_id', 'id');
    }
}
