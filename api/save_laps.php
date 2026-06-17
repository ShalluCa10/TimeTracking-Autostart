<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
set_exception_handler(function ($e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
});
set_error_handler(function ($errno, $errstr) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $errstr]);
    exit;
});

require_once '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['session_id']) || empty($data['laps'])) {
    echo json_encode(['success' => false, 'message' => 'Missing data']);
    exit;
}

$session_id = intval($data['session_id']);
$laps = $data['laps'];

$conn = getConnection();

$stmt = $conn->prepare("
    INSERT INTO laps (session_id, lap_number, lap_time_ms, lap_time)
    VALUES (?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

foreach ($laps as $lap) {
    $lap_number = intval($lap['lap_number']);
    $lap_time_ms = intval($lap['lap_time_ms']);
    $lap_time = $lap['lap_time'];

    $stmt->bind_param('iiis', $session_id, $lap_number, $lap_time_ms, $lap_time);
    $stmt->execute();
}

$stmt->close();
$conn->close();

echo json_encode(['success' => true, 'message' => 'Laps saved']);
