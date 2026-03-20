<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$farmOwner = \App\Models\FarmOwner::first();

if (!$farmOwner) {
    echo "No farm owner found. Please create a farm owner first.\n";
    exit(1);
}

$employee = \App\Models\Employee::create([
    'farm_owner_id' => $farmOwner->id,
    'first_name' => 'Maria',
    'last_name' => 'Dela Cruz',
    'middle_name' => 'Santos',
    'email' => 'maria@farm.com',
    'phone' => '09101234567',
    'position' => 'Farm Manager',
    'department' => 'Operations',
    'employment_type' => 'Full-time',
    'hire_date' => now(),
    'daily_rate' => 500.00,
    'monthly_salary' => 12000.00,
    'status' => 'active',
]);

echo "✓ Created test employee:\n";
echo "  ID: {$employee->id}\n";
echo "  Name: {$employee->first_name} {$employee->last_name}\n";
echo "  Position: {$employee->position}\n";
echo "  Farm: {$farmOwner->farm_name}\n";
echo "\nYou can now create a payroll record for this employee!\n";
