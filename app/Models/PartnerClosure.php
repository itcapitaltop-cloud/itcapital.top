<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartnerClosure extends Model
{
    use HasFactory;

    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = ['ancestor_id', 'descendant_id', 'depth'];
}
