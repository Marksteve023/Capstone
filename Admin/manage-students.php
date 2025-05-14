<?php 
session_start();
require_once __DIR__ . '/../config/db.php';

// Session validation
if (!isset($_SESSION['email']) || empty($_SESSION['role'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$edit_student = null;
$students = [];

// Fetch students
try {
    $sql = "SELECT * FROM students ORDER BY student_name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}
// Check if an ID is present in the URL for potential editing
if (isset($_GET['id']) && filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    $student_id = $_GET['id'];

    try {
        $sql = "SELECT * FROM students WHERE student_id = :student_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(":student_id", $student_id, PDO::PARAM_INT);
        $stmt->execute();
        $edit_student = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
}

// Determine the form title and button text based on whether $edit_student is set
$formTitle = $edit_student ? 'Edit Student' : 'Add Student';
$submitButtonText = $edit_student ? 'Edit Student' : 'Add Student';

// Pre-fill the form fields ONLY if $edit_student has data
$student_name = htmlspecialchars($edit_student['student_name'] ?? '');
$email = htmlspecialchars($edit_student['email'] ?? '');

?>

<!-- Manage-student.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <title>Manage Student - Smart Attendance Monitoring System</title>
</head>
<body>

    <?php include 'sidebar.php'; ?>

    <main class="main" id="main">
        <h1>Manage Student</h1>


        <div class="d-flex justify-content-center align-items-center mb-3" style="margin-top: 2.5rem;">
            <div class="col-4 text-center"> 
                <select class="form-select select2 mx-2" id="manual-select" style="text-align: center;">
                    <option value="add">Add Student</option>
                    <option value="import">Import Students</option>
                </select>
            </div>
        </div>


        <div class="container manual mt-4" id="manual">
            <div class="card shadow-lg">
                <div class="card-header text-center">
                    <h2 class="mb-0"><?= $formTitle; ?> Student</h2>
                </div>
                <div class="card-body">
                   
                    <form action="../admin/scripts/create-student.php" method="POST" enctype="multipart/form-data">
                        <?php if ($edit_student): ?>
                            <input type="hidden" name="student_id" value="<?= htmlspecialchars($edit_student['student_id']) ?>">
                        <?php endif; ?>

                        <div class="row g-3">
                            <div class="col-md-6 mb-3">
                                <label for="school_student_id">School ID</label>
                                <input type="text" class="form-control" id="school_student_id" name="school_student_id" required value="<?= htmlspecialchars($edit_student['school_student_id'] ?? '') ?>">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="student_name">Student Name</label>
                                <input type="text" class="form-control" id="student_name" name="student_name" required value="<?= $student_name ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="rfid_tag">RFID</label>
                                <input type="text" class="form-control" id="rfid_tag" name="rfid_tag" value="<?= htmlspecialchars($edit_student['rfid_tag'] ?? '') ?>">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="email">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required value="<?= $email ?>" placeholder="example@gmail.com">
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="password">Password</label>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Must be at least 8 characters." minlength="8" <?= !$edit_student ? 'required' : ''; ?>>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="program">Academic Program</label>
                                <select class="form-select select2" name="program" id="program" required>
                                    <option value="" disabled <?= !$edit_student ? 'selected' : ''; ?>>--- Select Program ---</option>
                                    <option value="ACT" <?= ($edit_student && $edit_student['program'] == 'ACT') ? 'selected' : ''; ?>>ACT</option>
                                    <option value="BSCS" <?= ($edit_student && $edit_student['program'] == 'BSCS') ? 'selected' : ''; ?>>BSCS</option>
                                    <option value="BSIT" <?= ($edit_student && $edit_student['program'] == 'BSIT') ? 'selected' : ''; ?>>BSIT</option>
                                    <option value="BSIS" <?= ($edit_student && $edit_student['program'] == 'BSIS') ? 'selected' : ''; ?>>BSIS</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="year_level">Year Level</label>
                                <select class="form-select select2" name="year_level" id="year_level" required>
                                    <option value="" disabled <?= !$edit_student ? 'selected' : ''; ?>>--- Select Year Level ---</option>
                                    <option value="1st Year" <?= ($edit_student && $edit_student['year_level'] == '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                    <option value="2nd Year" <?= ($edit_student && $edit_student['year_level'] == '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                    <option value="3rd Year" <?= ($edit_student && $edit_student['year_level'] == '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                    <option value="4th Year" <?= ($edit_student && $edit_student['year_level'] == '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                </select>
                            </div>

                            <div class="col-md-4 mb-3">
                                <label for="picture">Profile Picture</label>
                                <input type="file" id="picture" name="picture" accept="image/*">
                                <input type="hidden" name="current_picture" value="<?= htmlspecialchars($edit_student['picture'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="mb-3 d-flex justify-content-center">
                            <!-- Submit Button -->
                            <button type="submit" class="btn btn-primary"><?= $submitButtonText; ?></button>
                            
                            <!-- Cancel Button (only shows when editing) -->
                            <?php if ($edit_student): ?>
                                <a href="manage-students.php" class="btn btn-secondary ms-2">Cancel Edit</a>
                            <?php endif; ?>
                        </div>


                        <?php if (!empty($_SESSION['error'])): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
                        <?php endif; ?>

                        <?php if (!empty($_SESSION['message'])): ?>
                            <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
                        <?php endif; ?>

                        <div id="message-container"></div>
                    </form>
                </div>
            </div>
        </div>
        <div class="container import mt-4" id="import">
            <div class="card shadow-lg">
                <div class="card-header text-center">
                    <h2 class="mb-0">Import Student Data</h2>
                </div>
                <div class="card-body">
              
                    <div class="container">
                        <div class="row justify-content-center align-items-center h-100">
                            <div class="col-md-6">
                                <form action="../admin/scripts/upload_students.php" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3 text-center">
                                        <label for="file">File (Excel or CSV)</label>
                                        <input type="file" id="file" name="file" accept=".xlsx, .csv" class="form-control">
                                    </div>

                                    <div class="mb-3 text-center">
                                        <button type="submit" name="submit" class="btn btn-primary">Upload Excel/CSV</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="student-container">

            <div class="mb-0">
                <h2 class="text-center">Student Record</h2>
            </div>
         
            <!-- Search Bar -->     
            <div class="search-wrapper my-3">
                <input type="text" id="search-student" placeholder="Search by Name, School ID, Program" class="search-input">
            </div>
            <div class="table-wrapper">
                <!-- Bootstrap Table Example -->
                <table class="table table-striped table-bordered tContainer">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Picture</th>
                            <th>School ID</th>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>RFID</th>
                            <th>Academic Program</th>
                            <th>Year Level</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>    
                    <tbody  class="student-list">
                       <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="10" style="text-align: center; font-weight: bold;">No Student Records found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $index => $student): ?>
                                <tr data-student-id="<?php echo htmlspecialchars($student['student_id']);?>">
                                    <td><?php echo htmlspecialchars($index + 1); ?></td>
                                    <td>
                                        <img src="<?php echo !empty($student['picture']) ? '../assets/uploads/' . htmlspecialchars($student['picture']) : '../uploads/default.png'; ?>"
                                            alt="User Picture" class="user-picture">
                                    </td>
                                    <td><?php echo htmlspecialchars($student['school_student_id']);?></td>
                                    <td><?php echo htmlspecialchars($student['student_name']);?></td>
                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                    <td><?php echo htmlspecialchars($student['rfid_tag']);?></td>
                                    <td><?php echo htmlspecialchars($student['program']);?></td>
                                    <td><?php echo htmlspecialchars($student['year_level']);?></td>
                                    <td>
                                        <?php
                                            $createdAt = new DateTime($student['created_at']);
                                            echo htmlspecialchars($createdAt->format('Y-m-d g:i A'));
                                        ?>
                                    </td>

                                    <td>
                                        <div class="d-flex gap-2 justify-content-center">
                                        <a href="?id=<?php echo htmlspecialchars($student['student_id']); ?>"
                                            class="btn btn-outline-warning btn-sm">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </a>


                                            <form id="delete-form-<?php echo $student['student_id']; ?>" 
                                                action="" method="POST">
                                                <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                                <button type="button" class="btn btn-outline-danger btn-sm" 
                                                    onclick="deleteStudent(<?php echo $student['student_id']; ?>)">
                                                    <i class="bi bi-trash"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach?>
                        <?php endif?>    
                    </tbody>
                </table>
            </div>
        </div>
        
    </main>


    <!--=============== MAIN JS ===============-->
    <script src="../assets/js/global.js"></script>
    <script src="../assets/js/admin.js"></script>

    <!-- Bootstrap JS and Popper.js -->
    <script src="../assets/js/popper.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>-->
    
    <script src="../assets/js/bootstrap.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>-->


    <script>
         // Hide the import form initially
        document.getElementById('import').style.display = 'none';

        document.getElementById('manual-select').addEventListener('change', function() {
            const selectedValue = this.value;
            if (selectedValue === 'add') {
                document.getElementById('manual').style.display = 'block';
                document.getElementById('import').style.display = 'none';
            } else if (selectedValue === 'import') {
                document.getElementById('import').style.display = 'block';
                document.getElementById('manual').style.display = 'none';
            }
        });


        // Search Function
        document.getElementById('search-student').addEventListener('input', function () {
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
 
        // Create a WebSocket connection
        const socket = new WebSocket('ws://localhost:9000');
        
        // When the connection is open
        socket.addEventListener('open', function(event) {
            console.log('WebSocket connected to RFID server');

            // Set the mode to 'assign' when the connection is established
            socket.send(JSON.stringify({ type: 'set_mode', mode: 'assign' }));
        });

        // Listen for messages from the WebSocket server
        socket.addEventListener('message', function(event) {
            try {
                const data = JSON.parse(event.data);

                // Only update the RFID input if it's an assignment RFID
                if (data.type === 'assign_rfid' && data.rfid) {
                    document.getElementById('rfid_tag').value = data.rfid;
                    console.log("Assigned RFID received:", data.rfid);
                }
            } catch (error) {
                console.error('Error parsing message:', error);
            }
        });

        // Listen for manual input 
        document.getElementById('rfid_tag').addEventListener('input', function(event) {
            console.log("Manual RFID input:", event.target.value);
        });

    </script>
    

</body>
</html>