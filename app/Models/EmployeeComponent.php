<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payroll_component_id',
        'amount',
        'is_active',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function component()
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }
}
