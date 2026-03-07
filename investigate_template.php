<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$nik = 'ADM-000219';
$employee = \App\Models\Employee::where('nik_internal', $nik)->with(['position', 'department', 'branch'])->first();

if (!$employee) {
    echo "Karyawan NIK $nik tidak ditemukan.\n";
    exit;
}

echo "=== DATA KARYAWAN ===\n";
echo "Nama: " . $employee->name . "\n";
echo "Posisi ID: " . $employee->position_id . " - " . ($employee->position->name ?? 'N/A') . "\n";
echo "Departemen ID: " . $employee->department_id . " - " . ($employee->department->name ?? 'N/A') . "\n";
echo "Cabang ID: " . $employee->branch_id . " - " . ($employee->branch->name ?? 'N/A') . "\n";
echo "\n";

echo "=== MENCARI TEMPLATE ===\n";
// Kita coba simulasikan pencarian template seperti di PayrollTemplateResolver
$query = \App\Models\PayrollTemplate::with('components.component')
            ->where('is_active', true);

// Di PayrollTemplateResolver.php saat ini:
// $query->where(function ($q) use ($employee) {
//     $q->where('position_id', $employee->position_id)
//       ->orWhere('department_id', $employee->department_id)
//       ->orWhere('branch_id', $employee->branch_id);
// });

$templates = $query->get();
$found = [];

foreach ($templates as $t) {
    // Simulasi penilaian prioritas dari resolver
    $score = 0;
    $matchRes = [];
    if ($t->position_id && $t->position_id == $employee->position_id) { $score += 100; $matchRes[] = 'Position'; }
    if ($t->department_id && $t->department_id == $employee->department_id) { $score += 10; $matchRes[] = 'Department'; }
    if ($t->branch_id && $t->branch_id == $employee->branch_id) { $score += 1; $matchRes[] = 'Branch'; }
    
    // Tapi coba kalau templatenya cuma match salah satu doang dan sisanya Null?
    // Atau kasus cross-match?
    if ($score > 0) {
        $found[] = [
            'id' => $t->id,
            'name' => $t->name,
            'pos' => $t->position_id,
            'dep' => $t->department_id,
            'branch' => $t->branch_id,
            'score' => $score,
            'matched' => implode(', ', $matchRes)
        ];
    }
}

usort($found, function($a, $b) { return $b['score'] <=> $a['score']; });

echo "Templates yang cocok (diurutkan berdasarkan skor tertinggi):\n";
print_r($found);

if (!empty($found)) {
    echo "\n=> Secara teori, Template yang terpilih adalah: " . $found[0]['name'] . "\n";
} else {
    echo "\n=> TIDAK ADA template yang cocok dengan profil karyawan!\n";
}
