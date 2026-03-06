# Kamus Tipe Source Komponen Gaji (`payroll_item_components.source`)

Sistem Penggajian ini menggunakan penanda sumber asal atau `source` pada tingkat *Payroll Item Component* untuk melacak dari mana suatu komponen pembiayaan (pendapatan/potongan) itu berasal. Hal ini sangat krusial untuk keperluan **Audit Trail** serta **Custom Sorting Anomali** oleh tim Finance.

Berikut adalah kamus kode *Source* beserta penjelasannya:

| Kode Source | Kepanjangan | Makna / Penjelasan | Kapan Digenerate? |
| :--- | :--- | :--- | :--- |
| **`SYSTEM`** | *System Default* | Komponen gaji standar yang disalin mentah-mentah dari **Template Jabatan**. Nominalnya adalah nilai absolut `default_amount` alias tidak diutak-atik di profil karyawan. | Saat tombol *Generate Payroll* (Sinkronisasi awal sistem). |
| **`OVR_EMP`** | *Override Employee* | Singkatan dari karyawan menimpa sistem. Komponen ini **wajib ada karena terdaftar di Template Jabatan**, namun nilai bawaannya ditimpa (di-override) oleh nominal _Employee Specific Component_ (Komp. Khusus Karyawan). | Saat *Generate Payroll*, jika ditemukan relasi ID komponen di daftar khusus karyawan. |
| **`OVR_ADD`** | *Override Add* | Singkatan dari tambahan di luar sistem. Komponen ini **sama sekali tidak terdaftar di Template Jabatan**, namun secara paksa dilekatkan di profil karyawan. (Contoh: Karyawan X punya Tunjangan Perumahan khusus). | Saat *Generate Payroll*, ketika array sisa dari _Employee Specific Component_ di-inject ke draft. |
| **`IMPORT`** | *CSV Import/Upload* | Komponen khusus yang nilainya di-inject / diganti mendadak di rentang periode draft via antarmuka unggah CSV (Excel) oleh HR/Finance. Ini difungsikan untuk data dinamis layaknya Lembur, Absen Khusus, Insentif dadakan, dll. | Saat proses *Import Variable CSV* dijalankan via Modul Rincian Payroll. |

---

### Pentingnya Pembedaan Ini
Pemecahan label khusus (seperti membedakan `OVR_ADD` dengan `IMPORT`) menjaga integritas audit riwayat:
1. Finance bisa mengetahui apakah `Uang Jalan` tersebut berasal dari kebijakan paten di Profil Karyawan (`OVR_ADD` / `OVR_EMP`) atau sekadar kelebihan manual di bulan tersebut via Excel (`IMPORT`). 
2. Memfasilitasi filter pencarian anomali: Saat layar memisahkan *Sorting Berdasarkan Tambahan Terbesar*, sistem tahu dengan tepat harus mencarikan duit ekstra yang murni disuap via file CSV dengan melacak _tag_ `IMPORT`. 
3. *Regenerate Item*: Mencegah Data Hilang. Saat melakukan Sinkronisasi Ulang, elemen ber-flag `IMPORT` akan tersapu atau bertahan tergantung skema yang dirakit di `VariableImportService` tanpa merusak *Master Data* karyawan bawaannya.
