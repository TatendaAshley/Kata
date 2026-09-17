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
$student_id = $data['student_id'] ?? 0;
$amount = $data['amount'] ?? 0;
$payment_method = $data['payment_method'] ?? 'Cash';
$receipt_no = $data['receipt_no'] ?? null;
$notes = $data['notes'] ?? null;

if (!$fee_id || !$student_id || !$amount) {
    echo json_encode(['success' => false, 'message' => 'Fee ID, student ID, and amount required']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Insert into payment history
    $sql_history = "INSERT INTO payment_history (fee_id, student_id, amount_paid, payment_method, receipt_no, notes) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_history = $pdo->prepare($sql_history);
    $stmt_history->execute([$fee_id, $student_id, $amount, $payment_method, $receipt_no, $notes]);

    // Update student_fees table
    $sql_update = "UPDATE student_fees SET amount_paid = amount_paid + ? WHERE fee_id = ?";
    $stmt_update = $pdo->prepare($sql_update);
    $stmt_update->execute([$amount, $fee_id]);

    // Get new total and update status
    $sql_status = "SELECT amount_paid, total_amount FROM student_fees WHERE fee_id = ?";
    $stmt_status = $pdo->prepare($sql_status);
    $stmt_status->execute([$fee_id]);
    $fee = $stmt_status->fetch(PDO::FETCH_ASSOC);

    if ($fee['amount_paid'] >= $fee['total_amount']) {
        $new_status = 'Paid';
    } elseif ($fee['amount_paid'] > 0) {
        $new_status = 'Partial';
    } else {
        $new_status = 'Pending';
    }

    $sql_status_update = "UPDATE student_fees SET status = ? WHERE fee_id = ?";
    $stmt_status_update = $pdo->prepare($sql_status_update);
    $stmt_status_update->execute([$new_status, $fee_id]);

    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Payment recorded successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>