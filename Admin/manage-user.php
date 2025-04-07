<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Debugging: Check if session exists
if (!isset($_SESSION['email']) || empty($_SESSION['role'])) {
    echo "Session expired or not set!";
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    echo "Unauthorized access!";
    header("Location: ../login.php");
    exit();
}

$edit_user = null;

// Edit user logic
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $User_id = $_GET['id'];

    try {
        $sql = "SELECT * FROM users WHERE user_id = :user_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":user_id", $User_id, PDO::PARAM_INT);
        $stmt->execute();
        $edit_user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}
// Fetch users
$sql = "SELECT user_id, school_id, picture, full_name, role, email, created_at FROM users";
$stmt = $conn->prepare($sql);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
//$conn = null;
?>

<!-- Manage-user.php -->
<!DOCTYPE html>
<head>
    <?php include 'head.php'; ?>
    <title>Manage User - Smart Attendance Monitoring System</title>
</head>

<body>
    <!--=============== SIDEBAR ===============-->
    <?php include 'sidebar.php'; ?>

    <!--=============== MAIN ===============-->
    <main class="main" id="main">
        <h1>Manage User</h1>
        <div class="container mt-4">
            <div class="card shadow-lg">
                <div class="card-header text-center">
                    <h2 class="mb-0"><?php echo $edit_user ? 'Edit' : 'Create'; ?> User</h2>
                </div>
                <div class="card-body">
                    <form action="../admin/scripts/create-account.php" method="POST" enctype="multipart/form-data">
                        <?php if ($edit_user): ?>
                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($edit_user['user_id']); ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="school_id" class="form-label">Teacher ID</label>
                                    <input type="text" class="form-control" id="school_id" name="school_id" value="<?php echo htmlspecialchars($edit_user['school_id'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($edit_user['full_name'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="role">Role</label>
                                    <select name="role" id="role" required>
                                        <option value="" disabled <?php echo !$edit_user ? 'selected' : ''; ?>>--- Select Role ---</option>
                                        <option value="admin" <?php echo $edit_user && $edit_user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                        <option value="teacher" <?php echo $edit_user && $edit_user['role'] === 'teacher' ? 'selected' : ''; ?>>Teacher</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($edit_user['email'] ?? ''); ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" placeholder="Password must be at least 8 characters." minlength="8" <?php echo !$edit_user ? 'required' : ''; ?>>
                                </div>

                                <div class="mb-3">
                                    <label for="picture" class="form-label">Profile Picture</label>
                                    <input type="file" class="form-control" id="picture" name="picture" accept="image/*">
                                    <input type="hidden" name="current_picture" value="<?php echo htmlspecialchars($edit_user['picture'] ?? ''); ?>">
                                    
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 d-flex justify-content-center">
                            <button type="submit" class="btn btn-primary w-50"><?php echo $edit_user ? 'Update Account' : 'Create Account'; ?></button>
                        </div>
                        
                        <?php if (!empty($_SESSION['error'])): ?>
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($_SESSION['message'])): ?>
                                <div class="alert alert-success">
                                    <?php echo htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?>
                                </div>
                            <?php endif; ?>

                        <div id="message-container"></div> 
                    </form>
                </div>
            </div>
        </div>
        
        <div class="user-list-container">
            <h2 class="mb-0 text-center">List of Users</h2>
            <!-- Search Bar -->     
            <div class="search-wrapper">
                <input type="text" id="search-account" placeholder="Search by Name, Email, or Role" class="search-input">
            </div>

            <div class="table-wrapper">
               
                <table class="table table-striped table-bordered tContainer">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Picture</th>
                            <th>Teacher ID</th>
                            <th>Full Name</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody class="account-list">
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" style="text-align: center; font-weight: bold;">No Accounts found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr data-user-id="<?php echo htmlspecialchars($user['user_id']); ?>">
                                    <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                                    <td>
                                        <img src="<?php echo !empty($user['picture']) ? '../assets/uploads/' . htmlspecialchars($user['picture']) : '../uploads/default.png'; ?>"
                                            alt="User Picture" class="user-picture">
                                    </td>
                                    <td><?php echo htmlspecialchars($user['school_id']); ?></td>
                                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['role']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="?id=<?php echo htmlspecialchars($user['user_id']); ?>" 
                                            class="btn btn-warning btn-sm">Edit</a>

                                            <form id="delete-form-<?php echo $user['user_id']; ?>" 
                                                action="" method="POST">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="deleteUser(<?php echo $user['user_id']; ?>)">Delete</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>  
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!--=============== MAIN JS ===============-->
    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/admin.js"></script>

    <!-- Bootstrap JS and Popper.js -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>

    <script>
        // Search Function
        document.getElementById('search-account').addEventListener('input', function () {
            const query = this.value.trim().toLowerCase();
            document.querySelectorAll('.table tbody tr').forEach(row => {
                const text = Array.from(row.getElementsByTagName('td')).map(td => td.textContent.trim().toLowerCase()).join(' ');
                row.style.display = text.includes(query) ? '' : 'none';
            });
        });
        
        // Ensure Messages Fade Out After Page Load
        document.addEventListener("DOMContentLoaded", () => {
        setTimeout(() => {
            document.querySelectorAll(".alert").forEach(alert => {
                alert.classList.add("fade"); // Add fade class
                alert.style.transition = "opacity 0.5s ease-out"; 
                alert.style.opacity = "0"; 

                setTimeout(() => alert.remove(), 500); // Remove after fading
            });
        }, 3000);
    });

    </script>
</body>
</html>
