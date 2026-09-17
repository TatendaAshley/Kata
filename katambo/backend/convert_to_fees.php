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
$monthly_fee_ids = $data['monthly_fee_ids'] ?? [];

if (empty($monthly_fee_ids)) {
    echo json_encode(['success' => false, 'message' => 'No fees selected']);
    exit;
}

try {
    $pdo->beginTransaction();
    $converted = 0;
    
    foreach ($monthly_fee_ids as $monthly_fee_id) {
        // Get monthly fee details
        $sql_get = "SELECT student_id, fee_type_id, amount, academic_year, month FROM monthly_fees WHERE monthly_fee_id = ? AND status = 'Generated'";
        $stmt_get = $pdo->prepare($sql_get);
        $stmt_get->execute([$monthly_fee_id]);
        $monthly_fee = $stmt_get->fetch(PDO::FETCH_ASSOC);
        
        if ($monthly_fee) {
            // Check if fee already exists for this student and fee type (optional: prevent duplicate)
            $sql_check = "SELECT fee_id FROM student_fees WHERE student_id = ? AND fee_type_id = ? AND status NOT IN ('Paid')";
            $stmt_check = $pdo->prepare($sql_check);
            $stmt_check->execute([$monthly_fee['student_id'], $monthly_fee['fee_type_id']]);
            
            if (!$stmt_check->fetch()) {
                // Insert into student_fees
                $sql_insert = "INSERT INTO student_fees (student_id, fee_type_id, total_amount, status) VALUES (?, ?, ?, 'Pending')";
                $stmt_insert = $pdo->prepare($sql_insert);
                $stmt_insert->execute([$monthly_fee['student_id'], $monthly_fee['fee_type_id'], $monthly_fee['amount']]);
                $fee_id = $pdo->lastInsertId();
                
                // Update monthly fee record
                $sql_update = "UPDATE monthly_fees SET status = 'Billed', fee_id = ? WHERE monthly_fee_id = ?";
                $stmt_update = $pdo->prepare($sql_update);
                $stmt_update->execute([$fee_id, $monthly_fee_id]);
                
                $converted++;
            }
        }
    }
    
    $pdo->commit();
    echo json_encode(['success' => true, 'message' => $converted . ' fees converted successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>