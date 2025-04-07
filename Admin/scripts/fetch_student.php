<?php
include '../../config/db.php';

header('Content-Type: application/json'); // Set JSON response header

if (!isset($_GET['school_student_id']) || empty(trim($_GET['school_student_id']))) {
    echo json_encode(['error' => 'Missing student ID']);
    exit;
}

try {
    $student_id = trim($_GET['school_student_id']);
    
    $sql = "SELECT student_name FROM students WHERE school_student_id = :student_id LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_STR);
    $stmt->execute();
    
    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($student) {
        echo json_encode($student);
    } else {
        echo json_encode(['error' => 'Student not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
