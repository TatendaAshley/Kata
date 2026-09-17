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

$sql = "SELECT * FROM fee_generation_batches ORDER BY generated_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$batches = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'batches' => $batches]);
?>