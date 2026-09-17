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

$student_id = $data['student_id'] ?? 0;
$fee_type_id = $data['fee_type_id'] ?? 0;
$total_amount = $data['total_amount'] ?? 0;
$due_date = $data['due_date'] ?? null;

if (!$student_id || !$fee_type_id || !$total_amount) {
    echo json_encode(['success' => false, 'message' => 'Student, fee type, and amount are required']);
    exit;
}

$sql = "INSERT INTO student_fees (student_id, fee_type_id, total_amount, due_date, status) 
        VALUES (?, ?, ?, ?, 'Pending')";
$stmt = $pdo->prepare($sql);
$stmt->execute([$student_id, $fee_type_id, $total_amount, $due_date]);

echo json_encode(['success' => true, 'message' => 'Fee assigned successfully', 'fee_id' => $pdo->lastInsertId()]);
?>