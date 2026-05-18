# API Contract Specification (REST API) - Sobat Gaji

Dokumen ini mendefinisikan rancangan kontrak antarmuka API (API Contract) untuk sistem Sobat Gaji. Kontrak ini dirancang dengan asumsi backend akan dibangun ulang menggunakan **Golang** dan frontend menggunakan **Vue 3 sebagai murni SPA (Single Page Application)**.

> **Catatan:** Semua endpoint berada di bawah prefix `/api/v1`. Autentikasi diasumsikan menggunakan JSON Web Token (JWT) yang dikirim via header `Authorization: Bearer <token>`.

---

## 1. Authentication Module

### 1.1 Login (dengan Math Captcha)
Digunakan oleh frontend untuk melakukan proses login. Backend wajib memvalidasi captcha yang telah di-generate sebelumnya sebelum melakukan pengecekan email/password.

- **URL:** `/auth/login`
- **Method:** `POST`
- **Request Body:**
```json
{
  "email": "admin@example.com",
  "password": "secretpassword",
  "captcha_id": "uuid-1234-5678",
  "captcha_answer": "15"
}
```
- **Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1...",
    "user": {
      "id": 1,
      "name": "Super Admin",
      "role": "super-admin"
    }
  }
}
```

---

## 2. Karyawan (Employees) Module

### 2.1 Get All Employees
Mendapatkan daftar karyawan dengan dukungan fitur *pagination*, *searching*, dan *filtering*.

- **URL:** `/employees`
- **Method:** `GET`
- **Query Params:** `?page=1&limit=10&branch_id=2&search=budi`
- **Success Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "nip": "EMP-001",
      "name": "Budi Supir",
      "branch": {
        "id": 1,
        "name": "Cabang Jakarta"
      },
      "position": {
        "id": 3,
        "name": "Driver"
      },
      "basic_salary": 3000000
    }
  ],
  "meta": {
    "total": 50,
    "current_page": 1,
    "last_page": 5
  }
}
```

### 2.2 Create / Update Employee (Trigger Otomasi BPJS)
Endpoint ini sangat krusial. **Penting di Golang:** Endpoint ini harus menjalankan *service* kalkulasi BPJS (*Hybrid Approach*) dan menyimpannya di tabel `employee_components` (sebaiknya di dalam *Database Transaction* tunggal).

- **URL:** `/employees`
- **Method:** `POST`
- **Request Body:**
```json
{
  "nip": "EMP-002",
  "name": "Agus Mekanik",
  "branch_id": 1,
  "position_id": 3,
  "department_id": 2,
  "ptkp_status_id": 1,
  "basic_salary": 4500000
}
```
- **Success Response (201 Created):**
```json
{
  "status": "success",
  "message": "Employee created and BPJS components generated automatically",
  "data": {
    "id": 2,
    "nip": "EMP-002"
  }
}
```

---

## 3. Payroll Transaction Module (Core Engine)

### 3.1 Generate Payroll
Endpoint ini bertugas menyalin data *Master Data* dan mencetak slip gaji statis untuk seluruh karyawan aktif pada periode tertentu serta menjalankan kalkulator **PPh 21 TER 2024**.

- **URL:** `/payroll/generate`
- **Method:** `POST`
- **Request Body:**
```json
{
  "period_name": "Mei 2026",
  "start_date": "2026-05-01",
  "end_date": "2026-05-31"
}
```
- **Success Response (202 Accepted):**
*Best Practice Golang:* Mengingat proses kalkulasi pajak dan *copy* data bisa memakan waktu lama, proses ini idealnya dimasukkan ke dalam *Background Worker* menggunakan Goroutines/Channel.
```json
{
  "status": "accepted",
  "message": "Payroll generation task started in background.",
  "data": {
    "period_id": 15,
    "job_status": "processing"
  }
}
```

### 3.2 Get Payroll Slips per Period
Menampilkan hasil generate slip gaji.

- **URL:** `/payroll/periods/{period_id}/slips`
- **Method:** `GET`
- **Success Response (200 OK):**
```json
{
  "status": "success",
  "data": [
    {
      "slip_id": 120,
      "employee_name": "Budi Supir",
      "total_earnings": 5500000,
      "total_deductions": 200000,
      "net_salary": 5300000,
      "pph21_tax": 25000
    }
  ]
}
```

### 3.3 Sync BPJS Massal
Fungsi ini setara dengan *command* `php artisan payroll:sync-bpjs` di sistem Laravel saat ini. Digunakan ketika HRD mengubah tarif BPJS global di *Settings*.

- **URL:** `/payroll/sync-bpjs`
- **Method:** `POST`
- **Request Body:**
```json
{
  "branch_id": null, 
  "force_overwrite": true
}
```
- **Success Response (200 OK):**
```json
{
  "status": "success",
  "message": "BPJS sync successfully executed for 150 employees."
}
```

---
## 🛠️ Panduan Penting untuk Tim Backend Golang:

1. **Web Framework & Router:** Direkomendasikan menggunakan `gin-gonic/gin`, `labstack/echo`, atau `go-chi/chi` untuk routing dan *middleware* handling (seperti verifikasi JWT & RBAC).
2. **Preloading Relasi Tabel:** Sistem payroll sangat banyak mengakses data berelasi (contoh: kalkulasi BPJS butuh data `umr_amount` dari relasi `branch`). ORM seperti **GORM** atau **Ent** sangat disarankan untuk mempermudah *eager loading*.
3. **Presisi Kalkulasi Finansial (CRITICAL):**
   Karena ini adalah sistem keuangan dan perpajakan (yang ketat terhadap perhitungan desimal), **JANGAN** gunakan tipe data `float32` atau `float64` standar milik Golang. 
   **Wajib menggunakan *library* desimal yang presisi** seperti `github.com/shopspring/decimal` untuk menghitung PPh 21 dan BPJS. Kesalahan *floating-point* bawaan Golang bisa berakibat perbedaan hasil pajak.
