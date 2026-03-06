<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'nik_internal',
        'name',
        'ktp_number',
        'npwp_number',
        'ptkp_status',
        'department_id',
        'position_id',
        'branch_id',
        'employment_type',
        'join_date',
        'resign_date',
        'is_active',
        'payment_method',
        'bank_name',
        'bank_account',
    ];

    protected $casts = [
        'join_date' => 'date',
        'resign_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function specificComponents()
    {
        return $this->hasMany(EmployeeComponent::class, 'employee_id');
    }
}
