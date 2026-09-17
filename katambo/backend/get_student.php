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

$student_id = $_GET['id'] ?? 0;
if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID required']);
    exit;
}

// Fetch student basic info
$sql = "SELECT * FROM students WHERE student_id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$student_id]);
$student = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$student) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit;
}

// Fetch parent/guardian info
$sql_parent = "SELECT * FROM parent_guardian WHERE student_id = ?";
$stmt_parent = $pdo->prepare($sql_parent);
$stmt_parent->execute([$student_id]);
$parent = $stmt_parent->fetch(PDO::FETCH_ASSOC);
if ($parent) {
    $student['parentType'] = $parent['parent_type'];
    $student['fatherName'] = $parent['father_name'];
    $student['motherName'] = $parent['mother_name'];
    $student['guardianPhone'] = $parent['guardian_phone'];
    $student['guardianEmail'] = $parent['guardian_email'];
}

// Fetch address info
$sql_addr = "SELECT * FROM address WHERE student_id = ?";
$stmt_addr = $pdo->prepare($sql_addr);
$stmt_addr->execute([$student_id]);
$address = $stmt_addr->fetch(PDO::FETCH_ASSOC);
if ($address) {
    $student['permanentAddress'] = $address['permanent_address'];
    $student['currentAddress'] = $address['current_address'];
}

echo json_encode(['success' => true, 'student' => $student]);
?>