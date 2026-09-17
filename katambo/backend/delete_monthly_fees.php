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
$academic_year = $data['academic_year'] ?? '';
$month = $data['month'] ?? '';

if (!$academic_year || !$month) {
    echo json_encode(['success' => false, 'message' => 'Academic year and month required']);
    exit;
}

$sql = "DELETE FROM monthly_fees WHERE academic_year = ? AND month = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$academic_year, $month]);

echo json_encode(['success' => true, 'message' => 'Fees deleted for ' . $month . ' ' . $academic_year]);
?>