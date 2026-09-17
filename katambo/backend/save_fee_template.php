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

$template_id = $data['template_id'] ?? 0;
$template_name = $data['template_name'] ?? '';
$academic_year = $data['academic_year'] ?? '';
$month = $data['month'] ?? '';
$fee_type_id = $data['fee_type_id'] ?? 0;
$amount = $data['amount'] ?? 0;

if (!$template_name || !$academic_year || !$month || !$fee_type_id || !$amount) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if ($template_id > 0) {
    $sql = "UPDATE monthly_fee_templates SET template_name=?, academic_year=?, month=?, fee_type_id=?, amount=? WHERE template_id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$template_name, $academic_year, $month, $fee_type_id, $amount, $template_id]);
    echo json_encode(['success' => true, 'message' => 'Template updated successfully']);
} else {
    $sql = "INSERT INTO monthly_fee_templates (template_name, academic_year, month, fee_type_id, amount) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$template_name, $academic_year, $month, $fee_type_id, $amount]);
    echo json_encode(['success' => true, 'message' => 'Template saved successfully']);
}
?>