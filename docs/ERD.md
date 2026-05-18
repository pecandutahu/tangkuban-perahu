# Entity Relationship Diagram (ERD) - Sobat Gaji

Dokumen ini memetakan relasi antar entitas (tabel) di dalam database sistem penggajian Sobat Gaji. Diagram ini sangat penting sebagai referensi utama apabila Anda ingin melakukan migrasi database atau migrasi backend ke bahasa pemrograman lain (seperti Golang).

```mermaid
erDiagram
    %% Master Data & Organisasi
    BRANCH {
        bigint id PK
        string name
        decimal umr_amount "Threshold BPJS"
    }
    POSITIONS {
        bigint id PK
        string name
    }
    DEPARTMENTS {
        bigint id PK
        string name
    }
    PTKP_STATUSES {
        bigint id PK
        string code "e.g., TK/0, K/1"
        decimal amount
    }

    %% Karyawan & User
    USERS {
        bigint id PK
        string name
        string email
        bigint employee_id FK "Nullable"
    }
    EMPLOYEES {
        bigint id PK
        string nip
        string name
        decimal basic_salary "Gaji Pokok"
        bigint branch_id FK
        bigint position_id FK
        bigint department_id FK
        bigint ptkp_status_id FK
    }

    %% Master Komponen & Template Gaji
    PAYROLL_COMPONENTS {
        bigint id PK
        string code "e.g., GP, BPJS_TK"
        string name
        enum type "Earning / Deduction"
        boolean is_taxable "Kena Pajak PPh21?"
    }
    PAYROLL_TEMPLATES {
        bigint id PK
        string name
        bigint position_id FK
    }
    PAYROLL_TEMPLATE_COMPONENTS {
        bigint id PK
        bigint payroll_template_id FK
        bigint payroll_component_id FK
        decimal default_amount
    }
    EMPLOYEE_COMPONENTS {
        bigint id PK
        bigint employee_id FK
        bigint payroll_component_id FK
        decimal override_amount "Termasuk hasil hybrid BPJS"
    }

    %% Transaksi Penggajian (Payroll Slips)
    PAYROLL_PERIODS {
        bigint id PK
        string name "e.g., May 2026"
        date start_date
        date end_date
        string status "Draft / Published"
    }
    PAYROLL_ITEMS {
        bigint id PK
        bigint payroll_period_id FK
        bigint employee_id FK
        decimal total_earnings
        decimal total_deductions
        decimal net_salary
        decimal pph21_tax "Hasil potong pajak"
    }
    PAYROLL_ITEM_COMPONENTS {
        bigint id PK
        bigint payroll_item_id FK
        bigint payroll_component_id FK
        decimal amount "Snapshot saat di-generate"
    }

    %% Relasi
    BRANCH ||--o{ EMPLOYEES : "has"
    POSITIONS ||--o{ EMPLOYEES : "has"
    DEPARTMENTS ||--o{ EMPLOYEES : "has"
    PTKP_STATUSES ||--o{ EMPLOYEES : "has"
    
    USERS ||--o| EMPLOYEES : "linked_to"

    EMPLOYEES ||--o{ EMPLOYEE_COMPONENTS : "has_overrides"
    PAYROLL_COMPONENTS ||--o{ EMPLOYEE_COMPONENTS : "applies_to"
    
    POSITIONS ||--o{ PAYROLL_TEMPLATES : "defines_template"
    PAYROLL_TEMPLATES ||--o{ PAYROLL_TEMPLATE_COMPONENTS : "contains"
    PAYROLL_COMPONENTS ||--o{ PAYROLL_TEMPLATE_COMPONENTS : "included_in"

    EMPLOYEES ||--o{ PAYROLL_ITEMS : "receives_slip"
    PAYROLL_PERIODS ||--o{ PAYROLL_ITEMS : "has_slips"
    PAYROLL_ITEMS ||--o{ PAYROLL_ITEM_COMPONENTS : "has_details"
    PAYROLL_COMPONENTS ||--o{ PAYROLL_ITEM_COMPONENTS : "recorded_as"
```

## Mengapa ERD Ini Penting untuk Migrasi Golang?
1. **Model Generasi:** Di Golang (menggunakan GORM, Ent, atau SQLx), Anda harus mendefinisikan *structs* yang persis mewakili entitas ini.
2. **Ketergantungan Foreign Key:** Diagram ini menunjukkan urutan migrasi. Anda harus memigrasi tabel `branch` dan `ptkp_statuses` sebelum memigrasi data `employees`.
3. **Snapshot Transaksi:** Tabel `payroll_items` dan `payroll_item_components` adalah tabel statis historis. Jika logika *Generate Payroll* di-*rewrite* di Golang, output kalkulasinya wajib mengikuti skema ini agar riwayat slip gaji lama tidak rusak.
