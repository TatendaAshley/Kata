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

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$student_id = $_POST['student_id'] ?? 0;
if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID required']);
    exit;
}

// Collect data (same names as add_student.php, plus student_id)
$student_name   = $_POST['studentName'] ?? '';
$roll_number    = $_POST['rollNumber'] ?? '';
$associated_id  = $_POST['associatedId'] ?? '';
$email          = $_POST['email'] ?? '';
$phone          = $_POST['phone'] ?? '';
$dob            = $_POST['dob'] ?? null;
$gender         = $_POST['gender'] ?? null;
$academic_year  = $_POST['academicYear'] ?? '';
$class          = $_POST['class'] ?? '';
$section        = $_POST['section'] ?? '';
$status         = $_POST['status'] ?? 'Active';
$advance_payment = $_POST['advancePayment'] ?? 0;
$discount       = $_POST['discount'] ?? 0;
$fee_structure  = $_POST['feeStructure'] ?? '';
$parent_type    = $_POST['parentType'] ?? null;
$father_name    = $_POST['fatherName'] ?? '';
$mother_name    = $_POST['motherName'] ?? '';
$guardian_phone = $_POST['guardianPhone'] ?? '';
$guardian_email = $_POST['guardianEmail'] ?? '';
$permanent_addr = $_POST['permanentAddress'] ?? '';
$current_addr   = $_POST['currentAddress'] ?? '';

// Picture upload (if new picture provided)
$picture_path = null;
if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
    $ext = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
    $new_filename = 'student_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $roll_number) . '.' . $ext;
    $destination = $upload_dir . $new_filename;
    if (move_uploaded_file($_FILES['picture']['tmp_name'], $destination)) {
        $picture_path = 'uploads/' . $new_filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload picture']);
        exit;
    }
}

try {
    $pdo->beginTransaction();

    // Update students table
    if ($picture_path) {
        $sql = "UPDATE students SET 
                student_name=?, roll_number=?, associated_id=?, email=?, phone=?, date_of_birth=?, gender=?,
                picture_path=?, academic_year=?, class=?, section=?, status=?, advance_payment=?, discount=?, fee_structure=?
                WHERE student_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_name, $roll_number, $associated_id, $email, $phone, $dob, $gender,
                       $picture_path, $academic_year, $class, $section, $status, $advance_payment, $discount, $fee_structure, $student_id]);
    } else {
        $sql = "UPDATE students SET 
                student_name=?, roll_number=?, associated_id=?, email=?, phone=?, date_of_birth=?, gender=?,
                academic_year=?, class=?, section=?, status=?, advance_payment=?, discount=?, fee_structure=?
                WHERE student_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$student_name, $roll_number, $associated_id, $email, $phone, $dob, $gender,
                       $academic_year, $class, $section, $status, $advance_payment, $discount, $fee_structure, $student_id]);
    }

    // Update parent_guardian (delete old and insert new)
    $sql_del_parent = "DELETE FROM parent_guardian WHERE student_id = ?";
    $stmt_del = $pdo->prepare($sql_del_parent);
    $stmt_del->execute([$student_id]);

    $sql_parent = "INSERT INTO parent_guardian (student_id, parent_type, father_name, mother_name, guardian_phone, guardian_email)
                   VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_parent = $pdo->prepare($sql_parent);
    $stmt_parent->execute([$student_id, $parent_type, $father_name, $mother_name, $guardian_phone, $guardian_email]);

    // Update address (delete old and insert new)
    $sql_del_addr = "DELETE FROM address WHERE student_id = ?";
    $stmt_del_addr = $pdo->prepare($sql_del_addr);
    $stmt_del_addr->execute([$student_id]);

    $sql_addr = "INSERT INTO address (student_id, permanent_address, current_address) VALUES (?, ?, ?)";
    $stmt_addr = $pdo->prepare($sql_addr);
    $stmt_addr->execute([$student_id, $permanent_addr, $current_addr]);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Student updated successfully']);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Update error: ' . $e->getMessage()]);
}
?>