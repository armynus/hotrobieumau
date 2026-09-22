<?php

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicFile = dirname(__DIR__, 2).'/public'.$path;
if ($path !== '/' && is_file($publicFile)) {
    return false;
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

Illuminate\Support\Facades\URL::forceRootUrl('http://127.0.0.1:8793');

$user = App\Models\User::query()
    ->with(['branch.parent', 'department', 'position'])
    ->where('status', 'active')
    ->firstOrFail();

Illuminate\Support\Facades\Session::put([
    'user_id' => $user->id,
    'user_name' => $user->name,
    'user_email' => $user->email,
    'user_role' => $user->role_id,
    'UserBranchId' => $user->branch_id,
    'UserBranchName' => $user->branch?->branch_name,
    'user_has_avatar' => (bool) $user->avatar_path,
    'user_avatar_version' => optional($user->updated_at)->timestamp,
]);

echo view('user.profile.show', [
    'user' => $user,
    'errors' => new Illuminate\Support\ViewErrorBag,
])->render();
