<?php

namespace App\Modules\Payroll\Services;

use App\Models\PayrollPeriod;
use App\Models\AuditLog;
use Illuminate\Support\Facades\DB;
use Exception;

class PayrollStatusTransitionService
{
    /**
     * Kirim draf untuk direview
     */
    public function markAsReviewed(int $periodId, int $userId, ?string $notes = null)
    {
        return $this->transition($periodId, 'draft', 'pending-approval', $userId, $notes);
    }

    /**
     * Setujui Draf Gaji
     */
    public function markAsApproved(int $periodId, int $approverId, ?string $notes = null)
    {
        return DB::transaction(function () use ($periodId, $approverId, $notes) {
            $period = $this->transition($periodId, 'pending-approval', 'approved', $approverId, $notes);
            
            $period->update([
                'approved_by' => $approverId,
                'approved_at' => now(),
            ]);

            return $period;
        });
    }

    /**
     * Tandai sudah ditransfer / dibayar
     */
    public function markAsPaid(int $periodId, int $userId, ?string $notes = null)
    {
        return DB::transaction(function () use ($periodId, $userId, $notes) {
            $period = $this->transition($periodId, 'approved', 'paid', $userId, $notes);
            
            $period->update([
                'paid_at' => now(),
            ]);

            return $period;
        });
    }

    /**
     * Tutup Buku
     */
    public function markAsClosed(int $periodId, int $userId, ?string $notes = null)
    {
        return $this->transition($periodId, 'paid', 'closed', $userId, $notes);
    }

    /**
     * [REJECT] Kembalikan ke Draft
     */
    public function rejectToDraft(int $periodId, int $userId, ?string $notes = null)
    {
        return $this->transition($periodId, 'pending-approval', 'draft', $userId, $notes);
    }

    /**
     * [REJECT] Tolak Permanen / Void
     */
    public function markAsRejected(int $periodId, int $userId, ?string $notes = null)
    {
        return $this->transition($periodId, 'pending-approval', 'rejected', $userId, $notes);
    }

    /**
     * Fungsi helper untuk validasi transisi state & Audit Logging
     */
    protected function transition(int $periodId, string $expectedCurrentStatus, string $newStatus, int $userId, ?string $notes = null): PayrollPeriod
    {
        $period = PayrollPeriod::findOrFail($periodId);

        if ($period->status !== $expectedCurrentStatus && !($expectedCurrentStatus === 'draft' && $period->status === 'draft')) {
           // Allow self transition on draft if needed, but otherwise block it.
           if ($period->status !== $expectedCurrentStatus) {
                throw new Exception("Transisi tidak valid. Status saat ini '{$period->status}', tidak dapat diubah ke '{$newStatus}'.");
           }
        }

        $oldStatus = $period->status;
        $period->update(['status' => $newStatus]);
        
        // Propagate status ke semua item
        $period->items()->update(['status' => $newStatus]);

        // Rekam Jejak Ke Database
        AuditLog::create([
            'user_id' => $userId,
            'action' => 'change_status',
            'entity_type' => PayrollPeriod::class,
            'entity_id' => $period->id,
            'before_data' => ['status' => $oldStatus],
            'after_data' => [
                'status' => $newStatus, 
                'notes' => $notes
            ],
        ]);

        return $period;
    }
}
