<?php
session_start();
include '../../config/db.php';

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Sanitize and validate input data
        $school_user_id = htmlspecialchars($_POST['school_user_id']);
        $full_name = htmlspecialchars($_POST['full_name']);
        $role = htmlspecialchars($_POST['role']);

        // Sanitize and validate email  
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Invalid email format. Please provide a valid email address.";
            header("Location: ../manage-user.php");
            exit;
        }

        // Validate password strength (only for new users or when password is changed)
        $password = $_POST['password'];
        $hashed_password = null;

        if (!empty($password)) {
            if (strlen($password) < 8) { // Password length check
                $_SESSION['error'] = "Password must be at least 8 characters long.";
                header("Location: ../manage-user.php");
                exit;
            }
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        }

        // Default picture fallback
        $picture = isset($_POST['current_picture']) && $_POST['current_picture'] !== ''
            ? $_POST['current_picture']
            : "default.png";

        if (isset($_FILES['picture']) && $_FILES['picture']['error'] == 0) {
            $allowed_extensions = ['jpg', 'jpeg', 'png'];
            $file_extension = strtolower(pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION));
            $max_file_size = 2 * 1024 * 1024; // 2MB

            if (!in_array($file_extension, $allowed_extensions)) {
                $_SESSION['error'] = "Invalid file type. Only JPG, JPEG, and PNG files are allowed.";
                header("Location: ../manage-user.php");
                exit;
            }

            if ($_FILES['picture']['size'] > $max_file_size) {
                $_SESSION['error'] = "File size exceeds the 2MB limit. Please upload a smaller file.";
                header("Location: ../manage-user.php");
                exit;
            }

            // Set target directory and move the file
            $target_dir = "../../assets/uploads/";
            $picture = time() . "_" . basename($_FILES['picture']['name']); // Unique file name
            $target_file = $target_dir . $picture;

            if (!move_uploaded_file($_FILES['picture']['tmp_name'], $target_file)) {
                $_SESSION['error'] = "Failed to upload the file. Please try again.";
                header("Location: ../manage-user.php");
                exit;
            }
        }

        // Check if updating an existing user
        if (!empty($_POST['user_id'])) {
            $user_id = $_POST['user_id'];

            // Fetch the current user data
            $sql = "SELECT * FROM users WHERE user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->execute([':user_id' => $user_id]);
            $current_user = $stmt->fetch();

            // Check if any changes were made
            $has_changes = false;
            if ($current_user['school_user_id'] !== $school_user_id || 
                $current_user['full_name'] !== $full_name || 
                $current_user['role'] !== $role || 
                $current_user['email'] !== $email || 
                $current_user['picture'] !== $picture || 
                (!empty($password))) {
                $has_changes = true;
            }

            if ($has_changes) {
                // Ensure no duplicate user exists (excluding the current one)
                $sql = "SELECT * FROM users
                        WHERE school_user_id = :school_user_id AND full_name = :full_name AND role = :role 
                        AND email = :email AND user_id != :user_id";
                $stmt = $conn->prepare($sql);
                $stmt->execute([ 
                    ':school_user_id' => $school_user_id,
                    ':full_name' => $full_name,
                    ':role' => $role,
                    ':email' => $email,
                    ':user_id' => $user_id
                ]);

                if ($stmt->rowCount() > 0) {
                    $_SESSION['error'] = 'A user with this account already exists!';
                    header('Location: ../manage-user.php');
                    exit;
                }

                // Update user details
                $sql = "UPDATE users SET school_user_id = :school_user_id, full_name = :full_name, role = :role, 
                        email = :email, picture = :picture" . ($hashed_password ? ", password = :password" : "") .  
                        " WHERE user_id = :user_id";

                $stmt = $conn->prepare($sql);
                $params = [
                    ':school_user_id' => $school_user_id,
                    ':full_name' => $full_name,
                    ':role' => $role,
                    ':email' => $email,
                    ':picture' => $picture,
                    ':user_id' => $user_id
                ];
                if ($hashed_password) {
                    $params[':password'] = $hashed_password;
                }
                $stmt->execute($params);

                $_SESSION['message'] = ($stmt->rowCount() > 0) ? 'User updated successfully!' : 'No changes made!';
            } else {
                $_SESSION['message'] = 'No changes made!';
            }
        } else {
            // Check if the user already exists
            $sql = "SELECT * FROM users
                    WHERE school_user_id = :school_user_id AND full_name = :full_name AND role = :role 
                    AND email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':school_user_id' => $school_user_id,
                ':full_name' => $full_name,
                ':role' => $role,
                ':email' => $email
            ]);

            if ($stmt->rowCount() > 0) {
                $_SESSION['error'] = 'A user with this account already exists!';
                header('Location: ../manage-user.php');
                exit;
            }

            // Insert new user
            $sql = "INSERT INTO users (school_user_id, full_name, role, email, password, picture) 
                    VALUES (:school_user_id, :full_name, :role, :email, :password, :picture)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([
                ':school_user_id' => $school_user_id,
                ':full_name' => $full_name,
                ':role' => $role,
                ':email' => $email,
                ':password' => $hashed_password,
                ':picture' => $picture
            ]);

            $_SESSION['message'] = ($stmt->rowCount() > 0) ? 'User created successfully!' : 'User creation failed!';
        }

        // Redirect back to the user management page
        header("Location: ../manage-user.php");
        exit();
    } catch (PDOException $e) {
        // Handle exception gracefully
        error_log("Database error: " . $e->getMessage()); // Log the error message
        $_SESSION['error'] = 'An unexpected error occurred.';
        header("Location: ../manage-user.php");
        exit();
    }
}
?>
