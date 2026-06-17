<?php
$api = 'https://dreamland-t1ck.onrender.com/v1';
$payload = [
    'name' => 'Joseph Agyenim Boateng',
    'username' => 'boatengkwm',
    'email' => 'boatengkwm@yahoo.com',
    'password' => 'demo123',
    'account_type' => 'creator',
    'device_type' => '3',
];

// OPTIONS preflight
$ch = curl_init("$api/dreamland-auth/register");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'OPTIONS',
    CURLOPT_HTTPHEADER => [
        'Origin: https://dreamland-plum.vercel.app',
        'Access-Control-Request-Method: POST',
        'Access-Control-Request-Headers: content-type',
    ],
    CURLOPT_HEADER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 60,
]);
$raw = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "=== OPTIONS HTTP $code ===\n" . substr($raw, 0, 600) . "\n\n";

$ch = curl_init("$api/dreamland-auth/register");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Origin: https://dreamland-plum.vercel.app',
    ],
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 90,
]);
$raw = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "=== POST register HTTP $code ===\n" . substr($raw, 0, 1500) . "\n";
