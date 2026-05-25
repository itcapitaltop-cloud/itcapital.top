<?php

declare(strict_types=1);

namespace App\Models\Package;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PackageDefinition extends Model
{
    use SoftDeletes;
}
