# TransparaKas

> **Kas Transparan, Organisasi Tepercaya.**  
> Aplikasi manajemen kas dan audit trail multi-tenant untuk organisasi mahasiswa (HIMA, BEM, UKM).

| Informasi | Keterangan |
| :--- | :--- |
| **URL Aplikasi** | `https://transparakas.my.id` |
| **Repository** | [github.com/dhenda-ganteng/transparakas](https://github.com/dhenda-ganteng/transparakas) |
| **Framework** | Laravel 12 · PHP 8.2+ · Tailwind CSS v4 · MySQL |

---

## 1. Deskripsi Aplikasi

**TransparaKas** adalah aplikasi web keuangan organisasi mahasiswa yang dirancang untuk menjaga akuntabilitas pencatatan pemasukan, pengeluaran, iuran anggota, dan anggaran kegiatan.

Setiap perubahan transaksi tercatat otomatis dalam **Audit Log** (pelaku, waktu, serta nilai sebelum dan sesudah perubahan) yang tidak dapat diubah dari antarmuka. Sistem menggunakan arsitektur **multi-tenant** sehingga data keuangan tiap organisasi terisolasi penuh dan satu akun hanya terhubung ke satu organisasi.

---

## 2. Fitur Utama

### Manajemen Keuangan & Pelaporan
- **Transaksi Keuangan:** Pencatatan pemasukan dan pengeluaran per kategori, lengkap dengan pembatalan transaksi disertai alasan wajib.
- **Iuran Anggota:** Pengajuan pembayaran iuran oleh anggota, verifikasi oleh Bendahara, serta pemantauan status lunas/tertunggak.
- **Anggaran Kegiatan:** Perencanaan anggaran dan pemantauan realisasi per acara/kegiatan.
- **Laporan Keuangan:** Ringkasan arus kas bulanan dan visualisasi grafik menggunakan Chart.js.

### Keamanan, Tata Kelola & Multi-Tenant
- **Isolasi Data Organisasi:** Setiap organisasi memiliki ruang data terpisah melalui global scope model.
- **Kontrol Akses Berbasis Peran (RBAC):**
  - `Super Admin Platform`: Mengelola status aktif/nonaktif organisasi tanpa akses ke data keuangan (Aturan T-04).
  - `Admin Organisasi`: Mengelola anggota, kode undangan, persetujuan gabung, kebijakan keterlihatan transaksi, dan pengajuan penonaktifan organisasi.
  - `Bendahara`: Mengelola transaksi, kategori, verifikasi iuran, anggaran, dan laporan.
  - `Auditor / Ketua`: Memantau seluruh transaksi, laporan, dan Audit Log (*read-only*).
  - `Anggota`: Melihat ringkasan kas sesuai kebijakan organisasi dan membayar iuran pribadi.
- **Audit Trail:** Pencatatan otomatis setiap aktivitas penting dan perubahan nilai data.
- **Autentikasi Terproteksi:** Registrasi dengan kode undangan/buat organisasi, pembatasan login (maks. 5 percobaan gagal per menit), serta pengelolaan profil & ubah password.

---

## 3. Teknologi yang Digunakan

| Lapisan | Teknologi |
| :--- | :--- |
| **Backend** | PHP 8.2+, Laravel 12 |
| **Database** | MySQL / MariaDB |
| **Frontend** | Blade Template, Tailwind CSS v4, Vite 7 |
| **Visualisasi** | Chart.js |

---

## 4. Instalasi & Menjalankan Secara Lokal

### Prasyarat
- PHP 8.2 atau lebih baru
- Composer
- Node.js & npm
- MySQL / MariaDB (misalnya melalui XAMPP)

### Langkah Instalasi

1. **Clone repository dan masuk ke direktori proyek:**
   ```bash
   git clone https://github.com/dhenda-ganteng/transparakas.git
   cd transparakas
   ```

2. **Pasang dependensi PHP dan JavaScript:**
   ```bash
   composer install
   npm install
   ```

3. **Salin konfigurasi environment dan buat application key:**
   ```bash
   cp .env.example .env        # Windows PowerShell: Copy-Item .env.example .env
   php artisan key:generate
   ```

4. **Siapkan database:**
   - Buat database kosong di MySQL, misalnya `transparakas`.
   - Sesuaikan pengaturan `DB_*` di file `.env`.

5. **Jalankan migrasi, build aset, dan nyalakan server lokal:**
   ```bash
   php artisan migrate
   npm run build
   php artisan serve
   ```
   Akses aplikasi melalui `http://127.0.0.1:8000`.

6. **Buat akun Super Admin Platform:**
   ```bash
   php artisan make:superadmin
   ```
   > Password wajib minimal 12 karakter dengan kombinasi huruf besar, huruf kecil, angka, dan simbol. Untuk akun organisasi dan pengurus, daftarkan melalui halaman **Daftar** di aplikasi.

---

## 5. Konfigurasi Environment Variable

Salin `.env.example` menjadi `.env` dan isi nilainya secara lokal/server. **Jangan pernah melakukan commit terhadap file `.env`** (sudah tercantum di `.gitignore`).

| Variabel | Fungsi | Catatan Keamanan |
| :--- | :--- | :--- |
| `APP_NAME`, `APP_URL` | Nama dan URL utama aplikasi | Sesuaikan dengan domain deploy |
| `APP_ENV` | Mode lingkungan (`local` / `production`) | Wajib `production` saat live |
| `APP_DEBUG` | Menampilkan detail error | Wajib `false` saat live |
| `APP_KEY` | Kunci enkripsi aplikasi Laravel | Hasilkan dengan `php artisan key:generate` |
| `DB_CONNECTION` | Driver database (`mysql`) | — |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE` | Alamat, port, dan nama database | — |
| `DB_USERNAME`, `DB_PASSWORD` | Kredensial database | Jangan tulis di repository |
| `SESSION_DRIVER`, `CACHE_STORE` | Penyimpanan sesi dan cache (`file` / `database`) | — |
| `SUPERADMIN_EMAIL`, `SUPERADMIN_PASSWORD` | Opsional untuk `db:seed` | Gunakan password kuat min. 12 karakter |

### Perintah Ringkas Deploy Produksi

```bash
php artisan key:generate
php artisan migrate --force
php artisan make:superadmin
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

> `DatabaseSeeder` secara otomatis menolak berjalan saat `APP_ENV=production` dan tidak memuat akun maupun data demo.

---

## 6. Ringkasan Penggunaan AI Agent

Pengembangan proyek ini dibantu oleh **Antigravity (AI agent Google DeepMind)** dengan pendekatan *pair programming*:

- **Alat AI yang Dipakai:** Antigravity (Google DeepMind) di lingkungan editor lokal.
- **Bagian Pekerjaan yang Dibantu:**
  - Merancang struktur aplikasi multi-tenant Laravel 12, migrasi database, RBAC 5 peran, Audit Log, serta 23 layar antarmuka sesuai spesifikasi (`docs/PRD.md`).
  - Menyempurnakan antarmuka (logo bertema uang, halaman login/register, sidebar/topbar, dan mode gelap) serta alur pengajuan penonaktifan organisasi.
  - Melakukan peninjauan keamanan pra-upload (pemindaian secret, `.gitignore`, riwayat git, `SecurityHeaders` middleware, pemisahan kredensial ke `.env`, dan pembuatan command `make:superadmin`).
- **Contoh Prompt Utama:**
  1. *"Bangun aplikasi manajemen kas organisasi mahasiswa multi-tenant (TransparaKas) dengan Laravel 12 sesuai PRD: isolasi data per organisasi, RBAC (Super Admin tanpa akses keuangan, Admin Organisasi, Bendahara, Auditor, Anggota), serta Audit Log yang mencatat nilai lama dan nilai baru setiap perubahan transaksi."*
  2. *"Tambahkan fitur pengajuan penonaktifan organisasi dari Admin Organisasi ke Super Admin Platform dengan alasan wajib dan pencatatan ke Audit Log, tanpa melanggar aturan isolasi data keuangan."*
  3. *"Periksa seluruh file proyek dan riwayat Git sebelum diunggah ke GitHub agar tidak ada secret, kredensial, atau akun demo yang bocor, lalu tambahkan Security Headers standar OWASP."*
- **Cara Peninjauan & Verifikasi:** Seluruh kode hasil AI ditinjau secara manual pada bagian autentikasi, hashing password, otorisasi middleware, global scope tenant, dan query database, lalu diuji menggunakan `php -l`, `php artisan view:cache`, dan audit dependensi.

---

## 7. Hasil Self-Testing Keamanan

### Ringkasan Pemeriksaan & Skenario Uji

| No | Skenario Uji | Cara Pengujian | Hasil | Perbaikan yang Dilakukan |
| :---: | :--- | :--- | :---: | :--- |
| 1 | **SQL Injection & XSS pada form input / URL** | Memasukkan payload `' OR 1=1 --` dan `<script>alert(1)</script>` pada form login, pencarian transaksi, dan input catatan | **Lolos** | Seluruh query menggunakan Eloquent / parameter binding dan output Blade di-escape otomatis (`{{ }}`) |
| 2 | **Kontrol Akses & IDOR antar organisasi/peran** | Mengakses URL `/transactions`, `/audit-logs`, `/organization/settings`, dan `/platform/organizations` memakai akun Anggota atau organisasi berbeda | **Lolos** | Diblokir middleware `member:<role>` (`403`) dan global scope `BelongsToOrganization` (`404`) |
| 3 | **Autentikasi, Password Kuat & Rate Limiting** | Mendaftarkan password lemah (< 10 karakter / tanpa angka) serta mencoba login salah > 5 kali berturut-turut | **Lolos** | Password lemah ditolak validasi; login dibatasi maksimal 5 percobaan gagal/menit via `RateLimiter`; password di-hash bcrypt |
| 4 | **Manajemen Sesi & Security Headers** | Memeriksa header respons HTTP dan atribut cookie sesi (`HttpOnly`, `SameSite`, `Secure` di produksi) | **Lolos** | Ditambahkan middleware `SecurityHeaders` (`X-Frame-Options`, `X-Content-Type-Options`, `CSP`, `Referrer-Policy`, `HSTS`) dan default `SESSION_SECURE_COOKIE` otomatis aktif saat `production` |
| 5 | **Pemindaian Secret & Audit Dependency** | Menjalankan pemindaian kata kunci secret pada file ter-track + riwayat Git serta `composer audit` & `npm audit` | **Lolos** | Password dipindahkan ke `.env` (`.gitignore`), akun demo dihapus dari repo, dan command `make:superadmin` dibuat untuk produksi |
| 6 | **CSV Formula Injection** | Menguji ekspor CSV transaksi & laporan dengan input yang diawali `=`, `+`, `-`, `@` | **Lolos** | Sebelumnya belum disanitasi; kini diperbaiki melalui helper `sanitizeCsvRow()` di `Controller` agar sel berawalan formula dinetralkan dengan prefix `'` |

> Catatan: Setelah aplikasi di-deploy ke domain publik dengan HTTPS aktif, lengkapi tabel dan lampiran laporan dengan tangkapan layar hasil pengecekan `securityheaders.com` / SSL Labs serta hasil `composer audit` dan `npm audit`.
