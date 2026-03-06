<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItemComponent extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_item_id',
        'payroll_component_id',
        'component_code',
        'component_name',
        'component_type',
        'amount',
        'source',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function item()
    {
        return $this->belongsTo(PayrollItem::class, 'payroll_item_id');
    }

    public function component()
    {
        return $this->belongsTo(PayrollComponent::class, 'payroll_component_id');
    }

    protected static function booted()
    {
        $guard = function ($itemComponent) {
            if ($itemComponent->payroll_item_id) {
                $item = PayrollItem::with('period')->find($itemComponent->payroll_item_id);
                if ($item && $item->period && $item->period->status !== 'draft') {
                    throw new \DomainException("Immutable Guard: Tidak dapat mengubah Komponen karena periode sudah berstatus {$item->period->status}.");
                }
            }
        };

        static::updating($guard);
        static::deleting($guard);
        static::creating($guard); // Prevent adding new components if not draft
    }
}
