<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$user = new App\Domains\Users\Models\User;
$user->name = 'Demo Admin';
$user->email = 'demo@example.com';
$user->password = bcrypt('secret123');
$user->save();
echo $user->id;
