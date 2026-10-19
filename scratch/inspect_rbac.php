<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== USER AUDIT & PERMISSIONS ===" . PHP_EOL;
foreach (\App\Models\User::with(['roles', 'branch', 'position'])->get() as $u) {
    $roleSlugs = $u->roles->pluck('slug')->implode(', ');
    $perms = $u->getAllPermissionSlugs(true);
    $canUpload = $u->canUploadDocument() ? 'YES' : 'NO';
    $branchInfo = $u->branch ? "{$u->branch->branch_name} ({$u->branch->branch_type})" : 'NO_BRANCH';
    echo "ID: {$u->id} | {$u->name} | {$u->email}" . PHP_EOL;
    echo "  Role_id: {$u->role_id} | Doc_role: {$u->document_role} | Branch: {$branchInfo}" . PHP_EOL;
    echo "  Roles in DB: [{$roleSlugs}] | canUploadDocument: {$canUpload}" . PHP_EOL;
    echo "  Total Perms: " . count($perms) . PHP_EOL;
}
