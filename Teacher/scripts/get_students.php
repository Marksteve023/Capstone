<?php
// get_students.php
require_once '../../config/db.php';

if (isset($_POST['course_id']) && isset($_POST['set_group'])) {
    // Sanitize and validate inputs
    $course_id = intval($_POST['course_id']);
    $set_group = $_POST['set_group'];

    // Query to fetch students based on course_id and set_group
    $query = "SELECT s.student_id, s.school_student_id, s.student_name, s.rfid_tag
              FROM students s
              JOIN student_courses sc ON s.student_id = sc.student_id
              WHERE sc.course_id = ? AND sc.set_group = ?
              ORDER BY s.student_name ASC";
    
    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    $stmt->bindParam(1, $course_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $set_group, PDO::PARAM_STR);
    $stmt->execute();

    // Fetch the results as an associative array
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return the result as a JSON response
    echo json_encode($students);
}
?>
