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
$payment_ids = $data['payment_ids'] ?? [];
$action = $data['action'] ?? ''; // 'approve' or 'reject'
$reason = $data['reason'] ?? null;
$approved_by = $data['approved_by'] ?? 'Admin';

if (empty($payment_ids) || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    $pdo->beginTransaction();
    $processed = 0;

    foreach ($payment_ids as $payment_id) {
        // Get payment details
        $get_sql = "SELECT fee_id, student_id, amount_paid FROM payment_history WHERE payment_id = ? AND approval_status = 'Pending'";
        $get_stmt = $pdo->prepare($get_sql);
        $get_stmt->execute([$payment_id]);
        $payment = $get_stmt->fetch(PDO::FETCH_ASSOC);

        if (!$payment) continue;

        if ($action === 'approve') {
            // Update payment_history
            $update_sql = "UPDATE payment_history 
                          SET approval_status = 'Approved', approved_by = ?, approved_at = NOW() 
                          WHERE payment_id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$approved_by, $payment_id]);

            // Update student_fees: add the amount to amount_paid and update status
            $fee_sql = "UPDATE student_fees 
                        SET amount_paid = amount_paid + ? 
                        WHERE fee_id = ?";
            $fee_stmt = $pdo->prepare($fee_sql);
            $fee_stmt->execute([$payment['amount_paid'], $payment['fee_id']]);

            // Recalculate status
            $status_sql = "UPDATE student_fees 
                          SET status = CASE 
                              WHEN amount_paid >= total_amount THEN 'Paid'
                              WHEN amount_paid > 0 THEN 'Partial'
                              ELSE 'Pending'
                          END
                          WHERE fee_id = ?";
            $status_stmt = $pdo->prepare($status_sql);
            $status_stmt->execute([$payment['fee_id']]);

            $processed++;
        } else {
            // Reject
            $update_sql = "UPDATE payment_history 
                          SET approval_status = 'Rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? 
                          WHERE payment_id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$approved_by, $reason, $payment_id]);
            $processed++;
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => "$processed payment(s) " . ($action === 'approve' ? 'approved' : 'rejected') . ' successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>