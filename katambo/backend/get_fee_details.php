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

$fee_id = $_GET['id'] ?? 0;
if (!$fee_id) {
    echo json_encode(['success' => false, 'message' => 'Fee ID required']);
    exit;
}

$sql = "SELECT 
            sf.fee_id,
            sf.student_id,
            s.student_name,
            s.roll_number,
            sf.fee_type_id,
            ft.fee_name AS fee_type,
            sf.total_amount,
            sf.amount_paid,
            sf.due_date,
            sf.status,
            ft.default_amount
        FROM student_fees sf
        JOIN students s ON sf.student_id = s.student_id
        JOIN fee_types ft ON sf.fee_type_id = ft.fee_type_id
        WHERE sf.fee_id = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$fee_id]);
$fee = $stmt->fetch(PDO::FETCH_ASSOC);

if ($fee) {
    echo json_encode(['success' => true, 'fee' => $fee]);
} else {
    echo json_encode(['success' => false, 'message' => 'Fee not found']);
}
?>