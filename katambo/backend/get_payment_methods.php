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

$sql = "SELECT DISTINCT payment_method FROM payment_history WHERE payment_method IS NOT NULL ORDER BY payment_method";
$stmt = $pdo->query($sql);
$methods = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['success' => true, 'methods' => $methods]);
?>