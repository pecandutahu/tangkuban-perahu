# Panduan Deployment Laravel Inertia + Vue (Ubuntu 24.04)

Dokumen ini berisi panduan *step-by-step* bagaimana melakukan *deployment* aplikasi Payroll ini dari nol (*fresh install*) di sebuah VPS (Virtual Private Server) berbasis sistem operasi **Ubuntu 24.04 LTS**.

---

## 1. Persiapan Awal Server
Masuk ke VPS Anda via SSH:
```bash
ssh root@SERVER_IP_ADDRESS
```
Lakukan pembaruan repositori:
```bash
sudo apt update && sudo apt upgrade -y
```

---

## 2. Install Web Server (Nginx)
Kita akan menggunakan Nginx sebagai web server utama (lebih ringan dan disarankan untuk modern web app dibanding Apache).
```bash
sudo apt install nginx -y
```

---

## 3. Pilihan A: Install Database (MySQL / MariaDB)
```bash
sudo apt install mariadb-server mariadb-client -y
```
Amankan instalasi MySQL Anda (Opsional tapi sangat disarankan):
```bash
sudo mysql_secure_installation
```
*(Jawab `Y` untuk sebagian besar pertanyaan yang muncul.)*

**Buat Database & User untuk Aplikasi:**
Masuk ke terminal MySQL:
```bash
sudo mysql -u root -p
```
Jalankan query berikut:
```sql
CREATE DATABASE payroll_db;
CREATE USER 'payroll_user'@'localhost' IDENTIFIED BY 'password_rahasia_anda';
GRANT ALL PRIVILEGES ON payroll_db.* TO 'payroll_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## 3. Pilihan B: Install Database Alternatif (PostgreSQL)
Jika Anda lebih terbiasa atau memilih performa PostgreSQL dibanding MySQL/MariaDB:

Instal PostgreSQL server:
```bash
sudo apt install postgresql postgresql-contrib -y
```

**Buat Database & User untuk Aplikasi:**
Masuk ke terminal default PostgreSQL:
```bash
sudo -u postgres psql
```
Jalankan perintah SQL berikut:
```sql
CREATE DATABASE payroll_db;
CREATE USER payroll_user WITH ENCRYPTED PASSWORD 'password_rahasia_anda';
GRANT ALL PRIVILEGES ON DATABASE payroll_db TO payroll_user;
\q
```

---

## 4. Install PHP (Versi 8.2 / 8.3)
Ubuntu 24.04 mungkin membawa PHP 8.3 secara default. Kita akan menginstall PHP beserta ekstensi yang dibutuhkan Laravel.
```bash
sudo apt install php php-cli php-fpm php-mysql php-pgsql php-xml php-mbstring php-curl php-zip php-bcmath php-intl php-gd unzip git -y
```
Pastikan `php-fpm` berjalan:
```bash
sudo systemctl status php8.3-fpm
# Jika versi PHP Anda bukan 8.3, sesuaikan angkanya, misal php8.2-fpm
```

---

## 5. Install Composer & Node.js
**Install Composer (Package Manager PHP):**
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

**Install Node.js & NPM (Dibutuhkan untuk *build* Vue / Vite):**
Install versi Node.js yang stabil (LTS, misalnya versi 20):
```bash
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs
```

---

## 6. Clone Repositori Git (Pull Kode)
Lokasi standar web directory di Linux adalah `/var/www/`.
```bash
cd /var/www/
# Jika memakai private repo, Anda butuh konfigurasi SSH Key Git terlebih dulu
git clone https://github.com/USERNAME/URL_REPO_ANDA.git payroll-app

