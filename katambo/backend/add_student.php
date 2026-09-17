<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Database connection
$host = 'localhost';
$dbname = 'school_fee_system';
$username = 'root';   // change to your DB user
$password = '';       // change to your DB password

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Collect form data
$student_name     = $_POST['studentName'] ?? '';
$roll_number      = $_POST['rollNumber'] ?? '';
$associated_id    = $_POST['associatedId'] ?? '';
$email            = $_POST['email'] ?? '';
$phone            = $_POST['phone'] ?? '';
$dob              = $_POST['dob'] ?? null;
$gender           = $_POST['gender'] ?? null;
$academic_year    = $_POST['academicYear'] ?? '';
$class            = $_POST['class'] ?? '';
$section          = $_POST['section'] ?? '';
$status           = $_POST['status'] ?? 'Active';
$advance_payment  = $_POST['advancePayment'] ?? 0;
$discount         = $_POST['discount'] ?? 0;
$fee_structure    = $_POST['feeStructure'] ?? '';
$parent_type      = $_POST['parentType'] ?? null;
$father_name      = $_POST['fatherName'] ?? '';
$mother_name      = $_POST['motherName'] ?? '';
$guardian_phone   = $_POST['guardianPhone'] ?? '';
$guardian_email   = $_POST['guardianEmail'] ?? '';
$permanent_addr   = $_POST['permanentAddress'] ?? '';
$current_addr     = $_POST['currentAddress'] ?? '';

// Basic validation
if (empty($student_name) || empty($roll_number)) {
    echo json_encode(['success' => false, 'message' => 'Student name and roll number are required']);
    exit;
}

// Picture upload handling
$picture_path = null;
if (isset($_FILES['picture']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $file_extension = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
    $new_filename = 'student_' . time() . '_' . preg_replace('/[^a-zA-Z0-9]/', '', $roll_number) . '.' . $file_extension;
    $destination = $upload_dir . $new_filename;
    if (move_uploaded_file($_FILES['picture']['tmp_name'], $destination)) {
        $picture_path = 'uploads/' . $new_filename;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to upload picture']);
        exit;
    }
}

try {
    // Begin transaction
    $pdo->beginTransaction();

    // Insert into students table
    $sql_student = "INSERT INTO students 
        (student_name, roll_number, associated_id, email, phone, date_of_birth, gender, picture_path,
         academic_year, class, section, status, advance_payment, discount, fee_structure)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $pdo->prepare($sql_student);
    $stmt->execute([
        $student_name, $roll_number, $associated_id, $email, $phone, $dob, $gender, $picture_path,
        $academic_year, $class, $section, $status, $advance_payment, $discount, $fee_structure
    ]);
    $student_id = $pdo->lastInsertId();

    // Insert parent/guardian
    $sql_parent = "INSERT INTO parent_guardian 
        (student_id, parent_type, father_name, mother_name, guardian_phone, guardian_email)
        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_parent = $pdo->prepare($sql_parent);
    $stmt_parent->execute([$student_id, $parent_type, $father_name, $mother_name, $guardian_phone, $guardian_email]);

    // Insert address
    $sql_address = "INSERT INTO address (student_id, permanent_address, current_address) VALUES (?, ?, ?)";
    $stmt_address = $pdo->prepare($sql_address);
    $stmt_address->execute([$student_id, $permanent_addr, $current_addr]);

    // Commit
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Student registered successfully', 'student_id' => $student_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>