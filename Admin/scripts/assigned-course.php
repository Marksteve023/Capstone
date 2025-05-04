<?php
    session_start();
    include '../../config/db.php';
    
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Sanitize inputs
        $user_id = filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
        $course_id = filter_input(INPUT_POST, 'course_id', FILTER_SANITIZE_NUMBER_INT);
        $reassign_id = filter_input(INPUT_POST, 'reassign_id', FILTER_SANITIZE_NUMBER_INT);
    
        if (empty($user_id) || empty($course_id)) {
            $_SESSION['error'] = "All fields are required.";
            header("Location: ../manage-teachers.php");
            exit();
        }
    
        try {
            // Check if the teacher is already assigned to the same course
            $stmt = $conn->prepare("SELECT assigned_course_id FROM assigned_courses WHERE user_id = ? AND course_id = ?");
            $stmt->execute([$user_id, $course_id]);
            $existingAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($existingAssignment) {
                $_SESSION['error'] = "This teacher is already assigned to the selected course.";
                header("Location: ../manage-teachers.php");
                exit();
            }
    
            // Check if the course is already assigned to another teacher
            $stmt = $conn->prepare("SELECT assigned_course_id, user_id FROM assigned_courses WHERE course_id = ?");
            $stmt->execute([$course_id]);
            $previousAssignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
            if ($previousAssignment) {
                if (!$reassign_id) {
                    $_SESSION['error'] = "This course is already assigned to another teacher.";
                    header("Location: ../manage-teachers.php");
                    exit();
                }
    
                // Reassign: Update the existing record
                $stmt = $conn->prepare("UPDATE assigned_courses SET user_id = ?, assigned_at = NOW() WHERE assigned_course_id = ?");
                $stmt->execute([$user_id, $reassign_id]);
    
                $_SESSION['message'] = "Course reassigned successfully!";
            } else {
                // Assign the new teacher to the course
                $stmt = $conn->prepare("INSERT INTO assigned_courses (user_id, course_id, assigned_at) VALUES (?, ?, NOW())");
                $stmt->execute([$user_id, $course_id]);
    
                $_SESSION['message'] = "Teacher assigned successfully!";
            }
    
            header("Location: ../manage-teachers.php");
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
            header("Location: ../manage-teachers.php");
            exit();
        }
    } else {
        header("Location: ../manage-teachers.php");
        exit();
    }
    
    ?>
