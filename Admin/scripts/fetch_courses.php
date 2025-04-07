<?php
include '../../config/db.php';

header('Content-Type: application/json'); // Set JSON header

// Check if the section parameter is provided and not empty
$section = isset($_GET['section']) ? trim($_GET['section']) : '';

// If the section is empty, fetch all courses
if (empty($section)) {
    // Query to fetch all courses if no section is provided
    $sql = "SELECT course_id, course_name, section FROM courses";
} else {
    // If the section is provided, use LIKE for partial matching
    $sql = "SELECT course_id, course_name, section FROM courses WHERE section LIKE :section";
}

try {
    $stmt = $conn->prepare($sql);

    // If the section is not empty, bind the section parameter with wildcard for partial matching
    if (!empty($section)) {
        $stmt->bindValue(':section', '%' . $section . '%', PDO::PARAM_STR);
    }

    // Execute the query
    $stmt->execute();

    // Fetch results as an associative array
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($courses)) {
        echo json_encode(['status' => 'error', 'message' => 'No courses found for the selected section.']);
    } else {
        // Return the courses in JSON format
        echo json_encode($courses);
    }
} catch (PDOException $e) {
    // Return error message in case of failure
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
