# PRD Desain: TransparaKas

**Sistem Manajemen Keuangan Organisasi Mahasiswa dengan Audit Trail Berbasis Web**, versi multi-tenant (satu akun satu organisasi).

> Dokumen ini adalah acuan untuk membuat desain UI di Stitch sebelum coding Laravel. Fokusnya pada layar, alur, komponen, aturan sistem, dan prompt siap pakai.

## Daftar isi

1. [Ringkasan produk](#1-ringkasan-produk)
2. [Tujuan desain](#2-tujuan-desain)
3. [Konsep multi-tenant](#3-konsep-multi-tenant)
4. [Persona dan peran](#4-persona-dan-peran)
5. [Daftar layar](#5-daftar-layar)
6. [Alur pengguna utama](#6-alur-pengguna-utama)
7. [Navigasi dan hak akses menu](#7-navigasi-dan-hak-akses-menu)
8. [Aturan dan batasan sistem](#8-aturan-dan-batasan-sistem)
9. [Design system](#9-design-system)
10. [Komponen UI](#10-komponen-ui)
11. [Spesifikasi isi per layar](#11-spesifikasi-isi-per-layar)
12. [Responsif](#12-responsif)
13. [Aksesibilitas dan keamanan pada UI](#13-aksesibilitas-dan-keamanan-pada-ui)
14. [Prompt untuk Stitch](#14-prompt-untuk-stitch)
15. [Urutan pengerjaan di Stitch](#15-urutan-pengerjaan-di-stitch)
16. [Kriteria desain selesai](#16-kriteria-desain-selesai)
17. [Dari desain ke implementasi Laravel](#17-dari-desain-ke-implementasi-laravel)

---

## 1. Ringkasan produk

TransparaKas adalah aplikasi web untuk mencatat kas organisasi mahasiswa: pemasukan, pengeluaran, iuran anggota, anggaran kegiatan, dan laporan keuangan. Satu aplikasi dipakai banyak organisasi (multi-tenant), tetapi data setiap organisasi terpisah total.

**Nilai pembeda:**

- Setiap perubahan transaksi tercatat di audit log (siapa, kapan, nilai lama dan baru), sehingga transparan dan sulit dimanipulasi.
- Isolasi data antar organisasi, sehingga nama organisasi, anggota, dan keuangannya tidak terlihat oleh organisasi lain.

| Aspek | Keterangan |
|---|---|
| Platform | Web responsif (desktop utama, mobile tetap nyaman dipakai) |
| Bahasa antarmuka | Indonesia |
| Stack implementasi | Laravel + Blade + Tailwind + Chart.js |
| Catatan desain | Mudah diterjemahkan ke Tailwind (hindari efek yang sulit dibuat dengan utility class) |

## 2. Tujuan desain

1. Terlihat tepercaya dan profesional, karena ini aplikasi keuangan.
2. Angka mudah dibaca: saldo, pemasukan, dan pengeluaran langsung terlihat.
3. Setiap peran hanya melihat menu yang relevan baginya.
4. Aksi sensitif (hapus, batalkan, verifikasi, cabut kode undangan, nonaktifkan anggota) jelas dan butuh konfirmasi.
5. Konsisten, sehingga cukup 1 sistem komponen untuk semua layar.
6. Pengguna selalu tahu sedang berada di organisasi mana (nama organisasi dan badge peran tampil di header).

**Di luar cakupan desain tahap ini:** integrasi pembayaran, notifikasi email/WhatsApp, aplikasi mobile native, pengalih organisasi (satu akun lebih dari satu organisasi).

## 3. Konsep multi-tenant

### 3.1 Cara kerja pendaftaran

Saat mendaftar, pengguna memilih salah satu jalur:

- **Buat organisasi baru:** isi nama instansi/ormawa, lalu pendaftar otomatis menjadi Admin Organisasi. Sistem membuatkan kode undangan.
- **Gabung organisasi yang sudah ada:** masukkan kode undangan atau buka tautan undangan. Permintaan masuk disetujui Admin Organisasi, lalu pengguna menjadi Anggota.

Organisasi tidak bisa dicari publik. Bergabung hanya lewat kode undangan, supaya nama organisasi dan anggotanya tidak terbuka untuk orang luar.

### 3.2 Keputusan: satu akun satu organisasi

Untuk versi tugas ini, satu akun hanya boleh tergabung di satu organisasi. Konsekuensi untuk desain: tidak ada menu pengalih organisasi di header. Struktur database (tabel `memberships`) sudah siap dikembangkan ke multi-organisasi nanti, sehingga menu pengalih bisa ditambahkan tanpa mengubah skema.

### 3.3 Aturan kode undangan

- Dibuat acak, tidak berurutan, dan tidak bisa ditebak.
- Punya masa berlaku (kedaluwarsa) dan bisa dicabut atau dibuat ulang oleh Admin Organisasi.
- Punya peran default (Anggota) yang bisa diubah Admin Organisasi saat menyetujui.
- Pesan error tidak membocorkan alasan: cukup "Kode undangan tidak valid atau sudah kedaluwarsa".
- Percobaan memasukkan kode dibatasi (rate limiting), dengan tampilan akun terkunci sementara.

### 3.4 Aturan isolasi data

- Semua data keuangan (transaksi, kategori, iuran, anggaran, audit log) milik satu organisasi dan hanya terlihat oleh anggotanya.
- Audit log terpisah per organisasi.
- File bukti disimpan per organisasi dan hanya bisa diunduh lewat tautan yang dicek hak aksesnya.
- Peran melekat pada keanggotaan di organisasi, bukan pada akun.
- Super Admin platform (opsional) hanya mengelola platform dan status organisasi, tanpa akses ke data keuangan organisasi.

## 4. Persona dan peran

| Peran | Siapa | Cakupan | Kebutuhan utama di UI |
|---|---|---|---|
| Super Admin platform (opsional) | Pengelola platform | Seluruh platform, tanpa data keuangan | Lihat daftar organisasi, aktifkan atau nonaktifkan organisasi |
| Admin Organisasi | Pembuat atau pengelola organisasi | Organisasinya saja | Kelola anggota dan peran, kode undangan, persetujuan gabung, lihat audit log |
| Bendahara | Pengelola kas | Organisasinya saja | Input transaksi cepat, verifikasi iuran, ekspor laporan |
| Auditor/Ketua | Pengawas | Organisasinya saja | Lihat semua transaksi dan audit log, menandai transaksi mencurigakan |
| Anggota | Anggota ormawa | Organisasinya saja | Lihat saldo organisasi, tagihan iuran sendiri, upload bukti bayar |

> **Catatan:** pendaftar yang belum disetujui (menunggu persetujuan) belum punya peran dan hanya melihat layar status.

## 5. Daftar layar

Total 23 layar. Prioritas: **P1** wajib dibuat dulu, **P2** menyusul, **P3** opsional. Layar bertanda *(baru)* atau *(diubah)* adalah dampak konsep multi-tenant.

| No | Layar | Peran | Prioritas |
|---:|---|---|:---:|
| 1 | Login | Semua | P1 |
| 2 | Pendaftaran: pilih jalur Buat atau Gabung *(diubah)* | Calon pengguna | P1 |
| 3 | Buat Organisasi / onboarding, menampilkan kode undangan *(baru)* | Calon Admin Organisasi | P1 |
| 4 | Gabung dengan Kode Undangan *(baru)* | Calon anggota | P1 |
| 5 | Status Menunggu Persetujuan *(baru)* | Calon anggota | P1 |
| 6 | Dashboard Bendahara/Auditor | Bendahara, Auditor | P1 |
| 7 | Daftar Transaksi (filter, cari, tabel) | Bendahara, Auditor | P1 |
| 8 | Form Tambah/Ubah Transaksi | Bendahara | P1 |
| 9 | Detail Transaksi (termasuk riwayat perubahan) | Bendahara, Auditor | P1 |
| 10 | Laporan (rekap bulanan, grafik, tombol ekspor PDF/CSV) | Bendahara, Auditor | P1 |
| 11 | Dashboard Anggota | Anggota | P1 |
| 12 | Iuran Saya (daftar tagihan, upload bukti) | Anggota | P2 |
| 13 | Verifikasi Iuran | Bendahara | P2 |
| 14 | Anggaran Kegiatan (progress dan peringatan) | Bendahara, Auditor | P2 |
| 15 | Audit Log (per organisasi) | Auditor, Admin Organisasi | P2 |
| 16 | Kategori | Bendahara | P2 |
| 17 | Anggota Organisasi (menggantikan Manajemen Pengguna) *(diubah)* | Admin Organisasi | P2 |
| 18 | Pengaturan Organisasi dan Kode Undangan *(baru)* | Admin Organisasi | P2 |
| 19 | Persetujuan Permintaan Gabung *(baru)* | Admin Organisasi | P2 |
| 20 | Profil dan Ubah Password | Semua | P2 |
| 21 | Modal konfirmasi pembatalan transaksi (wajib isi alasan) | Bendahara | P2 |
| 22 | Halaman error 403/404 | Semua | P2 |
| 23 | Daftar Organisasi platform *(baru)* | Super Admin | P3 |

## 6. Alur pengguna utama

| Alur | Nama | Langkah |
|:---:|---|---|
| A | Bendahara mencatat pengeluaran | Login → Dashboard → klik Tambah Transaksi → isi form (tanggal, tipe, nominal, kategori, keterangan, unggah nota) → simpan → muncul notifikasi sukses → transaksi tampil di daftar dan tercatat di audit log organisasi. |
| B | Anggota membayar iuran | Login → Dashboard Anggota → Iuran Saya → pilih tagihan → unggah bukti bayar → status menjadi Menunggu Verifikasi → Bendahara memverifikasi → status menjadi Lunas. |
| C | Auditor memeriksa transaksi | Login → Daftar Transaksi → buka Detail → lihat riwayat perubahan (nilai lama vs baru) → tandai mencurigakan dengan catatan. |
| D | Bendahara membatalkan transaksi | Detail Transaksi → klik Batalkan → modal meminta alasan → konfirmasi → transaksi ditandai Dibatalkan (tidak dihapus permanen). |
| E | Admin Organisasi mengelola anggota | Login → Anggota Organisasi → ubah peran atau nonaktifkan akun anggota → muncul konfirmasi. |
| F | Membuat organisasi baru | Buka Pendaftaran → pilih "Buat organisasi baru" → isi data akun dan nama organisasi → daftar → masuk ke layar onboarding yang menampilkan kode undangan beserta tombol salin dan bagikan tautan → lanjut ke Dashboard sebagai Admin Organisasi. |
| G | Bergabung ke organisasi | Buka Pendaftaran atau tautan undangan → pilih "Gabung organisasi" → masukkan kode undangan → daftar → masuk ke layar Menunggu Persetujuan. Setelah disetujui, pengguna masuk ke Dashboard Anggota. Jika ditolak, layar status menampilkan pesan penolakan dan opsi memasukkan kode lain. |
| H | Admin Organisasi menyetujui permintaan gabung | Login → notifikasi atau lencana jumlah permintaan di menu → Persetujuan Permintaan Gabung → pilih Setujui (dengan pilihan peran, default Anggota) atau Tolak → muncul konfirmasi dan toast. |
| I | Admin Organisasi mengelola kode undangan | Pengaturan Organisasi → lihat kode aktif dan masa berlakunya → buat kode baru atau cabut kode lama lewat modal konfirmasi. |
| J | Super Admin menonaktifkan organisasi (opsional) | Login → Daftar Organisasi → pilih organisasi → nonaktifkan lewat modal konfirmasi. Super Admin tidak melihat data keuangan organisasi. |

## 7. Navigasi dan hak akses menu

Sidebar di desktop, menu hamburger di mobile. Header menampilkan nama organisasi dan badge peran supaya jelas siapa yang login dan di organisasi mana.

| Menu | Super Admin | Admin Org | Bendahara | Auditor | Anggota |
|---|:---:|:---:|:---:|:---:|:---:|
| Dashboard | Tidak | Ya (versi admin) | Ya | Ya | Ya (versi anggota) |
| Transaksi | Tidak | Tidak | Ya (CRUD) | Ya (baca) | Tidak |
| Iuran | Tidak | Tidak | Ya (verifikasi) | Ya (baca) | Ya (milik sendiri) |
| Anggaran | Tidak | Tidak | Ya | Ya (baca) | Tidak |
| Laporan | Tidak | Tidak | Ya | Ya | Ringkasan publik |
| Audit Log | Tidak | Ya | Tidak | Ya | Tidak |
| Kategori | Tidak | Tidak | Ya | Tidak | Tidak |
| Anggota Organisasi | Tidak | Ya | Tidak | Tidak | Tidak |
| Pengaturan Organisasi (kode undangan, persetujuan gabung) | Tidak | Ya | Tidak | Tidak | Tidak |
| Daftar Organisasi (platform) | Ya | Tidak | Tidak | Tidak | Tidak |

Dashboard versi admin berisi jumlah anggota, permintaan gabung yang menunggu, status kode undangan aktif, dan aktivitas terbaru dari audit log, tanpa angka keuangan rinci.

## 8. Aturan dan batasan sistem

Bagian ini merangkum di satu tempat **apa yang boleh dan tidak boleh dilakukan** oleh sistem dan setiap peran. Gunanya dua: sebagai acuan laporan, dan sebagai bahan skenario uji keamanan. Setiap aturan diberi kode (T = tidak boleh, S = boleh dengan syarat) agar mudah dirujuk di dokumen pengujian.

Aturan dibagi menjadi tiga tingkat:

1. **Tidak boleh** – dilarang untuk siapa pun yang disebut, tanpa pengecualian.
2. **Boleh dengan syarat** – diizinkan hanya jika syaratnya terpenuhi.
3. **Boleh bebas sesuai peran** – semua yang tercantum di tabel hak akses menu (bagian 7).

> [!IMPORTANT]
> Semua aturan di bawah wajib ditegakkan di **server** (Policy Laravel, Global Scope, validasi Form Request). Menyembunyikan tombol di UI hanya pelengkap, bukan kontrol akses.

### 8.1 Tidak boleh

| Kode | Aturan | Berlaku untuk | Perilaku sistem yang diharapkan | Skenario uji keamanan |
|:---:|---|---|---|---|
| T-01 | Menghapus transaksi secara permanen | Semua peran | Tidak ada tombol maupun route hapus permanen. Yang tersedia hanya pembatalan (status Dibatalkan, lihat S-01). | Kirim request hapus langsung ke endpoint transaksi; harus ditolak dan data tetap ada. |
| T-02 | Mengubah atau menghapus audit log | Semua peran, termasuk Admin Organisasi dan Auditor | Audit log hanya-baca: tidak ada tombol ubah/hapus dan tidak ada route untuk itu. | Kirim request ubah/hapus ke entri audit log; harus ditolak dan entri tidak berubah. |
| T-03 | Melihat data organisasi lain | Semua peran | Query otomatis dibatasi `org_id`. Akses ke data organisasi lain dijawab 404, sama dengan data yang tidak ada (lihat 11.19). | IDOR lintas organisasi: pengguna organisasi A membuka `/transactions/15` milik organisasi B. Uji juga untuk iuran, anggaran, kategori, audit log, dan file bukti. |
| T-04 | Super Admin membuka data keuangan organisasi | Super Admin | Tidak ada menu atau tautan ke data keuangan, dan tidak ada policy yang mengizinkan Super Admin membacanya. | Login sebagai Super Admin, buka URL transaksi/iuran/laporan/audit log organisasi secara langsung; harus ditolak. |
| T-05 | Menebak kode undangan | Calon anggota / pihak luar | Kode acak dan tidak berurutan, ada masa berlaku, rate limiting, lalu terkunci sementara. Pesan error generik: "Kode undangan tidak valid atau sudah kedaluwarsa". | Coba banyak kode secara berurutan; harus terkunci setelah batas percobaan, dan pesan tidak membedakan kode salah, kedaluwarsa, atau dicabut. |
| T-06 | Menampilkan nama organisasi sebelum permintaan gabung terkirim | Sistem (layar Gabung) | Nama organisasi hanya muncul setelah permintaan gabung berhasil dikirim. | Masukkan kode valid dan tidak valid; sebelum kirim, tidak ada respons (UI maupun API) yang memuat nama organisasi. |
| T-07 | Admin Organisasi menurunkan atau menonaktifkan dirinya sendiri bila ia satu-satunya admin | Admin Organisasi | Aksi ditolak dan tampil peringatan (lihat 11.16). | Dengan satu admin, kirim request ubah peran/nonaktifkan untuk diri sendiri; harus ditolak dan peran tetap Admin Organisasi. |

### 8.2 Boleh dengan syarat

| Kode | Aksi | Peran | Syarat | Skenario uji keamanan |
|:---:|---|---|---|---|
| S-01 | Membatalkan transaksi | Bendahara | Alasan wajib diisi, lewat modal konfirmasi. Transaksi ditandai Dibatalkan, tidak dihapus permanen, dan tercatat di audit log. | Batalkan tanpa alasan; harus ditolak validasi. Batalkan dengan peran lain (Auditor, Anggota); harus ditolak. |
| S-02 | Mengubah transaksi | Bendahara | Setiap perubahan tercatat di audit log: siapa, kapan, nilai lama, dan nilai baru. Riwayat tampil di Detail Transaksi. | Ubah nominal lalu periksa audit log; harus ada nilai lama dan baru. Ubah dengan peran lain; harus ditolak. |
| S-03 | Menolak iuran | Bendahara | Alasan penolakan wajib diisi. | Tolak tanpa alasan; harus ditolak validasi. Tolak dengan peran lain; harus ditolak. |
| S-04 | Mencabut kode undangan | Admin Organisasi | Lewat modal konfirmasi yang menjelaskan kode lama tidak berlaku lagi. Tercatat di audit log. | Pakai kode yang sudah dicabut untuk bergabung; harus muncul pesan generik. Cabut dengan peran lain; harus ditolak. |
| S-05 | Melihat detail transaksi per baris | Anggota | Hanya bila kebijakan keterlihatan organisasi diatur ke "detail" oleh Admin Organisasi (Pengaturan Organisasi, 11.17). Jika "ringkasan saja", anggota hanya melihat ringkasan publik. | Dengan kebijakan "ringkasan saja", anggota membuka URL detail transaksi secara langsung; harus ditolak. |

### 8.3 Boleh bebas sesuai peran

Semua aksi yang tercantum di tabel hak akses menu (bagian 7) boleh dilakukan sesuai kolom perannya, tanpa syarat tambahan selain akun sudah login, keanggotaannya aktif, dan data yang diakses milik organisasinya sendiri. Aksi di luar tabel tersebut dianggap tidak diizinkan.

### 8.4 Ringkasan per peran

| Peran | Boleh | Tidak boleh |
|---|---|---|
| Super Admin | Melihat daftar organisasi; mengaktifkan atau menonaktifkan organisasi. | Membuka transaksi, iuran, anggaran, laporan, kategori, atau audit log organisasi mana pun (T-04). |
| Admin Organisasi | Mengelola anggota dan peran; menyetujui/menolak permintaan gabung; membuat dan mencabut kode undangan (S-04); mengatur kebijakan keterlihatan transaksi; melihat audit log; dashboard versi admin. | Mengelola transaksi, iuran, anggaran, laporan, dan kategori; melihat angka keuangan rinci; mengubah/menghapus audit log (T-02); menurunkan atau menonaktifkan diri bila satu-satunya admin (T-07). |
| Bendahara | CRUD transaksi, dengan ubah (S-02) dan batal (S-01) bersyarat; verifikasi iuran dan menolak dengan alasan (S-03); kelola anggaran dan kategori; laporan dan ekspor. | Menghapus transaksi permanen (T-01); membuka atau mengubah audit log; mengelola anggota dan kode undangan. |
| Auditor/Ketua | Membaca transaksi, iuran, anggaran, laporan, dan audit log; menandai transaksi mencurigakan dengan catatan. | Membuat, mengubah, atau membatalkan transaksi; verifikasi iuran; mengelola kategori; mengubah/menghapus audit log (T-02). |
| Anggota | Dashboard anggota; melihat dan membayar iuran milik sendiri (unggah bukti); ringkasan publik; detail transaksi bila kebijakan mengizinkan (S-05). | Melihat iuran anggota lain; membuka audit log; melihat transaksi per baris tanpa izin kebijakan. |
| Pendaftar menunggu persetujuan | Melihat layar status; membatalkan permintaan; keluar. | Mengakses menu data apa pun. |
| Semua peran | – | Melihat data organisasi lain (T-03). |

## 9. Design system

Nuansa: bersih, tepercaya, modern, sedikit hangat. Mirip dashboard fintech, bukan terlalu korporat kaku.

### 9.1 Warna (usulan, bisa disesuaikan di Stitch)

| Peran warna | Hex | Penggunaan |
|---|---|---|
| Primer | `#0F766E` (teal tua) | Tombol utama, link aktif, sidebar aktif |
| Primer terang | `#CCFBF1` | Latar item aktif, badge |
| Latar halaman | `#F8FAFC` | Background |
| Permukaan | `#FFFFFF` | Kartu, tabel, form |
| Teks utama | `#0F172A` | Judul dan isi |
| Teks sekunder | `#64748B` | Label, keterangan |
| Pemasukan | `#16A34A` | Angka dan badge pemasukan |
| Pengeluaran | `#DC2626` | Angka dan badge pengeluaran |
| Peringatan | `#D97706` | Anggaran hampir habis, menunggu verifikasi, menunggu persetujuan |
| Info | `#2563EB` | Informasi netral |

> Jangan hanya mengandalkan warna untuk makna: pasangkan dengan ikon atau label teks (aksesibilitas).

### 9.2 Tipografi

Inter (atau Plus Jakarta Sans).

| Elemen | Ukuran |
|---|---|
| Judul halaman | 24 px semibold |
| Judul kartu | 16 px semibold |
| Isi | 14 px |
| Angka besar di kartu ringkasan | 28 px bold |

Angka di tabel rata kanan, format rupiah: `Rp 1.250.000`.

### 9.3 Bentuk dan jarak

Sudut membulat 8 px (kartu 12 px), bayangan tipis, jarak berbasis kelipatan 4 px, lebar konten maksimal 1280 px.

## 10. Komponen UI

- **Tombol:** primer, sekunder (outline), bahaya (merah), nonaktif.
- **Input:** teks, angka rupiah, tanggal, dropdown, textarea, unggah file (dengan petunjuk tipe dan ukuran maksimal), pesan error di bawah field.
- **Input kode undangan** *(baru)*: field berformat kode dengan tombol Tempel dan pesan error generik.
- **Kartu kode undangan** *(baru)*: kode, tombol salin, tautan undangan, masa berlaku, tombol Buat Ulang dan Cabut.
- **Pemilih jalur** *(baru)*: dua kartu pilihan besar "Buat organisasi baru" dan "Gabung organisasi".
- **Pengenal organisasi di header** *(baru)*: nama organisasi dan badge peran.
- **Kartu ringkasan:** ikon, label, angka, perubahan dibanding bulan lalu.
- **Tabel data:** header tetap, baris berselang-seling halus, paginasi, kolom aksi, badge status.
- **Badge status:** Lunas, Belum Lunas, Menunggu Verifikasi, Dibatalkan, Ditandai, serta *(baru)* Menunggu Persetujuan, Disetujui, Ditolak, Kode Aktif, Kedaluwarsa, Dicabut, Organisasi Aktif, Organisasi Nonaktif.
- **Badge peran:** Super Admin, Admin Organisasi, Bendahara, Auditor, Anggota.
- **Progress bar anggaran:** hijau di bawah 70 persen, kuning 70 sampai 100 persen, merah jika melebihi.
- **Toast notifikasi** (sukses, error, peringatan).
- **Modal konfirmasi** dengan teks risiko yang jelas.
- **Timeline riwayat perubahan (audit):** waktu, pengguna, aksi, nilai lama menjadi nilai baru.
- **Empty state:** ilustrasi sederhana dan tombol aksi.
- **Skeleton loading** untuk tabel dan kartu.
- **Grafik:** garis (arus kas bulanan), donat (pengeluaran per kategori).

## 11. Spesifikasi isi per layar

### 11.1 Login

Logo dan nama aplikasi, judul "Masuk ke TransparaKas", field email dan password (ada tombol tampilkan password), centang "Ingat saya", tombol Masuk, tautan "Lupa password" dan "Daftar". Layout dua kolom di desktop: kiri form, kanan panel ilustrasi dengan tagline "Kas transparan, organisasi tepercaya".

- **State:** error kredensial salah, akun terkunci sementara (karena rate limiting), loading.
- **Setelah login:** pengguna diarahkan sesuai statusnya: Dashboard sesuai peran, atau layar Menunggu Persetujuan.

### 11.2 Pendaftaran (pilih jalur)

- **Langkah 1:** dua kartu pilihan, "Buat organisasi baru" dan "Gabung organisasi".
- **Langkah 2:** field nama lengkap, email, password, konfirmasi password, dengan indikator kekuatan password dan daftar aturan (minimal 10 karakter, kombinasi huruf dan angka) yang tercentang saat terpenuhi.
  - Jika jalur **Buat**: tambah field nama organisasi dan pesan bahwa pendaftar menjadi Admin Organisasi.
  - Jika jalur **Gabung**: tambah field kode undangan (terisi otomatis bila membuka tautan undangan) dan pesan bahwa permintaan perlu disetujui Admin Organisasi serta peran awal adalah Anggota.

### 11.3 Buat Organisasi / onboarding

Tampil setelah pendaftaran jalur Buat. Pesan sukses, nama organisasi, kartu kode undangan (salin kode, salin tautan, masa berlaku), penjelasan singkat langkah berikutnya (bagikan kode, setujui permintaan, atur peran), tombol "Lanjut ke Dashboard".

Opsional: isi singkat jenis organisasi dan kebijakan keterlihatan transaksi bagi anggota.

### 11.4 Gabung dengan Kode Undangan

Field kode undangan, tombol Kirim Permintaan Gabung, tautan kembali ke login.

- **State:** kode tidak valid atau kedaluwarsa (pesan generik), terlalu banyak percobaan (terkunci sementara), loading, sukses (lanjut ke Menunggu Persetujuan).
- Nama organisasi baru ditampilkan setelah permintaan terkirim, bukan sebelum, agar tidak bisa dipakai menebak organisasi (T-06).

### 11.5 Status Menunggu Persetujuan

Ikon jam, judul "Menunggu persetujuan Admin Organisasi", penjelasan singkat, nama organisasi, waktu permintaan dikirim, tombol Batalkan Permintaan dan Keluar. Tidak ada sidebar menu data.

- **Varian:** Ditolak (pesan dan tombol "Masukkan kode lain"), Organisasi dinonaktifkan.

### 11.6 Dashboard Bendahara/Auditor

- 4 kartu: Saldo Kas, Pemasukan Bulan Ini, Pengeluaran Bulan Ini, Iuran Belum Lunas.
- Grafik garis arus kas 6 bulan terakhir.
- Grafik donat pengeluaran per kategori.
- Tabel 5 transaksi terbaru.
- Panel kecil: anggaran kegiatan yang hampir habis dan iuran menunggu verifikasi.
- Tombol cepat "Tambah Transaksi" (Bendahara).
- Header menampilkan nama organisasi.

### 11.7 Daftar Transaksi

- **Bar filter:** rentang tanggal, tipe, kategori, status, kolom cari.
- **Tabel:** tanggal, keterangan, kategori, tipe (badge), nominal, dibuat oleh, status, aksi (lihat, ubah, batalkan).
- Tombol Tambah Transaksi dan Ekspor CSV.
- Baris dibatalkan tampil dicoret dengan badge abu-abu.

### 11.8 Form Transaksi

Tipe (toggle Pemasukan/Pengeluaran), tanggal, nominal, kategori, kegiatan/anggaran (opsional), keterangan, unggah bukti nota (pratinjau file). Tombol Simpan dan Batal.

- Peringatan inline jika pengeluaran membuat anggaran melebihi batas.
- Dropdown kategori dan anggaran hanya berisi milik organisasi sendiri.

### 11.9 Detail Transaksi

Kartu informasi transaksi dan pratinjau bukti di sisi kiri, timeline riwayat perubahan di kanan.

- **Aksi:** Ubah, Batalkan (Bendahara), Tandai Mencurigakan (Auditor).

### 11.10 Laporan

Pemilih periode (bulan dan tahun), kartu ringkasan, tabel rekap per kategori, grafik arus kas, tombol Ekspor PDF dan Ekspor CSV. Pratinjau gaya lembar laporan untuk PDF dengan nama organisasi pada kop.

### 11.11 Dashboard Anggota

Kartu Saldo Organisasi, status iuran saya (Lunas/Belum), tagihan terdekat dengan tombol Bayar/Unggah Bukti, ringkasan publik pemasukan dan pengeluaran bulan ini. Anggota tidak melihat detail transaksi per baris kecuali kebijakan organisasi mengizinkan (S-05).

### 11.12 Iuran Saya dan Verifikasi Iuran

- **Anggota:** tabel periode, nominal, status, tombol unggah bukti.
- **Bendahara:** antrean verifikasi dengan pratinjau bukti, tombol Setujui dan Tolak (alasan penolakan wajib, S-03).
- Hanya iuran milik organisasi sendiri yang tampil.

### 11.13 Anggaran Kegiatan

Kartu per kegiatan: nama, batas, terpakai, sisa, progress bar berwarna. Tombol Tambah Anggaran.

### 11.14 Audit Log

- **Tabel:** waktu, pengguna, aksi (buat/ubah/batalkan/login, serta aksi organisasi seperti setujui anggota, ubah peran, buat atau cabut kode undangan), entitas, IP, tombol "lihat perubahan" yang membuka panel nilai lama vs baru.
- **Filter:** pengguna, aksi, dan tanggal.
- Log hanya mencakup organisasi yang sedang aktif.
- **Catatan:** log hanya-baca, tidak ada tombol hapus (T-02).

### 11.15 Kategori

Tabel sederhana dengan tipe (pemasukan/pengeluaran) dan tombol tambah/ubah. Kategori milik organisasi masing-masing.

### 11.16 Anggota Organisasi (menggantikan Manajemen Pengguna)

Tabel nama, email, peran (dropdown: Admin Organisasi, Bendahara, Auditor, Anggota), status (Aktif, Nonaktif), tanggal bergabung, aksi nonaktifkan.

- Perubahan peran dan penonaktifan lewat modal konfirmasi.
- Admin Organisasi tidak bisa menurunkan atau menonaktifkan dirinya sendiri bila ia satu-satunya admin (tampilkan peringatan, T-07).

### 11.17 Pengaturan Organisasi dan Kode Undangan

- **Bagian 1:** informasi organisasi (nama, jenis, ubah nama).
- **Bagian 2:** kartu kode undangan (kode, tautan, masa berlaku, peran default, tombol salin, Buat Ulang, Cabut).
- **Bagian 3:** kebijakan keterlihatan transaksi bagi anggota (ringkasan saja atau detail).
- Pencabutan dan pembuatan ulang kode lewat modal konfirmasi yang menjelaskan bahwa kode lama tidak berlaku lagi (S-04).

### 11.18 Persetujuan Permintaan Gabung

Tabel atau daftar kartu: nama, email, waktu permintaan, dropdown peran yang akan diberikan (default Anggota), tombol Setujui dan Tolak (alasan penolakan opsional).

- **Empty state:** "Belum ada permintaan gabung".
- Lencana jumlah permintaan menunggu tampil di menu.

### 11.19 Profil, Modal, Error

- **Profil:** ubah nama, ubah password (dengan password lama), nama organisasi dan peran (hanya-baca), daftar sesi aktif bila memungkinkan.
- **Modal pembatalan:** judul, penjelasan bahwa transaksi tidak dihapus permanen, textarea alasan wajib, tombol Batalkan Transaksi (merah) dan Kembali.
- **Error 403/404:** pesan ramah dan tombol kembali ke dashboard. Akses ke data organisasi lain ditampilkan sebagai 404 yang sama dengan data yang tidak ada, tanpa membocorkan bahwa datanya ada (T-03).

### 11.20 Daftar Organisasi (Super Admin, P3)

Tabel nama organisasi, tanggal dibuat, jumlah anggota, status (Aktif/Nonaktif), aksi aktifkan atau nonaktifkan. Tidak ada tautan ke transaksi, iuran, atau audit log organisasi. Tambahkan catatan: "Data keuangan organisasi tidak dapat diakses dari sini" (T-04).

## 12. Responsif

| Ukuran | Perilaku |
|---|---|
| Desktop (1280 px ke atas) | Sidebar tetap, konten grid 12 kolom |
| Tablet (768 px) | Sidebar bisa dilipat |
| Mobile (375 px) | Menu hamburger, kartu bertumpuk, tabel berubah menjadi daftar kartu atau bisa digulir horizontal |

Buat minimal layar P1 dalam versi desktop dan mobile. Layar pendaftaran, gabung, dan menunggu persetujuan wajib nyaman di mobile karena kode undangan akan sering dibagikan lewat ponsel.

## 13. Aksesibilitas dan keamanan pada UI

- Kontras teks minimal 4.5 banding 1.
- Semua input punya label terlihat; fokus keyboard jelas.
- Pesan error spesifik, tetapi login tidak membocorkan apakah email terdaftar ("Email atau password salah").
- Pesan kode undangan tidak membocorkan alasan ("Kode undangan tidak valid atau sudah kedaluwarsa") dan tidak menampilkan nama organisasi sebelum permintaan terkirim.
- Aksi destruktif (batalkan transaksi, cabut kode, nonaktifkan anggota atau organisasi) selalu lewat modal konfirmasi.
- Field password bertipe tersembunyi dengan opsi tampilkan.
- Elemen yang tidak boleh diakses peran tertentu tidak ditampilkan, tetapi ingat: penyembunyian di UI bukan pengganti kontrol akses di server (Policy Laravel dan isolasi tenant).
- Indikator di halaman unggah: tipe file diizinkan (JPG, PNG, PDF) dan ukuran maksimal.

## 14. Prompt untuk Stitch

**Cara pakai:** mulai dengan prompt global (14.1) untuk menetapkan gaya, lalu buat layar satu per satu dengan prompt di bawahnya. Pakai mode web/desktop dulu, lalu buat varian mobile. Jika hasil kurang pas, revisi dengan instruksi pendek (misalnya "ganti warna primer menjadi teal", "tabel lebih padat").

### 14.1 Prompt global (gaya)

```text
Design a clean, trustworthy fintech-style web app called "TransparaKas", a multi-tenant financial management system for student organizations. Language: Indonesian. Style: modern, minimal, light theme. Primary color teal #0F766E, page background #F8FAFC, white cards with 12px rounded corners and subtle shadow, font Inter. Green #16A34A for income, red #DC2626 for expenses, amber #D97706 for warnings and pending states. Left sidebar navigation on desktop. The top bar always shows the organization name and a role badge. Currency format "Rp 1.250.000". Use consistent components across all screens.
```

### 14.2 Login

```text
Create the login page for TransparaKas. Two-column layout: left has logo, heading "Masuk ke TransparaKas", email field, password field with show/hide toggle, "Ingat saya" checkbox, primary button "Masuk", links "Lupa password?" and "Belum punya akun? Daftar". Right side has a teal gradient panel with a simple finance illustration and the tagline "Kas transparan, organisasi tepercaya". Include an error alert state "Email atau password salah".
```

### 14.3 Pendaftaran (dua jalur)

```text
Create a two-step registration flow. Step 1: two large selectable cards, "Buat organisasi baru" (for treasurers or organization founders) and "Gabung organisasi" (for members with an invitation code). Step 2: fields nama lengkap, email, password, konfirmasi password, a password strength meter and a checklist of rules (minimal 10 karakter, mengandung huruf dan angka) that turns green when met. If "Buat organisasi baru" was chosen, add a field "Nama organisasi" and a note that the user becomes Admin Organisasi. If "Gabung organisasi" was chosen, add a field "Kode undangan" and a note that the request must be approved by the organization admin. Button "Daftar" and link to login. Same visual style as the login page.
```

### 14.4 Buat Organisasi / onboarding

```text
Create the organization onboarding page shown after creating a new organization. Success message, organization name, an invitation code card with the code in large monospace text, buttons "Salin kode" and "Salin tautan undangan", expiry info, and a short three-step guide (bagikan kode, setujui permintaan, atur peran). Primary button "Lanjut ke Dashboard".
```

### 14.5 Gabung dengan kode undangan dan menunggu persetujuan

```text
Create two screens. (1) Join page: single invitation code input, button "Kirim Permintaan Gabung", generic error "Kode undangan tidak valid atau sudah kedaluwarsa", and a locked state for too many attempts. (2) Pending approval page: clock icon, heading "Menunggu persetujuan Admin Organisasi", organization name, time requested, amber badge "Menunggu Persetujuan", buttons "Batalkan Permintaan" and "Keluar". Add a rejected variant with a button "Masukkan kode lain". No data sidebar.
```

### 14.6 Dashboard Bendahara

```text
Create the treasurer dashboard. Sidebar menu: Dashboard, Transaksi, Iuran, Anggaran, Laporan, Kategori. Top bar with page title, organization name, role badge "Bendahara" and user avatar menu. Content: four summary cards (Saldo Kas, Pemasukan Bulan Ini, Pengeluaran Bulan Ini, Iuran Belum Lunas) with icons, a line chart of cash flow for the last 6 months, a donut chart of expenses by category, a table of the 5 latest transactions with income/expense badges, and a side panel listing budgets nearly exhausted. Primary button "Tambah Transaksi".
```

### 14.7 Daftar Transaksi

```text
Create the transactions list page. Filter bar with date range, type, category, status, and a search box. Data table columns: Tanggal, Keterangan, Kategori, Tipe (colored badge), Nominal (right aligned), Dibuat oleh, Status, Aksi (view, edit, cancel icons). Include pagination, buttons "Tambah Transaksi" and "Ekspor CSV". Show one cancelled row with strikethrough text and a gray "Dibatalkan" badge.
```

### 14.8 Form Transaksi

```text
Create the add transaction form page. Toggle Pemasukan/Pengeluaran, date picker, nominal input with Rp prefix, category dropdown, optional budget/kegiatan dropdown, keterangan textarea, receipt upload area with drag and drop and hint "JPG, PNG, PDF maks 2 MB". Inline amber warning "Pengeluaran ini akan melebihi anggaran kegiatan". Buttons "Simpan" and "Batal".
```

### 14.9 Detail Transaksi dan Audit

```text
Create the transaction detail page. Left: information card (tanggal, tipe, nominal, kategori, keterangan, dibuat oleh) and receipt image preview. Right: vertical timeline titled "Riwayat Perubahan" with entries showing time, user, action, and old value to new value (e.g. Nominal: Rp 500.000 -> Rp 550.000). Action buttons: Ubah, Batalkan (red outline). Add a "Tandai Mencurigakan" button variant for auditor.
```

### 14.10 Laporan

```text
Create the reports page. Month and year selector, summary cards (total pemasukan, pengeluaran, saldo akhir), a table of recap per category, a cash flow bar chart, and buttons "Ekspor PDF" and "Ekspor CSV". Add a section previewing the printable report layout with the organization name in the header.
```

### 14.11 Dashboard Anggota

```text
Create the member dashboard with simplified sidebar (Dashboard, Iuran Saya, Ringkasan Kas). Content: card showing organization balance, card showing my dues status with badge Lunas/Belum Lunas, upcoming bill with button "Unggah Bukti Bayar", and a simple public summary chart of income and expenses this month.
```

### 14.12 Iuran Saya dan Verifikasi

```text
Create two screens. (1) Member dues page: table of periods, nominal, status badge, and upload proof button; upload modal with file dropzone. (2) Treasurer verification queue: list of pending payments with member name, period, amount, proof image preview, and buttons "Setujui" and "Tolak" (rejection requires a reason field).
```

### 14.13 Anggaran Kegiatan

```text
Create the activity budgets page as a grid of cards. Each card: activity name, budget limit, spent, remaining, and a progress bar (green under 70%, amber 70-100%, red over 100%). Button "Tambah Anggaran".
```

### 14.14 Audit Log

```text
Create the audit log page for auditors and organization admins. Filters for user, action, and date range. Table columns: Waktu, Pengguna, Aksi (badge: buat, ubah, batalkan, login, setujui anggota, kode undangan), Entitas, IP Address, and a "Lihat Perubahan" link that opens a side panel comparing old and new values in two columns. Read-only, no delete buttons. Add a small note "Log bersifat hanya-baca".
```

### 14.15 Anggota Organisasi

```text
Create the organization members page for the organization admin. Table columns: Nama, Email, Peran (dropdown: Admin Organisasi, Bendahara, Auditor, Anggota), Status (Aktif/Nonaktif toggle), Tanggal Bergabung, Aksi (nonaktifkan). Show a confirmation modal for role changes and deactivation. Warning banner when the user is the only admin.
```

### 14.16 Pengaturan Organisasi dan persetujuan gabung

```text
Create two screens. (1) Organization settings: section for organization info, an invitation code card with code, shareable link, expiry date, default role, and buttons "Salin", "Buat Ulang" and "Cabut" (with confirmation modal explaining the old code stops working), and a section for member visibility policy. (2) Join request approval page: list of pending requests with name, email, time requested, role dropdown (default Anggota), buttons "Setujui" and "Tolak", and an empty state "Belum ada permintaan gabung".
```

### 14.17 Layar pelengkap

```text
Create: (1) cancel transaction confirmation modal with required reason textarea and red "Batalkan Transaksi" button; (2) 403 and 404 error pages with friendly message and button back to dashboard; (3) profile page with name, change password (old password required), and role/organization shown read-only; (4) optional platform super admin page: table of organizations (nama, tanggal dibuat, jumlah anggota, status, aktifkan/nonaktifkan) with a note "Data keuangan organisasi tidak dapat diakses dari sini". Keep the same style.
```

### 14.18 Versi mobile

```text
Create a mobile version (375px) of the treasurer dashboard, transactions list, registration flow, and pending approval screen: hamburger menu with organization name in the header, stacked summary cards, tables turned into card lists, floating action button for "Tambah Transaksi".
```

## 15. Urutan pengerjaan di Stitch

1. Prompt global dan Login (tetapkan gaya).
2. Pendaftaran dua jalur, Buat Organisasi, Gabung, dan Menunggu Persetujuan.
3. Dashboard Bendahara, Daftar Transaksi, Form, Detail, Laporan.
4. Dashboard Anggota.
5. Versi mobile layar P1.
6. Layar P2, termasuk Anggota Organisasi, Pengaturan Organisasi, dan Persetujuan Gabung.
7. Layar P3 bila waktu cukup.
8. Ekspor desain (kode HTML/Tailwind atau ke Figma) dan simpan sebagai referensi di folder `docs/design/` pada repository.

## 16. Kriteria desain selesai

- [ ] 11 layar P1 selesai (desktop), dan versi mobile untuk layar utama.
- [ ] Warna, font, dan komponen konsisten di semua layar.
- [ ] Semua status penting tergambar: error, kosong, loading, sukses, menunggu persetujuan, ditolak.
- [ ] Setiap peran punya menu yang sesuai tabel bagian 7.
- [ ] Nama organisasi dan badge peran tampil di header semua layar setelah login.
- [ ] Layar Audit Log dan Detail Transaksi menampilkan nilai lama vs baru.
- [ ] Layar kode undangan tidak membocorkan informasi organisasi pada pesan error.
- [ ] Tangkapan layar desain disimpan untuk lampiran laporan.

## 17. Dari desain ke implementasi Laravel

### 17.1 Pemetaan desain ke kode

| Desain | Implementasi |
|---|---|
| Layout sidebar dan header | Komponen Blade `layouts/app.blade.php` |
| Kartu, badge, tombol, modal | Komponen Blade (`<x-card>`, `<x-badge>`, dan lainnya) |
| Grafik | Chart.js |
| Menu per peran | `@can` / pengecekan peran, dengan Policy di server |
| Form dan pesan error | Form Request (validasi) dan `@error` |
| Format Rupiah | Helper `format_rupiah()` |
| Isolasi data organisasi | Global Scope pada model data (`WHERE org_id` otomatis) |
| Kode undangan | Tabel `invitations`, rate limiting pada route gabung |

### 17.2 Perubahan database

- Semua tabel data (`transactions`, `categories`, `dues`, `budgets`, `audit_logs`) wajib punya `org_id`.
- Tabel baru:
  - `organizations`
  - `memberships` (user_id, org_id, role, status)
  - `invitations` (kode, org_id, peran default, kedaluwarsa, dicabut)
  - `join_requests` (user_id, org_id, status)
- Kolom `role` dipindah dari `users` ke `memberships`.
- Pembatasan satu akun satu organisasi: unique index pada `memberships.user_id`. Untuk versi multi-organisasi nanti, indeks diganti menjadi unik pada pasangan `user_id` dan `org_id`.

### 17.3 Dampak ke keamanan

- Isolasi tenant berlapis: Global Scope menambahkan `WHERE org_id` otomatis di setiap query, ditambah Policy yang mengecek keanggotaan.
- Skenario uji IDOR lintas organisasi: Admin organisasi A mencoba membuka `/transactions/15` milik organisasi B, hasilnya harus 403/404.
- File bukti disimpan per organisasi dan hanya bisa diunduh lewat route yang dicek hak aksesnya.
- Kode undangan dibuat acak, bisa kedaluwarsa dan dicabut, serta dibatasi rate limiting agar tidak bisa ditebak.
- Audit log terpisah per organisasi dan hanya-baca.
- Super Admin tidak punya policy untuk membaca data keuangan organisasi.
- Daftar lengkap aturan dan skenario uji keamanan ada di bagian 8 (kode T-01 sampai T-07 dan S-01 sampai S-05).

> **Catatan:** pesan error pada desain hanyalah tampilan. Validasi dan kontrol akses sebenarnya wajib ada di server.
