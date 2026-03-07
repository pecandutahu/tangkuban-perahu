<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PtkpStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'amount',
        'description',
    ];
}
