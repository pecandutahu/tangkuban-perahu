<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollComponent extends Model
{
    protected $fillable = [
        'code',
        'name',
        'component_type',
        'is_variable',
        'default_amount',
        'is_active',
    ];
}
