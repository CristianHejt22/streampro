<?php

$baseUrl = 'http://127.0.0.1:8080';
$cookieFile = sys_get_temp_dir() . '/cookie_cstreame_staff_' . uniqid() . '.txt';

echo "1. GET /login to fetch CSRF token..." . PHP_EOL;
$ch = curl_init("{$baseUrl}/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: {$httpCode}" . PHP_EOL;

if (!preg_match('/name="_token" value="([^"]+)"/', $response, $matches)) {
    echo "   [ERROR] Could not extract CSRF token from /login" . PHP_EOL;
    exit(1);
}
$csrfToken = $matches[1];

echo "2. POST /login with customer credentials (cliente@cstreame.com)..." . PHP_EOL;
$ch = curl_init("{$baseUrl}/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    '_token' => $csrfToken,
    'email' => 'cliente@cstreame.com',
    'password' => 'cliente123',
]));
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "   HTTP Code: {$httpCode}" . PHP_EOL;
echo "   Final URL: {$effectiveUrl}" . PHP_EOL;

if (strpos($effectiveUrl, '/boveda') !== false && $httpCode === 200) {
    echo "   [SUCCESS] Customer logging in at /login was automatically redirected to /boveda with 200 OK!" . PHP_EOL;
} else {
    echo "   [FAIL] Expected /boveda with 200, got {$effectiveUrl} with {$httpCode}" . PHP_EOL;
}

@unlink($cookieFile);
