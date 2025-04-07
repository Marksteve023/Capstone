<?php
session_start();
include '../../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Sanitize and validate input data
        $school_student_id = strtoupper(htmlspecialchars($_POST['school_student_id'])); // Convert to uppercase
        $student_name = htmlspecialchars($_POST['student_name']);
        $rfid_tag = htmlspecialchars($_POST['rfid_tag']);
        $program = strtoupper(htmlspecialchars($_POST['program'])); // Capitalize program
        $year_level = intval($_POST['year_level']);

        // Validate year level (1-4)
        if ($year_level < 1 || $year_level > 4) {
            $_SESSION['error'] = "Invalid year level.";
            header("Location: ../manage-students.php");
            exit;
        }

        // Check RFID uniqueness
        $sql = "SELECT * FROM students WHERE rfid_tag = :rfid_tag";
        if (!empty($_POST['student_id'])) {
            $sql .= " AND student_id != :student_id"; // Exclude current student when updating
        }
        $stmt = $conn->prepare($sql);
        $params = [':rfid_tag' => $rfid_tag];
        if (!empty($_POST['student_id'])) {
            $params[':student_id'] = $_POST['student_id'];
        }
        $stmt->execute($params);

        if ($stmt->rowCount() > 0) {
            $_SESSION['error'] = "RFID tag already exists!";
            header("Location: ../manage-students.php");
            exit;
        }

        // Password processing (only hash if provided)
        $password = $_POST['password'] ?? "";
        $hashed_password = (!empty($password) && strlen($password) >= 8) ? password_hash($password, PASSWORD_BCRYPT) : null;

        // Handle file upload
        $picture = $_POST['current_picture'] ?? "default.png";
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] == 0) {
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            $file_extension = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
            $max_file_size = 2 * 1024 * 1024; // 2MB limit

            if (!in_array($file_extension, $allowed_extensions) || $_FILES['picture']['size'] > $max_file_size) {
                $_SESSION['error'] = "Invalid file type or size.";
                header("Location: ../manage-students.php");
                exit;
            }

            // Upload the file
            $target_dir = "../../assets/uploads/";
            $picture = time() . "_" . basename($_FILES['picture']['name']);
            $target_file = $target_dir . $picture;
            if (!move_uploaded_file($_FILES['picture']['tmp_name'], $target_file)) {
                $_SESSION['error'] = "File upload failed.";
                header("Location: ../manage-students.php");
                exit;
            }
        }

        // Check if updating or adding a new student
        if (!empty($_POST['student_id'])) {
            $student_id = $_POST['student_id'];

            // Update student details
            $password_sql = $hashed_password ? ", password = :password" : "";
            $sql = "UPDATE students SET school_student_id = :school_student_id, student_name = :student_name,
                    rfid_tag = :rfid_tag, program = :program, year_level = :year_level, picture = :picture
                    $password_sql WHERE student_id = :student_id";

            $stmt = $conn->prepare($sql);
            $params = [
                ':school_student_id' => $school_student_id,
                ':student_name' => $student_name,
                ':rfid_tag' => $rfid_tag,
                ':program' => $program,
                ':year_level' => $year_level,
                ':picture' => $picture,
                ':student_id' => $student_id
            ];
            if ($hashed_password) {
                $params[':password'] = $hashed_password;
            }
            $stmt->execute($params);
            $_SESSION['message'] = ($stmt->rowCount() > 0) ? 'Student updated successfully!' : 'No changes made!';
        } else {
            // Insert new student
            $sql = "INSERT INTO students (school_student_id, student_name, rfid_tag, program, year_level, password, picture) 
                    VALUES (:school_student_id, :student_name, :rfid_tag, :program, :year_level, :password, :picture)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':school_student_id' => $school_student_id,
                ':student_name' => $student_name,
                ':rfid_tag' => $rfid_tag,
                ':program' => $program,
                ':year_level' => $year_level,
                ':password' => $hashed_password,
                ':picture' => $picture
            ]);

            $_SESSION['message'] = ($stmt->rowCount() > 0) ? 'Student created successfully!' : 'Student creation failed!';
        }

        // Redirect
        header("Location: ../manage-students.php");
        exit();
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = 'An unexpected error occurred.';
        header("Location: ../manage-students.php");
        exit();
    }
}
?>
