<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollTemplate extends Model
{
    protected $fillable = [
        'name',
        'employment_type',
    ];
    public function components()
    {
        return $this->hasMany(PayrollTemplateComponent::class);
    }

}
