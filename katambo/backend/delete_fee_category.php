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
$force = !empty($data['force']);

if (!$fee_type_id) {
    echo json_encode(['success' => false, 'message' => 'Fee ID required']);
    exit;
}

// Check if it's in use
$check = $pdo->prepare("SELECT COUNT(*) AS count FROM student_fees WHERE fee_type_id = ?");
$check->execute([$fee_type_id]);
$usage = $check->fetch(PDO::FETCH_ASSOC);

if ($usage['count'] > 0 && !$force) {
    echo json_encode([
        'success' => false,
        'message' => "This category is used in {$usage['count']} fee record(s). Cannot delete.",
        'in_use' => true,
        'usage_count' => $usage['count']
    ]);
    exit;
}

$sql = "DELETE FROM fee_types WHERE fee_type_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$fee_type_id]);

echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
?>