<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'osas21125@gmail.com')->first();
if ($user) {
    $token = Tymon\JWTAuth\Facades\JWTAuth::fromUser($user);
    echo "Generated Token: " . substr($token, 0, 30) . "...\n";

    // Set token
    Tymon\JWTAuth\Facades\JWTAuth::setToken($token);
    $payload = Tymon\JWTAuth\Facades\JWTAuth::getPayload();
    echo "Sub claim from getPayload: " . $payload->get('sub') . "\n";
    echo "Parsed user ID from token: " . ($payload->get('sub') === $user->user_id ? 'MATCH' : 'MISMATCH') . "\n";
} else {
    echo "User not found\n";
}
