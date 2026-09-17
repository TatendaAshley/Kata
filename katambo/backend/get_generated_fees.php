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

$academic_year = $_GET['academic_year'] ?? '';
$month = $_GET['month'] ?? '';

if ($academic_year && $month) {
    $sql = "SELECT 
                mf.monthly_fee_id,
                mf.student_id,
                s.student_name,
                s.roll_number,
                s.class,
                mf.fee_type_id,
                ft.fee_name,
                mf.amount,
                mf.status,
                mf.generated_date
            FROM monthly_fees mf
            JOIN students s ON mf.student_id = s.student_id
            JOIN fee_types ft ON mf.fee_type_id = ft.fee_type_id
            WHERE mf.academic_year = ? AND mf.month = ?
            ORDER BY s.class, s.student_name";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$academic_year, $month]);
} else {
    $sql = "SELECT 
                mf.monthly_fee_id,
                mf.student_id,
                s.student_name,
                s.roll_number,
                s.class,
                mf.fee_type_id,
                ft.fee_name,
                mf.amount,
                mf.status,
                mf.generated_date,
                mf.academic_year,
                mf.month
            FROM monthly_fees mf
            JOIN students s ON mf.student_id = s.student_id
            JOIN fee_types ft ON mf.fee_type_id = ft.fee_type_id
            ORDER BY mf.academic_year DESC, FIELD(mf.month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'), s.student_name
            LIMIT 500";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
}

$fees = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode(['success' => true, 'fees' => $fees]);
?>