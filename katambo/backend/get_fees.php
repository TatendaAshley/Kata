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

$sql = "SELECT 
            sf.fee_id,
            sf.student_id,
            s.student_name,
            s.roll_number,
            s.class,
            ft.fee_name AS fee_type,
            ft.fee_type_id,
            sf.total_amount,
            sf.amount_paid,
            sf.due_date,
            sf.status,
            sf.created_at,
            ROUND((sf.amount_paid / sf.total_amount) * 100, 2) AS payment_progress
        FROM student_fees sf
        JOIN students s ON sf.student_id = s.student_id
        JOIN fee_types ft ON sf.fee_type_id = ft.fee_type_id
        ORDER BY sf.fee_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'fees' => $fees]);
?>