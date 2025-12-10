#!/usr/bin/env php
<?php

/*
|--------------------------------------------------------------------------
| Script de Criação de Super Admin
|--------------------------------------------------------------------------
|
| Este script cria um usuário super admin no sistema.
| Uso: php create_super_admin.php
|
*/

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

echo "\n";
echo "=================================================\n";
echo "  Criação de Super Admin\n";
echo "=================================================\n";
echo "\n";

// Solicitar dados do super admin
echo "Digite os dados do Super Admin:\n\n";

echo "Nome: ";
$name = trim(fgets(STDIN));

echo "Email: ";
$email = trim(fgets(STDIN));

echo "Senha: ";
$password = trim(fgets(STDIN));

echo "Telefone (opcional): ";
$phone = trim(fgets(STDIN));

echo "\n";
echo "Confirme os dados:\n";
echo "Nome: $name\n";
echo "Email: $email\n";
echo "Telefone: " . ($phone ?: '(não informado)') . "\n";
echo "\n";
echo "Deseja continuar? (s/n): ";
$confirm = trim(fgets(STDIN));

if (strtolower($confirm) !== 's') {
    echo "\n❌ Operação cancelada.\n\n";
    exit(0);
}

try {
    // Verificar se usuário já existe
    $existingUser = User::where('email', $email)->first();
    
    if ($existingUser) {
        echo "\n⚠️  Usuário com este email já existe!\n";
        echo "Deseja atualizar para super admin? (s/n): ";
        $updateConfirm = trim(fgets(STDIN));
        
        if (strtolower($updateConfirm) !== 's') {
            echo "\n❌ Operação cancelada.\n\n";
            exit(0);
        }
        
        $user = $existingUser;
        $user->name = $name;
        $user->password = Hash::make($password);
        if ($phone) {
            $user->phone = $phone;
        }
        $user->is_admin = true;
        $user->save();
        
        echo "\n✅ Usuário atualizado com sucesso!\n";
    } else {
        // Criar novo usuário
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'phone' => $phone ?: null,
            'is_admin' => true,
            'email_verified_at' => now(),
            'locale' => 'en',
        ]);
        
        echo "\n✅ Super Admin criado com sucesso!\n";
    }
    
    // Criar ou obter role de super_admin
    $superAdminRole = Role::firstOrCreate(
        ['name' => 'super_admin'],
        ['guard_name' => 'web']
    );
    
    // Atribuir role ao usuário
    if (!$user->hasRole('super_admin')) {
        $user->assignRole('super_admin');
        echo "✅ Role 'super_admin' atribuída!\n";
    }
    
    echo "\n";
    echo "=================================================\n";
    echo "  Credenciais de Acesso\n";
    echo "=================================================\n";
    echo "Email: $email\n";
    echo "Senha: (a que você digitou)\n";
    echo "\n";
    echo "Acesse o painel em: http://localhost:8000/admin\n";
    echo "\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "\nDetalhes:\n";
    echo $e->getTraceAsString() . "\n\n";
    exit(1);
}
