<?php
session_start();
require_once '../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_role = $_SESSION['role'];

// Only allow teachers to access this page
if ($user_role !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$query = "SELECT c.course_id, c.course_name, c.section 
        FROM assigned_courses ac
        JOIN courses c ON ac.course_id = c.course_id
        WHERE ac.user_id = ?";

$stmt = $conn->prepare($query);
$stmt->bindParam(1, $user_id, PDO::PARAM_INT);
$stmt->execute();
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <title>Attendance - Smart Attendance Monitoring System</title>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?> 

    <!-- Main Content -->
    <main class="main" id="main">
        <h1>Attendance</h1>
        <div class="container d-flex justify-content-center align-items-center">
            <div class="card shadow-lg p-4">
                <div class="mb-3">
                    <h2 class="text-center">Create Attendance</h2>
                </div>
                <form id="CreateAttendanceForm">
                    <div class="row">
                        <!-- Course Selection -->
                        <div class="col-md-3">
                            <label for="course_name" class="form-label">Course & Section</label>
                            <select name="course_name" id="course_name" class="form-select" required>
                                <option value="" disabled selected>----- Select Course & Section -----</option>
                                <?php foreach ($courses as $course) { ?>
                                    <option value="<?= $course['course_id']; ?>">
                                        <?= htmlspecialchars($course['course_name'] . " - " . $course['section']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- Set Group Selection -->
                        <div class="col-md-3">
                            <label for="set_group" class="form-label">Set Group</label>
                            <select name="set_group" id="set_group" class="form-select" required>
                                <option value="" disabled selected>----- Select Set Group -----</option>
                                <option value="Set A">Set A</option>
                                <option value="Set B">Set B</option>
                            </select>
                        </div>

                        <!-- Date Selection -->
                        <div class="col-md-3">
                            <label for="attendance_date" class="form-label">Date</label>
                            <div class="input-group">
                                <span class="input-group-text" id="datePickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-calendar"></i>
                                </span>
                                <input type="date" name="attendance_date" id="attendance_date" class="form-control" required>
                            </div>
                        </div>

                        <!-- Time Selection -->
                        <div class="col-md-3">
                            <label for="attendance_time" class="form-label">Time</label>
                            <div class="input-group">
                                <span class="input-group-text" id="timePickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-clock"></i>
                                </span>
                                <input type="time" name="attendance_time" id="attendance_time" class="form-control" required>
                            </div>
                        </div>

                        
                    </div>
                    <div class="text-center mt-4">
                        <button class="btn btn-primary" id="createBtn" type="submit">
                            Create Attendance
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Modal -->
        <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-custom">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title w-100 text-center" id="attendanceModalLabel">Course | Attendance</h5>
                        <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Student ID</th>
                                    <th>Student Name</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Status</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceTable">
                                <!-- Attendance data will be appended here -->
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-success" id="saveBtn">Save Attendance</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../assets/js/global.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
   
    <script>
        // Date Picker Icon Clicked
        document.getElementById("datePickerIcon").addEventListener("click", function () {
            document.getElementById("attendance_date").showPicker(); // Opens the date picker
        });

        // Time Picker Icon Clicked
        document.getElementById("timePickerIcon").addEventListener("click", function () {
            document.getElementById("attendance_time").showPicker(); 
        });

        // Create Attendance Button Clicked
        document.getElementById("CreateAttendanceForm").addEventListener("submit", function (event) {
            event.preventDefault();

            var courseSelect = document.getElementById("course_name");
            var setGroup = document.getElementById("set_group").value;  
            var date = document.getElementById("attendance_date").value;
            var time = document.getElementById("attendance_time").value;

            if (!courseSelect.value || !setGroup || !date || !time) {
                
                // Check if any required field is empty
                alert("Please fill in all required fields!");
                return;
            }

            // Get selected course name and section
            var selectedOption = courseSelect.options[courseSelect.selectedIndex].text;

            // Update modal title
            document.getElementById("attendanceModalLabel").innerText = `${selectedOption} | ${setGroup} | Attendance`;

            // Show the modal
            var attendanceModal = new bootstrap.Modal(document.getElementById("attendanceModal"));
            attendanceModal.show();
        });

        // WebSocket Connection
        var socket = new WebSocket("ws://127.0.0.1:9000");
        var attendanceData = [];

        // Handle WebSocket Events
        socket.onopen = function () {
            console.log("WebSocket connected!");
        };

        socket.onerror = function (error) {
            console.error("WebSocket Error:", error);
        };

        socket.onclose = function () {
            console.warn("WebSocket disconnected! Attempting to reconnect...");
            setTimeout(() => {
                socket = new WebSocket("ws://127.0.0.1:9000");
            }, 3000);
        };

        // Receive RFID scan data
        socket.onmessage = function (event) {
            var data = JSON.parse(event.data);

            // Check if student already exists in the table
            if (!attendanceData.some(att => att.student_id === data.student_id)) {
                attendanceData.push(data);
                $("#attendanceTable").append(
                    `<tr>
                        <td>${data.student_id}</td>
                        <td>${data.student_name}</td>
                        <td>${data.course_name}</td>
                        <td>${data.section}</td>
                        <td>${data.status}</td>
                        <td>${data.timestamp}</td>
                    </tr>`
                );
            }
        };

        // Save button clicked -> Send data to PHP
        $("#saveBtn").click(function () {
            if (attendanceData.length === 0) {
                alert("No attendance records to save!");
                return;
            }

            $("#saveBtn").html('<span class="spinner-border spinner-border-sm"></span> Saving...').prop("disabled", true);

            $.post("save_attendance.php", { attendance: JSON.stringify(attendanceData) }, function (response) {
                alert(response);
                attendanceData = [];
                $("#attendanceTable").html("");
                $("#attendanceModal").modal("hide");
                $("#saveBtn").html("Save Attendance").prop("disabled", false);
            });
        });

    </script>
</body>
</html>
