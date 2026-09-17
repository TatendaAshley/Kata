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
$fee_name = trim($data['fee_name'] ?? '');
$description = trim($data['description'] ?? '');
$default_amount = floatval($data['default_amount'] ?? 0);

if (empty($fee_name)) {
    echo json_encode(['success' => false, 'message' => 'Fee name is required']);
    exit;
}

if ($default_amount < 0) {
    echo json_encode(['success' => false, 'message' => 'Amount cannot be negative']);
    exit;
}

// Check for duplicate name
$check = $pdo->prepare("SELECT fee_type_id FROM fee_types WHERE LOWER(fee_name) = LOWER(?)");
$check->execute([$fee_name]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'A category with this name already exists']);
    exit;
}

$sql = "INSERT INTO fee_types (fee_name, description, default_amount) VALUES (?, ?, ?)";
$stmt = $pdo->prepare($sql);
$stmt->execute([$fee_name, $description, $default_amount]);

echo json_encode([
    'success' => true,
    'message' => 'Category added successfully',
    'fee_type_id' => $pdo->lastInsertId()
]);
?>