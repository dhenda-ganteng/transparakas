# TransparaKas

> **Kas Transparan, Organisasi Tepercaya.**  
> Aplikasi manajemen kas dan audit trail multi-tenant untuk organisasi mahasiswa (HIMA, BEM, UKM).

| Informasi | Keterangan |
| :--- | :--- |
| **URL Aplikasi** | `https://GANTI-DENGAN-URL-DEPLOY-ANDA` _(perbarui setelah deploy)_ |
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

- **Perancangan & Implementasi:** Membantu menyusun arsitektur multi-tenant Laravel 12, skema migrasi, RBAC, mekanisme Audit Log, serta 23 layar antarmuka sesuai dokumen spesifikasi (`docs/PRD.md`).
- **Penyempurnaan Antarmuka & Fitur:** Pembuatan komponen logo, halaman login/register, navigasi sidebar/topbar, dan alur pengajuan penonaktifan organisasi.
- **Audit Keamanan Pra-Upload:** Pemindaian secret di kode dan riwayat git, verifikasi `.gitignore`, penghapusan akun/data demo, pemisahan kredensial ke `.env`, serta pembuatan command `make:superadmin`.
- **Validasi Pengembang:** Seluruh struktur, konfigurasi, dan hasil keluaran AI ditinjau ulang serta diperiksa menggunakan `php -l` dan `php artisan view:cache`.

---

## 7. Hasil Self-Testing Keamanan

| Aspek Pemeriksaan | Status | Keterangan |
| :--- | :---: | :--- |
| **Pemindaian Secret** | Lulus | Tidak ada password hardcoded, `APP_KEY`, `DB_PASSWORD`, token GitHub/AWS, atau private key di file ter-track maupun riwayat git |
| **File Terkecuali (`.gitignore`)** | Lulus | `.env`, log, `vendor`, `node_modules`, `public/build`, dan file `.sqlite` terverifikasi diabaikan git |
| **Akun & Data Demo** | Lulus | Dihapus dari database dan kode; seeder diblokir di lingkungan `production` |
| **Penyimpanan Password** | Lulus | Seluruh password di-hash dengan bcrypt; `make:superadmin` mewajibkan kompleksitas tinggi |
| **Proteksi Brute Force** | Lulus | Login dibatasi maksimal 5 percobaan gagal per menit per email dan IP |
| **Otorisasi & Isolasi Tenant** | Lulus | Rute dilindungi middleware peran dan model keuangan menerapkan global scope `BelongsToOrganization` |
| **Batasan Super Admin (T-04)** | Lulus | Super Admin hanya mengelola status organisasi tanpa akses data transaksi keuangan |
| **Proteksi CSRF & XSS** | Lulus | Formulir menggunakan token `@csrf` dan output Blade di-escape otomatis (`{{ }}`) |
| **Pemeriksaan Sintaks & View** | Lulus | Seluruh file PHP lolos `php -l` dan kompilasi template Blade berhasil tanpa error |

> Catatan: Hasil di atas merupakan pemeriksaan statis dan verifikasi fungsional lokal. Pengujian penetrasi dinamis pada URL produksi dapat ditambahkan setelah aplikasi live.
