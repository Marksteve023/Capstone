<?php
session_start();
include '../../config/db.php';

// Ensure the user is an admin
if (!isset($_SESSION['email']) || empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$response = ['success' => false, 'courses' => []];

try {
    // Fetch all courses with instructor name
    $sql = "SELECT c.course_id, c.course_name, c.section, c.semester, c.academic_year, c.created_at, u.full_name 
            FROM courses c 
            LEFT JOIN assigned_courses ac ON c.course_id = ac.course_id 
            LEFT JOIN users u ON ac.user_id = u.user_id
            ORDER BY c.course_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($courses) {
        $response['success'] = true;
        $response['courses'] = $courses;
    } else {
        $response['success'] = false;
        $response['message'] = 'No courses found.';
    }
} catch (PDOException $e) {
    // Error handling
    $response['success'] = false;
    $response['message'] = 'Database error: ' . $e->getMessage();
}

// Return the response as JSON
echo json_encode($response);
?>
