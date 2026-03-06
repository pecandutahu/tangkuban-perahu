<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_period_id',
        'employee_id',
        'employee_name',
        'department_name',
        'branch_name',
        'total_bruto',
        'total_deduction',
        'total_netto',
        'status',
    ];

    protected $casts = [
        'total_bruto' => 'decimal:2',
        'total_deduction' => 'decimal:2',
        'total_netto' => 'decimal:2',
    ];

    public function period()
    {
        return $this->belongsTo(PayrollPeriod::class, 'payroll_period_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function components()
    {
        return $this->hasMany(PayrollItemComponent::class);
    }

    protected static function booted()
    {
        $guard = function ($item) {
            // Jika period sudah diload dan bukan draft, tolak perubahan (kecuali jika item ini baru saja dibuat bersamaan periodnya)
            if ($item->payroll_period_id) {
                $period = PayrollPeriod::find($item->payroll_period_id);
                if ($period && $period->status !== 'draft') {
                    throw new \DomainException("Immutable Guard: Tidak dapat merubah Payroll Item karena periode sudah berstatus {$period->status}.");
                }
            }
        };

        static::updating($guard);
        static::deleting($guard);
    }
}
