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

$search = $_GET['search'] ?? '';

$sql = "SELECT 
            ft.fee_type_id,
            ft.fee_name,
            ft.description,
            ft.default_amount,
            COALESCE(ft.is_active, 1) AS is_active,
            ft.created_at,
            (SELECT COUNT(*) FROM student_fees sf WHERE sf.fee_type_id = ft.fee_type_id) AS usage_count
        FROM fee_types ft";

$params = [];
if (!empty($search)) {
    $sql .= " WHERE ft.fee_name LIKE ? OR ft.description LIKE ?";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
}

$sql .= " ORDER BY ft.fee_name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary stats
$stats_sql = "SELECT 
                COUNT(*) AS total_categories,
                COALESCE(SUM(default_amount), 0) AS total_default,
                (SELECT fee_name FROM fee_types 
                 ORDER BY (SELECT COUNT(*) FROM student_fees sf WHERE sf.fee_type_id = fee_types.fee_type_id) DESC 
                 LIMIT 1) AS most_used_name
              FROM fee_types";
$stats = $pdo->query($stats_sql)->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'categories' => $categories,
    'stats' => $stats
]);
?>