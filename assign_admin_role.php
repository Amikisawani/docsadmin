<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Seed roles and permissions first
$seeder = new Database\Seeders\RoleAndPermissionSeeder();
$seeder->run();

// Find user by email
$user = App\Domains\Users\Models\User::where('email', 'kouame@test.ci')->first();
if (!$user) {
    echo "User kouame@test.ci not found. Creating...\n";
    $user = new App\Domains\Users\Models\User;
    $user->name = 'Kouame Yao';
    $user->email = 'kouame@test.ci';
    $user->password = bcrypt('password123');
    $user->save();
    echo "User created with ID: " . $user->id . "\n";
}

// Assign admin role
$user->assignRole('admin');
echo "Role 'admin' assigned to user: " . $user->email . "\n";

// Also create a second admin user
$admin2 = App\Domains\Users\Models\User::where('email', 'admin@adminflow.ci')->first();
if (!$admin2) {
    $admin2 = new App\Domains\Users\Models\User;
    $admin2->name = 'Admin AdminFlow';
    $admin2->email = 'admin@adminflow.ci';
    $admin2->password = bcrypt('admin123');
    $admin2->save();
    $admin2->assignRole('admin');
    echo "Admin user created: admin@adminflow.ci / admin123\n";
}

echo "Done.\n";
