<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PaymentSource extends Model
{
    use HasFactory;

    protected $fillable = ['source'];

    public function deposit(): HasOne
    {
        return $this->hasOne(Deposit::class);
    }

    public function withdraw(): HasOne
    {
        return $this->hasOne(Withdraw::class);
    }
}
