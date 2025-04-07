<?php
require_once '../config/db.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'])) {
        $course_id = intval($_POST['course_id']);

        // Fetch students for the given course ID, ordered alphabetically
        $query = "SELECT sc.student_course_id, s.student_id, s.student_name 
                  FROM students s
                  INNER JOIN student_courses sc ON s.student_id = sc.student_id
                  WHERE sc.course_id = :course_id 
                  ORDER BY s.student_name ASC";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($students)) {
            echo json_encode(['status' => 'error', 'message' => 'No students found.']);
            exit;
        }

        $totalStudents = count($students);
        $half = ceil($totalStudents / 2);

        // Prepare update statement once for better performance
        $updateQuery = "UPDATE student_courses SET set_group = :set_group WHERE student_course_id = :student_course_id";
        $updateStmt = $conn->prepare($updateQuery);

        foreach ($students as $index => $student) {
            $set_group = ($index < $half) ? 'Set A' : 'Set B';

            $updateStmt->bindParam(':set_group', $set_group, PDO::PARAM_STR);
            $updateStmt->bindParam(':student_course_id', $student['student_course_id'], PDO::PARAM_INT);
            $updateStmt->execute();
        }

        echo json_encode(['status' => 'success', 'message' => 'Students have been assigned successfully.']);
    } else {
        throw new Exception('Invalid request.');
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>