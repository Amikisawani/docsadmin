<?php
// Crée un utilisateur de test avec le rôle "directeur" (autorisé à signer).
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Domains\Users\Models\User;
use Spatie\Permission\Models\Role;

// S'assurer que le rôle existe
Role::firstOrCreate(['name' => 'directeur', 'guard_name' => 'web']);

$email = 'test.directeur@adminflow.ci';
$user = User::firstOrCreate(
    ['email' => $email],
    [
        'name' => 'Directeur Test',
        'password' => bcrypt('Test@123456'),
        'is_active' => true,
        'status' => 'active',
        'job_title' => 'Directeur de test',
    ]
);

if (!$user->hasRole('directeur')) {
    $user->assignRole('directeur');
}

echo "=== UTILISATEUR DE TEST ===\n";
echo "Email   : " . $user->email . "\n";
echo "Mot de passe : Test@123456\n";
echo "Rôles   : " . $user->getRoleNames()->implode(', ') . "\n";
echo "Peut signer : " . ($user->canSignDocuments() ? 'OUI' : 'NON') . "\n";
echo "ID      : " . $user->id . "\n";

