# Payroll Trucking System

Sistem penggajian berbasis web untuk perusahaan trucking, dibangun menggunakan **Laravel 11** + **Inertia.js** + **Vue 3**.

---

## 🚀 Tech Stack

| Layer | Teknologi |
|-------|-----------|
| Backend | Laravel 11 (PHP 8.2+) |
| Frontend | Vue 3 (Composition API) + Inertia.js |
| Database | PostgreSQL (atau MySQL) |
| Auth | Laravel Breeze + Spatie Laravel Permission |
| Styling | Tailwind CSS |

---

## 📦 Instalasi

```bash
git clone <repo-url>
cd payroll-trucking

composer install
npm install

cp .env.example .env
php artisan key:generate

# Sesuaikan konfigurasi database di .env
php artisan migrate:refresh --seed

npm run dev
```

---

## 🗂️ Fitur Utama

### 1. Master Data
- **Karyawan** — Data lengkap: jabatan, cabang, tipe kerja, bank, PTKP
- **Cabang (Branch)** — Termasuk konfigurasi **UMR per cabang** (basis kalkulasi BPJS)
- **Jabatan (Position)** & **Departemen**
- **Komponen Gaji (Payroll Component)** — Earning dan Deduction
- **Template Gaji (Payroll Template)** — Set komponen per jabatan/tipe kerja

### 2. Generate Payroll
- Kalkulator gaji bulanan per periode
- Komponen Earning + Deduction otomatis dari template
- Override komponen spesifik per karyawan (`employee_components`)
- Ekspor slip gaji

### 3. BPJS Otomatis (Hybrid Approach)
- 6 komponen BPJS ter-generate otomatis saat input/update karyawan:

  | Kode | Tipe | Keterangan |
  |------|------|-----------|
  | `BPJS_TK` | Deduction | Iuran TK karyawan (2% JHT + 1% JP) |
  | `BPJS_KES` | Deduction | Iuran Kes karyawan (1%, plafon 12jt) |
  | `TJ_BPJS_TK_CO` | Earning | Tunjangan TK perusahaan (3.7% JHT + 2% JP + JKK + JKm) |
  | `TJ_BPJS_KES_CO` | Earning | Tunjangan Kes perusahaan (4%, plafon 12jt) |
  | `POT_BPJS_TK_CO` | Deduction | Offset TK perusahaan (= TJ_BPJS_TK_CO) |
  | `POT_BPJS_KES_CO` | Deduction | Offset Kes perusahaan (= TJ_BPJS_KES_CO) |

- **Basis perhitungan**: `max(Gaji Pokok karyawan, UMR cabang)`
- Semua tarif dapat dikonfigurasi dari **Pengaturan Sistem**

### 4. Gaji Pokok Universal
- Komponen `GP` (Gaji Pokok) bersifat universal dan menjadi **penanda basis BPJS**
- Komponen jabatan-spesifik (dahulu "Gaji Pokok Jabatan") diubah menjadi **Tunjangan Jabatan** (kode: `GP_DRV`, `GP_ADM`, dst.)
- Nilai GP diset per-karyawan melalui form input karyawan

### 5. Kalkulasi PPh 21
- Mendukung regulasi **TER 2024**
- Dapat dinonaktifkan (pilihan `none`) — untuk antisipasi revisi pemerintah
- Komponen yang dikecualikan dari Bruto Kena Pajak dapat dicentang di Pengaturan (default: `BPJS_TK`)

### 6. Pengaturan Sistem
Halaman `/settings` menyediakan konfigurasi:
- Versi kalkulator PPh 21
- Komponen pengurang Bruto pajak
- **Seluruh tarif BPJS** (karyawan + perusahaan termasuk JKK configurable)

---

## 🧱 Struktur Komponen Gaji (Seeder Default)

### Earnings (Pendapatan)
| Kode | Nama | Nominal Default |
|------|------|----------------|
| `GP` | Gaji Pokok (Universal) | 0 (diset per karyawan) |
| `GP_SPV` | Tunjangan Jabatan SPV | Rp 6.000.000 |
| `GP_ADM` | Tunjangan Jabatan Admin | Rp 4.500.000 |
| `GP_DRV` | Tunjangan Jabatan Driver | Rp 3.000.000 |
| `GP_HLP` | Tunjangan Jabatan Helper | Rp 2.000.000 |
| `GP_MCH` | Tunjangan Jabatan Mekanik | Rp 4.000.000 |
| `UM` | Uang Makan Tetap | Rp 500.000 |
| `TJ_FUNC` | Tunjangan Fungsional | 0 |
| `UJ` | Uang Jalan / Insentif Trip | 0 (variabel) |
| `LMBR` | Uang Lembur | 0 (variabel) |
| `TJ_BPJS_TK_CO` | Tunjangan BPJS TK Perusahaan | Auto |
| `TJ_BPJS_KES_CO` | Tunjangan BPJS Kes Perusahaan | Auto |

