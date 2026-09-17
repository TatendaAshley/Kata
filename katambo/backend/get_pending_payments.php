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

$status = $_GET['status'] ?? 'Pending';
$search = $_GET['search'] ?? '';
$method = $_GET['method'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$where = [];
$params = [];

if ($status !== 'all') {
    $where[] = "ph.approval_status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $where[] = "(s.student_name LIKE ? OR s.roll_number LIKE ? OR ph.receipt_no LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

if ($method !== 'all' && !empty($method)) {
    $where[] = "ph.payment_method = ?";
    $params[] = $method;
}

if (!empty($date_from)) {
    $where[] = "DATE(ph.payment_date) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $where[] = "DATE(ph.payment_date) <= ?";
    $params[] = $date_to;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "SELECT 
            ph.payment_id,
            ph.fee_id,
            ph.student_id,
            s.student_name,
            s.roll_number,
            s.class,
            ft.fee_name AS fee_type,
            ph.amount_paid,
            ph.payment_date,
            ph.payment_method,
            ph.receipt_no,
            ph.notes,
            ph.approval_status,
            ph.approved_by,
            ph.approved_at,
            ph.rejection_reason,
            sf.total_amount,
            sf.amount_paid AS total_paid
        FROM payment_history ph
        JOIN students s ON ph.student_id = s.student_id
        JOIN student_fees sf ON ph.fee_id = sf.fee_id
        JOIN fee_types ft ON sf.fee_type_id = ft.fee_type_id
        $whereSQL
        ORDER BY ph.payment_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get counts for summary
$countSQL = "SELECT 
                SUM(CASE WHEN approval_status = 'Pending' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN approval_status = 'Approved' AND DATE(approved_at) = CURDATE() THEN 1 ELSE 0 END) AS approved_today,
                SUM(CASE WHEN approval_status = 'Rejected' AND DATE(approved_at) = CURDATE() THEN 1 ELSE 0 END) AS rejected_today,
                COALESCE(SUM(CASE WHEN approval_status = 'Pending' THEN amount_paid ELSE 0 END), 0) AS pending_amount
            FROM payment_history";
$countStmt = $pdo->query($countSQL);
$counts = $countStmt->fetch(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'payments' => $payments,
    'counts' => $counts
]);
?>