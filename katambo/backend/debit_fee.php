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
$amount = $data['amount'] ?? 0;

if (!$fee_id || !$amount) {
    echo json_encode(['success' => false, 'message' => 'Fee ID and amount required']);
    exit;
}

// Get current fee details
$sql = "SELECT amount_paid, total_amount FROM student_fees WHERE fee_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$fee_id]);
$fee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fee) {
    echo json_encode(['success' => false, 'message' => 'Fee not found']);
    exit;
}

$new_amount_paid = $fee['amount_paid'] + $amount;

// Determine new status
if ($new_amount_paid >= $fee['total_amount']) {
    $status = 'Paid';
} elseif ($new_amount_paid > 0) {
    $status = 'Partial';
} else {
    $status = 'Pending';
}

// Update the fee
$sql_update = "UPDATE student_fees SET amount_paid = ?, status = ? WHERE fee_id = ?";
$stmt_update = $pdo->prepare($sql_update);
$stmt_update->execute([$new_amount_paid, $status, $fee_id]);

echo json_encode(['success' => true, 'message' => 'Payment recorded successfully', 'amount_paid' => $new_amount_paid, 'status' => $status]);
?>