### Deductions (Potongan)
| Kode | Nama |
|------|------|
| `BPJS_TK` | Potongan BPJS Ketenagakerjaan Karyawan |
| `BPJS_KES` | Potongan BPJS Kesehatan Karyawan |
| `POT_BPJS_TK_CO` | Potongan BPJS TK Perusahaan (offset) |
| `POT_BPJS_KES_CO` | Potongan BPJS Kes Perusahaan (offset) |
| `KSB` | Potongan Kasbon |
| `KLAIM` | Potongan Klaim Barang/Kerusakan |

---

## ⚙️ Artisan Commands Kustom

### 1. Sinkronisasi BPJS Massal

Hitung ulang dan simpan komponen BPJS untuk **seluruh karyawan aktif** berdasarkan Gaji Pokok dan UMR cabangnya masing-masing. Gunakan ini ketika:
- Baru onboarding data karyawan lama (sebelum fitur BPJS otomatis)
- Ada perubahan tarif BPJS di Pengaturan Sistem
- Ada perubahan nilai UMR cabang

```bash
php artisan payroll:sync-bpjs
```

Opsi tambahan:
```bash
# Paksa overwrite meski data BPJS sudah ada
php artisan payroll:sync-bpjs --force

# Hanya karyawan dari cabang tertentu
php artisan payroll:sync-bpjs --branch=1
```

---

## 🔄 Alur Kalkulasi BPJS

```
Input Karyawan (form)
    │
    ├─ Nilai GP (Gaji Pokok) diinput manual
    │
    └─► BpjsCalculatorService::calculateForAdmin()
            │
            ├─ Ambil nilai GP dari: submitted form → employee_components → template fallback
            │
            ├─ Ambil UMR dari: employee.branch.umr_amount
            │
            ├─ baseSalary = max(GP, UMR)
            │
            ├─ Hitung 6 komponen BPJS menggunakan tarif dari settings DB
            │
            └─ Simpan ke employee_components
```

---

## 🔄 Alur Generate Payroll

```
Admin pilih Periode → Generate Payroll
    │
    ├─ Ambil semua karyawan aktif
    │
    ├─ Untuk setiap karyawan:
    │     ├─ Resolve template gaji (by jabatan/tipe)
    │     ├─ Ambil komponen override (employee_components)
    │     ├─ Hitung Total Earning & Total Deduction
    │     ├─ Hitung Bruto Kena Pajak (dikurangi komponen exclude setting)
    │     ├─ Hitung PPh 21 (via Pph21Calculator sesuai versi setting)
    │     └─ Simpan PayrollSlip
    │
    └─ Periode status: draft → published
```

---

## 🗄️ Tabel Penting

| Tabel | Keterangan |
|-------|-----------|
| `employees` | Master karyawan |
| `branch` | Master cabang, termasuk `umr_amount` |
| `payroll_components` | Master komponen gaji |
| `payroll_templates` | Template gaji per jabatan |
| `payroll_template_components` | Detail komponen per template |
| `employee_components` | Komponen override spesifik karyawan (termasuk BPJS auto) |
| `payroll_periods` | Periode penggajian |
| `payroll_slips` | Slip gaji per karyawan per periode |
| `settings` | Konfigurasi sistem (BPJS rates, PPh21 version, dll) |
| `ptkp_statuses` | Master status PTKP |

---

## 📐 Tarif BPJS Default (sesuai regulasi pemerintah)

| Komponen | Karyawan | Perusahaan |
|----------|----------|------------|
| JHT | 2% | 3.7% |
| JP | 1% | 2% |
| JKK | — | 0.24% *(configurable, sesuai risiko kerja)* |
| JKm | — | 0.3% |
| BPJS Kes | 1% | 4% |
| Plafon Kes | 12jt | 12jt |

> **JKK (Jaminan Kecelakaan Kerja)** dapat disesuaikan di Pengaturan Sistem:
> - Risiko sangat rendah: 0.24%
> - Risiko rendah: 0.54%
> - Risiko sedang: 0.89%
> - Risiko tinggi: 1.27%
> - Risiko sangat tinggi: 1.74%

---

## 🔑 Roles & Permission

| Role | Akses |
|------|-------|
| `super-admin` | Akses penuh semua fitur |
| `admin` | CRUD karyawan, generate payroll |
| `finance` | View slip gaji, laporan |
| `operator` | Input data harian (kasbon, insentif) |

---

## 📝 Catatan Pengembang

- Setelah mengubah tarif BPJS di Pengaturan, jalankan `php artisan payroll:sync-bpjs` untuk update data karyawan
- `PayrollComponentSeeder` menggunakan `updateOrCreate` — aman dijalankan berulang
- Setting `pph21_calculator_version = none` akan menonaktifkan pemotongan pajak sepenuhnya (berguna saat ada revisi regulasi pemerintah)
- UMR per cabang diisi manual di menu **Master > Cabang**
- Komponen `GP` (Gaji Pokok) diisi saat input/edit karyawan — ini yang menjadi basis BPJS, berbeda dengan `GP_DRV` dll (Tunjangan Jabatan)
