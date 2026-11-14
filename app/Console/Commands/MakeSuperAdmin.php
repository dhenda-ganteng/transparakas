<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class MakeSuperAdmin extends Command
{
    protected $signature = 'make:superadmin {--name= : Nama lengkap} {--email= : Email login}';

    protected $description = 'Buat atau perbarui akun Super Admin Platform dengan password kuat (aman untuk produksi)';

    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Nama lengkap');
        $email = $this->option('email') ?: $this->ask('Email login');
        $password = $this->secret('Password (min. 12 karakter, huruf besar, kecil, angka & simbol)');
        $confirm = $this->secret('Ulangi password');

        if ($password !== $confirm) {
            $this->error('Konfirmasi password tidak cocok.');
            return self::FAILURE;
        }

        $validator = Validator::make(
            ['name' => $name, 'email' => $email, 'password' => $password],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:12', 'regex:/[a-z]/', 'regex:/[A-Z]/', 'regex:/[0-9]/', 'regex:/[^A-Za-z0-9]/'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'is_super_admin' => true,
            ]
        );

        $this->info("Super Admin {$email} berhasil disimpan.");
        return self::SUCCESS;
    }
}
