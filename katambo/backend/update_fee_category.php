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
$fee_type_id = intval($data['fee_type_id'] ?? 0);
$fee_name = trim($data['fee_name'] ?? '');
$description = trim($data['description'] ?? '');
$default_amount = floatval($data['default_amount'] ?? 0);

if (!$fee_type_id || empty($fee_name)) {
    echo json_encode(['success' => false, 'message' => 'Fee ID and name are required']);
    exit;
}

if ($default_amount < 0) {
    echo json_encode(['success' => false, 'message' => 'Amount cannot be negative']);
    exit;
}

// Check duplicate name (excluding self)
$check = $pdo->prepare("SELECT fee_type_id FROM fee_types WHERE LOWER(fee_name) = LOWER(?) AND fee_type_id != ?");
$check->execute([$fee_name, $fee_type_id]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Another category with this name already exists']);
    exit;
}

$sql = "UPDATE fee_types SET fee_name = ?, description = ?, default_amount = ? WHERE fee_type_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$fee_name, $description, $default_amount, $fee_type_id]);

echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
?>