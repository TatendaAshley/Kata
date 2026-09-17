<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$host = 'localhost';
$dbname = 'school_fee_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$fee_id = $data['fee_id'] ?? 0;

if (!$fee_id) {
    echo json_encode(['success' => false, 'message' => 'Fee ID required']);
    exit;
}

$sql = "DELETE FROM student_fees WHERE fee_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$fee_id]);

echo json_encode(['success' => true, 'message' => 'Fee deleted successfully']);
?>