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
$fee_type_id = $data['fee_type_id'] ?? 0;
$total_amount = $data['total_amount'] ?? 0;
$due_date = $data['due_date'] ?? null;

if (!$fee_id || !$fee_type_id || !$total_amount) {
    echo json_encode(['success' => false, 'message' => 'Fee ID, fee type, and amount are required']);
    exit;
}

// Get current paid amount to determine new status
$sql_current = "SELECT amount_paid, total_amount FROM student_fees WHERE fee_id = ?";
$stmt_current = $pdo->prepare($sql_current);
$stmt_current->execute([$fee_id]);
$current = $stmt_current->fetch(PDO::FETCH_ASSOC);

// Calculate new status based on amount_paid vs new total_amount
if ($current['amount_paid'] >= $total_amount) {
    $status = 'Paid';
} elseif ($current['amount_paid'] > 0) {
    $status = 'Partial';
} else {
    $status = 'Pending';
}

$sql = "UPDATE student_fees SET fee_type_id = ?, total_amount = ?, due_date = ?, status = ? WHERE fee_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$fee_type_id, $total_amount, $due_date, $status, $fee_id]);

echo json_encode(['success' => true, 'message' => 'Fee updated successfully']);
?>