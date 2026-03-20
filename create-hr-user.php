<?php
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Delete old HR user if exists
User::where('email', 'hr@poultry.com')->delete();

// Create new HR user
$user = User::create([
    'name' => 'HR Manager',
    'email' => 'hr@poultry.com',
    'password' => Hash::make('HRManager@2026'),
    'role' => 'hr',
    'status' => 'active',
    'email_verified_at' => now(),
]);

echo "✓ HR user created successfully!\n";
echo "Email: " . $user->email . "\n";
echo "Password: HRManager@2026\n";
echo "ID: " . $user->id . "\n";
