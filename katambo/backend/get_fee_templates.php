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
            mft.template_id,
            mft.template_name,
            mft.academic_year,
            mft.month,
            mft.fee_type_id,
            ft.fee_name,
            mft.amount,
            mft.is_active
        FROM monthly_fee_templates mft
        JOIN fee_types ft ON mft.fee_type_id = ft.fee_type_id
        ORDER BY mft.academic_year DESC, FIELD(mft.month, 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December')";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'templates' => $templates]);
?>