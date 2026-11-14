# TransparaKas

**Kas Transparan, Organisasi Tepercaya.** Aplikasi web manajemen kas dan audit trail untuk organisasi mahasiswa (HIMA, BEM, UKM).

- **URL Aplikasi:** `https://GANTI-DENGAN-URL-DEPLOY-ANDA` _(isi setelah deploy)_
- **Repository:** https://github.com/dhenda-ganteng/transparakas

## Deskripsi Aplikasi

TransparaKas membantu organisasi mahasiswa mencatat pemasukan, pengeluaran, iuran anggota, dan anggaran kegiatan secara rapi dan transparan. Setiap perubahan transaksi terekam di **Audit Log** (siapa, kapan, nilai lama vs nilai baru). Data tiap organisasi terpisah (multi-tenant) dan satu akun hanya terhubung ke satu organisasi.

## Fitur

- **Autentikasi:** daftar, login (dibatasi 5 percobaan gagal per menit), profil dan ubah password.
- **Multi-organisasi (tenant):** setiap organisasi memiliki data keuangan sendiri yang terisolasi.
- **Peran (RBAC):** Super Admin Platform, Admin Organisasi, Bendahara, Auditor/Ketua, Anggota.
- **Transaksi keuangan:** pemasukan dan pengeluaran per kategori, pembatalan transaksi dengan alasan wajib.
- **Iuran anggota:** pembayaran, verifikasi bendahara, dan status iuran.
- **Anggaran kegiatan:** rencana anggaran dan realisasi per kegiatan.
- **Laporan keuangan:** ringkasan kas dan grafik.
- **Audit Log:** jejak perubahan data yang tidak dapat dimanipulasi dari antarmuka.
- **Manajemen organisasi:** kode undangan, persetujuan permintaan gabung, pengaturan peran dan status anggota, kebijakan keterlihatan transaksi.
- **Pengajuan penonaktifan organisasi:** Admin Organisasi mengajukan, Super Admin menyetujui atau menolak.
- **Super Admin Platform:** mengelola daftar organisasi tanpa akses ke data keuangan (aturan T-04).

## Teknologi

- PHP 8.2+ dan Laravel 12
- MySQL (dipakai saat pengembangan; query urutan peran memakai fungsi `FIELD()` milik MySQL/MariaDB)
- Blade, Tailwind CSS v4, Vite 7
- Chart.js untuk grafik laporan

## Instalasi dan Menjalankan Secara Lokal

Prasyarat: PHP 8.2+, Composer, Node.js, dan MySQL (mis. XAMPP).

```bash
git clone https://github.com/dhenda-ganteng/transparakas.git
cd transparakas

composer install
npm install

cp .env.example .env        # Windows PowerShell: Copy-Item .env.example .env
php artisan key:generate
```

1. Buat database kosong di MySQL, misalnya `transparakas`.
2. Isi konfigurasi database di `.env` (lihat bagian berikut).
3. Jalankan migrasi dan build aset:

```bash
php artisan migrate
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Buat akun Super Admin (tidak ada akun demo):

```bash
php artisan make:superadmin
```

Password wajib minimal 12 karakter dengan huruf besar, huruf kecil, angka, dan simbol. Organisasi dan admin organisasi dibuat lewat halaman **Daftar**.

## Konfigurasi Environment Variable

Salin `.env.example` menjadi `.env`, lalu isi nilainya sendiri. **Jangan commit `.env`** (sudah masuk `.gitignore`). Nilai rahasia tidak ditulis di repository.

| Variable | Keterangan |
| --- | --- |
| `APP_NAME`, `APP_URL` | Nama dan URL aplikasi |
| `APP_ENV` | `local` untuk pengembangan, `production` untuk server |
| `APP_DEBUG` | `true` hanya di lokal, wajib `false` di produksi |
| `APP_KEY` | Dibuat dengan `php artisan key:generate` |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST`, `DB_PORT`, `DB_DATABASE` | Lokasi dan nama database |
| `DB_USERNAME`, `DB_PASSWORD` | Kredensial database (isi sendiri) |
| `SESSION_DRIVER`, `CACHE_STORE` | `file` atau `database` |
| `SUPERADMIN_EMAIL`, `SUPERADMIN_PASSWORD` | Opsional, untuk `php artisan db:seed` (password kuat, min. 12 karakter) |

Deploy produksi:

```bash
php artisan key:generate
php artisan migrate --force
php artisan make:superadmin
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Seeder otomatis menolak berjalan saat `APP_ENV=production`, dan seeder tidak membuat akun atau data demo.

## Ringkasan Penggunaan AI Agent

Proyek ini dikerjakan dengan bantuan **Antigravity (AI agent Google DeepMind)** dalam pola pair programming:

- Menyusun struktur aplikasi Laravel multi-tenant, RBAC, audit trail, dan 23 layar sesuai PRD (`docs/PRD.md`).
- Membuat migrasi, model, controller, view, tampilan (logo, login, navigasi), dan fitur pengajuan penonaktifan organisasi.
- Melakukan audit sebelum upload: memindai secret, memeriksa `.gitignore`, riwayat git, dan isi staging; menghapus akun/data demo; memindahkan password ke `.env`; membuat command `make:superadmin`.
- Semua keputusan, konfigurasi, dan hasil akhir ditinjau oleh pengembang. Kode hasil AI diperiksa dengan sintaks lint dan kompilasi view sebelum dipakai.

## Hasil Self-Testing Keamanan

Pemeriksaan yang sudah dilakukan:

| Aspek | Hasil |
| --- | --- |
| Secret di repository | Tidak ditemukan (pemindaian `Password123`, `APP_KEY`, `DB_PASSWORD`, token GitHub/AWS, private key) pada file ter-track dan riwayat git |
| `.env`, log, `vendor`, `node_modules`, database sqlite | Diabaikan git (`git check-ignore`) |
| Akun/password demo | Dihapus dari database, kode, dan dokumen; seeder tidak memuat akun demo |
| Password | Disimpan dengan hash (bcrypt); password Super Admin acak dan kuat, berasal dari `.env` |
| Brute force login | Dibatasi 5 percobaan gagal per menit (per email dan IP) |
| Otorisasi | Rute dilindungi middleware peran (`member:super_admin`, `member:admin_organisasi`, dan lainnya) |
| Isolasi data tenant | Global scope `BelongsToOrganization` pada model keuangan |
| Super Admin | Tidak memiliki akses ke data keuangan organisasi (T-04) |
| CSRF dan XSS | Semua form memakai `@csrf`; keluaran Blade di-escape `{{ }}` |
| Kualitas kode | Seluruh file PHP lolos `php -l`; semua view Blade berhasil di-compile |

Catatan: pengujian di atas bersifat pemeriksaan statis dan manual. Uji penetrasi dinamis (misalnya scanner otomatis di URL deploy) belum dijalankan. Tambahkan hasilnya di sini setelah dilakukan.
