<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CriarSuperAdmin extends Command
{
    protected $signature   = 'app:super-admin
                                {--email=superadmin@lojin.com}
                                {--name=Super Admin}
                                {--password=SuperAdmin@2026}';
    protected $description = 'Cria o usuário Super Admin da plataforma';

    public function handle(): int
    {
        $user = User::updateOrCreate(
            ['email' => $this->option('email')],
            [
                'name'      => $this->option('name'),
                'password'  => Hash::make($this->option('password')),
                'role'      => 'super_admin',
                'tenant_id' => null,
            ]
        );

        $this->info('');
        $this->info('✅ Super Admin ' . ($user->wasRecentlyCreated ? 'criado' : 'atualizado') . '!');
        $this->table(['Campo','Valor'], [
            ['E-mail', $user->email],
            ['Senha',  $this->option('password')],
            ['Role',   $user->role],
        ]);
        $this->warn('⚠  Altere a senha após o primeiro acesso.');
        return self::SUCCESS;
    }
}
