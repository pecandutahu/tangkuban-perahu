<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollTemplateComponent extends Model
{
    protected $fillable = [
        'payroll_template_id',
        'payroll_component_id',
    ];
    public function component()
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }

}
