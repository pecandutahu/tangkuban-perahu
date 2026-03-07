<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollTemplate extends Model
{
    protected $fillable = [
        'name',
        'employment_type',
        'position_id',
    ];

    public function components()
    {
        return $this->hasMany(PayrollTemplateComponent::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
