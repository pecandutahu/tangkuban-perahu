<?php

namespace App\Imports;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class EmployeeImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    public array $results = [];
    public int $successCount = 0;
    public int $errorCount = 0;

    private int $importedByUserId;

    public function __construct(int $userId)
    {
        $this->importedByUserId = $userId;
    }

    public function collection(Collection $rows): void
    {
        // Preload lookup tables untuk efisiensi
        $departments = DB::table('departments')->pluck('id', 'name');
        $positions   = DB::table('positions')->pluck('id', 'name');
        $branches    = DB::table('branch')->pluck('id', 'name');
        $ptkpCodes   = DB::table('ptkp_statuses')->pluck('code')->toArray();

        $validEmploymentTypes = ['tetap', 'kontrak', 'probation', 'paruh waktu'];
        $validPaymentMethods  = ['transfer', 'cash', 'cek'];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 karena baris 1 = heading

            $rowData = $row->toArray();
            // Normalisasi key (lowercase, ganti spasi jadi underscore)
            $data = [];
            foreach ($rowData as $key => $value) {
                $data[strtolower(str_replace(' ', '_', $key))] = $value;
            }

            // --- Validasi dasar ---
            $validator = Validator::make($data, [
                'nik_internal'    => 'required|string|max:50',
                'name'            => 'required|string|max:150',
                'employment_type' => 'required|string',
                'join_date'       => 'required',
                'payment_method'  => 'required|string',
            ]);

            if ($validator->fails()) {
                $this->results[] = [
                    'row'    => $rowNumber,
                    'status' => 'error',
                    'nik'    => $data['nik_internal'] ?? '(kosong)',
                    'name'   => $data['name'] ?? '(kosong)',
                    'reason' => implode(', ', $validator->errors()->all()),
                ];
                $this->errorCount++;
                continue;
            }

            // --- Validasi NIK unik ---
            if (Employee::query()->where('nik_internal', trim($data['nik_internal']))->exists()) {
                $this->results[] = [
                    'row'    => $rowNumber,
                    'status' => 'error',
                    'nik'    => $data['nik_internal'],
                    'name'   => $data['name'],
                    'reason' => 'NIK sudah terdaftar di sistem.',
                ];
                $this->errorCount++;
                continue;
            }

            // --- Resolve FK via nama ---
            $departmentId = null;
            if (!empty($data['department_name'])) {
                $departmentId = $departments->get($data['department_name']);
                if (!$departmentId) {
                    $this->results[] = [
                        'row'    => $rowNumber,
                        'status' => 'error',
                        'nik'    => $data['nik_internal'],
                        'name'   => $data['name'],
                        'reason' => "Departemen '{$data['department_name']}' tidak ditemukan.",
                    ];
                    $this->errorCount++;
                    continue;
                }
            }

            $positionId = null;
            if (!empty($data['position_name'])) {
                $positionId = $positions->get($data['position_name']);
                if (!$positionId) {
                    $this->results[] = [
                        'row'    => $rowNumber,
                        'status' => 'error',
                        'nik'    => $data['nik_internal'],
                        'name'   => $data['name'],
                        'reason' => "Jabatan '{$data['position_name']}' tidak ditemukan.",
                    ];
                    $this->errorCount++;
                    continue;
                }
            }

            $branchId = null;
            if (!empty($data['branch_name'])) {
                $branchId = $branches->get($data['branch_name']);
                if (!$branchId) {
                    $this->results[] = [
                        'row'    => $rowNumber,
                        'status' => 'error',
                        'nik'    => $data['nik_internal'],
                        'name'   => $data['name'],
                        'reason' => "Cabang '{$data['branch_name']}' tidak ditemukan.",
                    ];
                    $this->errorCount++;
                    continue;
                }
            }

            // --- Validasi employment_type ---
            $employmentType = strtolower(trim($data['employment_type']));
            if (!in_array($employmentType, $validEmploymentTypes)) {
                $this->results[] = [
                    'row'    => $rowNumber,
                    'status' => 'error',
                    'nik'    => $data['nik_internal'],
                    'name'   => $data['name'],
                    'reason' => "Tipe karyawan '{$data['employment_type']}' tidak valid. Pilihan: " . implode(', ', $validEmploymentTypes),
                ];
                $this->errorCount++;
                continue;
            }

            // --- Validasi payment_method ---
            $paymentMethod = strtolower(trim($data['payment_method']));
            if (!in_array($paymentMethod, $validPaymentMethods)) {
                $this->results[] = [
                    'row'    => $rowNumber,
                    'status' => 'error',
                    'nik'    => $data['nik_internal'],
                    'name'   => $data['name'],
                    'reason' => "Metode pembayaran '{$data['payment_method']}' tidak valid. Pilihan: " . implode(', ', $validPaymentMethods),
                ];
                $this->errorCount++;
                continue;
            }

            // --- Validasi ptkp_status jika diisi ---
            $ptkpStatus = !empty($data['ptkp_status']) ? strtoupper(trim($data['ptkp_status'])) : null;
            if ($ptkpStatus && !in_array($ptkpStatus, $ptkpCodes)) {
                $this->results[] = [
                    'row'    => $rowNumber,
                    'status' => 'error',
                    'nik'    => $data['nik_internal'],
                    'name'   => $data['name'],
                    'reason' => "Status PTKP '{$ptkpStatus}' tidak ditemukan di master data.",
                ];
                $this->errorCount++;
                continue;
            }

            // --- Parse join_date ---
            try {
                $joinDate = \Carbon\Carbon::parse($data['join_date'])->format('Y-m-d');
            } catch (\Exception $e) {
                $this->results[] = [
                    'row'    => $rowNumber,
                    'status' => 'error',
                    'nik'    => $data['nik_internal'],
                    'name'   => $data['name'],
                    'reason' => "Format tanggal join_date tidak valid: '{$data['join_date']}'.",
                ];
                $this->errorCount++;
                continue;
            }

            $isActive = isset($data['is_active']) && in_array((string)$data['is_active'], ['1', 'true', 'ya', 'yes', 'aktif'], true);

            // --- Simpan ke database ---
            DB::beginTransaction();
            try {
                $employee = Employee::create([
                    'nik_internal'    => trim($data['nik_internal']),
                    'name'            => trim($data['name']),
                    'ktp_number'      => $data['ktp_number'] ?? null,
                    'npwp_number'     => $data['npwp_number'] ?? null,
                    'ptkp_status'     => $ptkpStatus,
                    'department_id'   => $departmentId,
                    'position_id'     => $positionId,
                    'branch_id'       => $branchId,
                    'employment_type' => $employmentType,
                    'join_date'       => $joinDate,
                    'is_active'       => $isActive,
                    'payment_method'  => $paymentMethod,
                    'bank_name'       => $data['bank_name'] ?? null,
                    'bank_account'    => $data['bank_account'] ?? null,
                ]);

                // Auto-create user account
                $userEmail = strtolower($employee->nik_internal) . '@company.local';
                if (!User::query()->where('email', $userEmail)->exists()) {
                    $user = User::create([
                        'name'        => $employee->name,
                        'email'       => $userEmail,
                        'password'    => Hash::make('password'),
                        'employee_id' => $employee->id,
                    ]);
                    $user->assignRole('Karyawan');
                }

                // Auto-generate BPJS components
                $finalComponents = \App\Modules\Payroll\Services\BpjsCalculatorService::calculateForAdmin($employee, []);
                foreach ($finalComponents as $comp) {
                    $employee->specificComponents()->create([
                        'payroll_component_id' => $comp['payroll_component_id'],
                        'amount'               => $comp['amount'],
                        'is_active'            => true,
                    ]);
                }

                \App\Models\AuditLog::create([
                    'user_id'     => $this->importedByUserId,
                    'entity_type' => Employee::class,
                    'entity_id'   => $employee->id,
                    'action'      => 'import_employee',
                    'before_data' => [],
                    'after_data'  => $employee->toArray(),
                ]);

                DB::commit();

                $this->results[] = [
                    'row'    => $rowNumber,
                    'status' => 'success',
                    'nik'    => $employee->nik_internal,
                    'name'   => $employee->name,
                    'reason' => 'Berhasil diimpor.',
                ];
                $this->successCount++;
            } catch (\Exception $e) {
                DB::rollBack();
                $this->results[] = [
                    'row'    => $rowNumber,
                    'status' => 'error',
                    'nik'    => $data['nik_internal'],
                    'name'   => $data['name'],
                    'reason' => 'Gagal menyimpan: ' . $e->getMessage(),
                ];
                $this->errorCount++;
            }
        }
    }
}
