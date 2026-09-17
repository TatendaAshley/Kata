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
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$academic_year = $data['academic_year'] ?? '';
$month = $data['month'] ?? '';
$fee_type_ids = $data['fee_type_ids'] ?? [];
$amount_overrides = $data['amount_overrides'] ?? [];

if (!$academic_year || !$month || empty($fee_type_ids)) {
    echo json_encode(['success' => false, 'message' => 'Academic year, month, and fee types are required']);
    exit;
}

// Debug: Log what we received
error_log("Generating fees for: $month $academic_year");
error_log("Fee types selected: " . print_r($fee_type_ids, true));
error_log("Amount overrides: " . print_r($amount_overrides, true));

try {
    $pdo->beginTransaction();
    
    // Check if fees already generated for this month
    $check_sql = "SELECT COUNT(*) as count FROM monthly_fees WHERE academic_year = ? AND month = ?";
    $check_stmt = $pdo->prepare($check_sql);
    $check_stmt->execute([$academic_year, $month]);
    $existing = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing['count'] > 0) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Fees already generated for ' . $month . ' ' . $academic_year . '. Delete existing records first.']);
        exit;
    }
    
    // Get all active students
    $students_sql = "SELECT student_id, student_name, roll_number FROM students WHERE status = 'Active'";
    $students_stmt = $pdo->prepare($students_sql);
    $students_stmt->execute();
    $students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($students)) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'No active students found']);
        exit;
    }
    
    $total_generated = 0;
    $total_amount = 0;
    $errors = [];
    
    // Generate fees for each student and fee type
    foreach ($students as $student) {
        foreach ($fee_type_ids as $fee_type_id) {
            // Get amount from override or template
            $amount = 0;
            
            // Check if there's an override amount provided
            if (isset($amount_overrides[$fee_type_id]) && $amount_overrides[$fee_type_id] > 0) {
                $amount = floatval($amount_overrides[$fee_type_id]);
                error_log("Using override amount $amount for fee_type_id $fee_type_id");
            } else {
                // Try to get amount from template for this month/year
                $template_sql = "SELECT amount FROM monthly_fee_templates WHERE academic_year = ? AND month = ? AND fee_type_id = ?";
                $template_stmt = $pdo->prepare($template_sql);
                $template_stmt->execute([$academic_year, $month, $fee_type_id]);
                $template = $template_stmt->fetch(PDO::FETCH_ASSOC);
                if ($template) {
                    $amount = floatval($template['amount']);
                    error_log("Using template amount $amount for fee_type_id $fee_type_id");
                } else {
                    // If no template, get default amount from fee_types
                    $default_sql = "SELECT default_amount FROM fee_types WHERE fee_type_id = ?";
                    $default_stmt = $pdo->prepare($default_sql);
                    $default_stmt->execute([$fee_type_id]);
                    $default = $default_stmt->fetch(PDO::FETCH_ASSOC);
                    if ($default) {
                        $amount = floatval($default['default_amount']);
                        error_log("Using default amount $amount for fee_type_id $fee_type_id");
                    }
                }
            }
            
            // Only generate if amount is greater than 0
            if ($amount > 0) {
                $insert_sql = "INSERT INTO monthly_fees (student_id, fee_type_id, academic_year, month, amount, status) VALUES (?, ?, ?, ?, ?, 'Generated')";
                $insert_stmt = $pdo->prepare($insert_sql);
                $insert_stmt->execute([$student['student_id'], $fee_type_id, $academic_year, $month, $amount]);
                $total_generated++;
                $total_amount += $amount;
                error_log("Generated fee for student {$student['student_name']} - amount: $amount");
            } else {
                $errors[] = "Fee type ID $fee_type_id has amount 0 - not generated";
                error_log("Skipped fee_type_id $fee_type_id - amount is 0");
            }
        }
    }
    
    // Only record batch if at least one fee was generated
    if ($total_generated > 0) {
        $batch_sql = "INSERT INTO fee_generation_batches (academic_year, month, total_students, total_amount) VALUES (?, ?, ?, ?)";
        $batch_stmt = $pdo->prepare($batch_sql);
        $batch_stmt->execute([$academic_year, $month, count($students), $total_amount]);
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Generated ' . $total_generated . ' fee records for ' . count($students) . ' students',
            'total_students' => count($students),
            'total_fees' => $total_generated,
            'total_amount' => $total_amount,
            'errors' => $errors
        ]);
    } else {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'No fees were generated. Please ensure fee amounts are set correctly. ' . implode(', ', $errors),
            'errors' => $errors
        ]);
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>