cd payroll-app
```

---

## 7. Install Dependencies
**Install Dependensi PHP:**
```bash
composer install --optimize-autoloader --no-dev
```

**Install Dependensi Javascript (Vue) & Build:**
```bash
npm install
npm run build
```
*(Perintah `npm run build` wajib dijalankan di server agar Vue di-_compile_ ke file statis (`public/build`)).*

---

## 8. Konfigurasi Environment (`.env`)
Salin file bawaan:
```bash
cp .env.example .env
```
Edit file `.env`:
```bash
nano .env
```
Ubah isian berikut menyesuaikan *production*:
```env
APP_NAME="Payroll Trucking"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://DOMAIN_ATAU_IP_VPS_ANDA

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=payroll_db
DB_USERNAME=payroll_user
DB_PASSWORD=password_rahasia_anda
```
*(Catatan: Jika Anda menggunakan PostgreSQL (Pilihan B), ubah `DB_CONNECTION` menjadi `pgsql` dan `DB_PORT` menjadi `5432`).*

*(Tekan `Ctrl+O` lalu `Enter` untuk save, dan `Ctrl+X` untuk keluar).*

Generate Kunci Aplikasi (*App Key*):
```bash
php artisan key:generate
```

---

## 9. Menjalankan Migrasi & Seeder Database
```bash
php artisan migrate --force
```
Jika ingin mereset dan memuat data awal (termasuk 500 karyawan dummy):
```bash
php artisan migrate:refresh --seed --force
```

---

## 10. Konfigurasi Folder Permission (Hak Akses)
Agar Nginx (`www-data`) bisa membaca dan menulis _logs / cache / storage / file upload_:
```bash
# Ubah pemilik direktori ke Nginx user
sudo chown -R www-data:www-data /var/www/payroll-app

# Beri hak akses wajar (Folder: 755, File: 644)
sudo find /var/www/payroll-app -type f -exec chmod 644 {} \;
sudo find /var/www/payroll-app -type d -exec chmod 755 {} \;

# Izin menulis eksklusif untuk storage & cache
sudo chmod -R 775 /var/www/payroll-app/storage
sudo chmod -R 775 /var/www/payroll-app/bootstrap/cache
```

---

## 11. Konfigurasi Nginx Server Block (Virtual Host)
Kita perlu memberi tahu web server Nginx cara menyajikan website Anda.
```bash
sudo nano /etc/nginx/sites-available/payroll
```

Isikan konfigurasi di bawah ini (Pastikan `fastcgi_pass` mengarah ke versi PHP Anda, misal `php8.3-fpm.sock` atau `php8.2-fpm.sock`):
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name DOMAIN_ANDA_ATAU_IP.com;
    root /var/www/payroll-app/public; # Harus mengarah ke /public

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock; # Sesuaikan versi PHP!
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Aktifkan konfigurasi dan _restart_ Nginx:
```bash
# Hapus setting default Nginx
sudo rm /etc/nginx/sites-enabled/default

# Aktifkan config yang baru dibuat
sudo ln -s /etc/nginx/sites-available/payroll /etc/nginx/sites-enabled/

# Test syntax konfigurasi
sudo nginx -t

# Restart Web Server
sudo systemctl restart nginx
```

---

## 12. Optimasi Akhir (*Caching*)
Setelah semua siap, jalankan serangkaian perintah Laravel Optimation agar aplikasi berjalan maksimal di *production*:
```bash
cd /var/www/payroll-app
php artisan optimize
php artisan view:cache
php artisan config:cache
php artisan route:cache
```

***🚨 Penting:***
Setiap kali Anda me-*pull* kodingan baru dari Git untuk *update* project, pastikan menjalankan 3 urutan ini:
1. `composer install --optimize-autoloader --no-dev` (Jika ada lib baru)
2. `npm install && npm run build` (Jika ada perubahan Vue/Tailwind)
3. `php artisan optimize:clear` (Membersihkan cache lama)

---
Selamat, aplikasi Payroll Anda sekarang berjalan secara Production di server Anda! Akses lewat IP atau Domain terkait di peramban Anda.


untuk hapus data demo : 
php artisan app:clean-demo
