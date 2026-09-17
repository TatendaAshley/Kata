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

// Fetch students with basic info, plus parent names (optional) and address
$sql = "SELECT 
            s.student_id,
            s.student_name,
            s.roll_number,
            s.class,
            s.section,
            s.status,
            s.picture_path,
            s.email,
            s.phone,
            s.created_at,
            p.father_name,
            p.mother_name,
            p.guardian_phone,
            a.permanent_address,
            a.current_address
        FROM students s
        LEFT JOIN parent_guardian p ON s.student_id = p.student_id
        LEFT JOIN address a ON s.student_id = a.student_id
        ORDER BY s.student_id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'students' => $students]);
?>