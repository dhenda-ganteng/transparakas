<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder produksi-aman: hanya membuat satu akun Super Admin Platform.
 * Tidak ada akun/data demo. Password wajib kuat dan berasal dari .env (tidak ditulis di kode).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SUPERADMIN_EMAIL') ?: 'superadmin@transparakas.id';
        $password = env('SUPERADMIN_PASSWORD');

        if (empty($password) || strlen($password) < 12
            || ! preg_match('/[a-z]/', $password)
            || ! preg_match('/[A-Z]/', $password)
            || ! preg_match('/[0-9]/', $password)) {
            $this->command?->error('SUPERADMIN_PASSWORD di .env wajib diisi (min. 12 karakter, huruf besar, kecil, dan angka). Atau gunakan: php artisan make:superadmin');
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Pengelola Platform TransparaKas',
                'password' => Hash::make($password),
                'is_super_admin' => true,
            ]
        );

        $this->command?->info("Super Admin {$email} siap digunakan.");
    }
}
