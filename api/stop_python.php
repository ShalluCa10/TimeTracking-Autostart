<?php
header('Content-Type: application/json');

$ch = curl_init('http://127.0.0.1:5000/stop');

curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 5,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);

curl_close($ch);

if ($response === false) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Python could not be reached',
        'python_error' => $curlError !== '' ? $curlError : 'cURL request failed',
    ]);
    exit();
}

$data = json_decode($response, true);

if ($httpCode >= 400 || !is_array($data)) {
    http_response_code($httpCode >= 400 ? $httpCode : 500);
    echo json_encode([
        'success' => false,
        'error' => 'Python returned an error',
        'python_response' => $data,
        'python_raw_response' => $response,
    ]);
    exit();
}

echo json_encode([
    'success' => $data['success'] ?? false,
    'python_response' => $data,
]);
