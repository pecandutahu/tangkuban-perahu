<?php

namespace App\Modules\Payroll\Services;

use App\Models\Employee;
use App\Models\PayrollComponent;
use App\Models\PayrollItem;
use App\Models\PayrollItemComponent;
use App\Models\PayrollPeriod;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Exception;

class VariableImportService
{
    /**
     * Import Variable Components from CSV
     */
    public function import(int $periodId, UploadedFile $file)
    {
        $period = PayrollPeriod::findOrFail($periodId);

        if ($period->status !== 'draft') {
            throw new Exception("Hanya periode berstatus draft yang dapat diubah.");
        }

        $path = $file->getRealPath();
        
        $handle = fopen($path, 'r');
        if ($handle === false) {
            throw new Exception("Gagal membaca file CSV.");
        }

        DB::beginTransaction();
        try {
            // Bersihkan sisa data import sebelumnya agar CSV bertindak me-replace (tidak menumpuk)
            $itemIds = PayrollItem::where('payroll_period_id', $periodId)->pluck('id')->toArray();
            
            $affectedItemIds = PayrollItemComponent::whereIn('payroll_item_id', $itemIds)
                ->where('source', 'IMPORT')
                ->pluck('payroll_item_id')
                ->unique()
                ->toArray();

            if (!empty($affectedItemIds)) {
                PayrollItemComponent::whereIn('payroll_item_id', $affectedItemIds)
                    ->where('source', 'IMPORT')
                    ->delete();
                
                // Kalkulasi ulang sementara item yang kehilangan komponen IMPORT lamanya 
                foreach ($affectedItemIds as $affectedId) {
                    $item = PayrollItem::find($affectedId);
                    if ($item) {
                        $this->recalculateItem($item);
                    }
                }
            }

            // Asumsi baris 1 adalah Header
            $header = fgetcsv($handle, 1000, ",");
            
            // Bersihkan BOM Unicode jika ada pada elemen pertama
            if (isset($header[0])) {
                $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
            }
            
            $errors = [];
            $rowNum = 1;

            // Deteksi posisi Index Kolom berdasarkan Header
            $nikIdx = array_search('nik_internal', $header);
            if ($nikIdx === false) $nikIdx = 0; // Fallback lama
            
            $compIdx = array_search('component_code', $header);
            if ($compIdx === false) $compIdx = 1; // Fallback lama
            
            $amountIdx = array_search('amount', $header);
            if ($amountIdx === false) $amountIdx = 2; // Fallback lama

            while (($data = fgetcsv($handle, 1000, ",")) !== false) {
                $rowNum++;
                
                // Abaikan baris kosong
                if (count($data) < 3) continue;

                $nik = trim($data[$nikIdx] ?? '');
                $compCode = trim($data[$compIdx] ?? '');
                $amount = (float) trim($data[$amountIdx] ?? 0);

                $employee = Employee::where('nik_internal', $nik)->first();
                if (!$employee) {
                    $errors[] = "Baris {$rowNum}: NIK {$nik} tidak ditemukan.";
                    continue;
                }

                $item = PayrollItem::where('payroll_period_id', $periodId)
                    ->where('employee_id', $employee->id)
                    ->first();

                if (!$item) {
                    $errors[] = "Baris {$rowNum}: NIK {$nik} tidak ada dalam draft periode ini.";
                    continue;
                }

                $component = PayrollComponent::where('code', $compCode)->first();
                if (!$component) {
                    $errors[] = "Baris {$rowNum}: Kode komponen {$compCode} tidak valid.";
                    continue;
                }

                // Update jika komponen sudah ada, Create jika belum ada
                PayrollItemComponent::updateOrCreate(
                    [
                        'payroll_item_id' => $item->id,
                        'payroll_component_id' => $component->id,
                    ],
                    [
                        'component_code' => $component->code,
                        'component_name' => $component->name,
                        'component_type' => $component->component_type,
                        'amount' => $amount,
                        'source' => 'IMPORT',
                    ]
                );

                // Hitung ulang total di item ini
                $this->recalculateItem($item);
            }

            fclose($handle);

            // Terapkan kebijakan All-or-Nothing
            if (count($errors) > 0) {
                DB::rollBack();
                return ['success' => false, 'errors' => $errors];
            }

            DB::commit();
            return ['success' => true, 'message' => "Import berhasil tanpa error."];

        } catch (Exception $e) {
            DB::rollBack();
            fclose($handle);
            throw $e;
        }
    }

    protected function recalculateItem(PayrollItem $item)
    {
        $bruto = PayrollItemComponent::where('payroll_item_id', $item->id)
            ->where('component_type', 'earning')
            ->sum('amount');
            
        $deduction = PayrollItemComponent::where('payroll_item_id', $item->id)
            ->where('component_type', 'deduction')
            ->sum('amount');

        $item->update([
            'total_bruto' => $bruto,
            'total_deduction' => $deduction,
            'total_netto' => $bruto - $deduction,
        ]);
    }
}
