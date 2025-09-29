<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageZeroing extends Model
{
    use HasFactory;

    protected $fillable = ['package_uuid', 'transaction_uuid'];

    public function package()
    {
        return $this->belongsTo(ItcPackage::class, 'package_uuid', 'uuid');
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_uuid', 'uuid');
    }
}
