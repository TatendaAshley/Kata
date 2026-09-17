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

$student_id = $_GET['student_id'] ?? 0;
if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID required']);
    exit;
}

// Get student details
$sql_student = "SELECT student_name, roll_number, class FROM students WHERE student_id = ?";
$stmt_student = $pdo->prepare($sql_student);
$stmt_student->execute([$student_id]);
$student = $stmt_student->fetch(PDO::FETCH_ASSOC);

// Get all fees and payment history for this student
$sql_fees = "SELECT 
                sf.fee_id,
                ft.fee_name AS fee_type,
                sf.total_amount,
                sf.amount_paid,
                sf.due_date,
                sf.status,
                sf.created_at
            FROM student_fees sf
            JOIN fee_types ft ON sf.fee_type_id = ft.fee_type_id
            WHERE sf.student_id = ?
            ORDER BY sf.fee_id DESC";

$stmt_fees = $pdo->prepare($sql_fees);
$stmt_fees->execute([$student_id]);
$fees = $stmt_fees->fetchAll(PDO::FETCH_ASSOC);

// Get payment history
$sql_payments = "SELECT 
                    ph.payment_id,
                    ph.fee_id,
                    ft.fee_name AS fee_type,
                    ph.amount_paid,
                    ph.payment_date,
                    ph.payment_method,
                    ph.receipt_no,
                    ph.notes
                FROM payment_history ph
                JOIN student_fees sf ON ph.fee_id = sf.fee_id
                JOIN fee_types ft ON sf.fee_type_id = ft.fee_type_id
                WHERE ph.student_id = ?
                ORDER BY ph.payment_date DESC";

$stmt_payments = $pdo->prepare($sql_payments);
$stmt_payments->execute([$student_id]);
$payments = $stmt_payments->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'student' => $student,
    'fees' => $fees,
    'payments' => $payments
]);
?>