<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageBalanceWithdraw extends Model
{
    use HasFactory;

    protected $fillable = ['uuid', 'package_uuid'];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'uuid', 'uuid');
    }

    public function package()
    {
        return $this->belongsTo(ItcPackage::class, 'package_uuid', 'uuid');
    }
}
