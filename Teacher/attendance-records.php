<?php
// ============================
// View Attendance Page (Teacher)
// Allows teachers to view, search, and manage attendance records
// ============================

session_start();
include '../config/db.php';

// --- Access control: Redirect if user is not logged in or not a teacher
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);
$user_role = $_SESSION['role'];

if ($user_role !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

// --- Fetch attendance records for this teacher's assigned courses
$sql = "SELECT DISTINCT 
            c.course_id,
            c.course_name, 
            c.section, 
            a.set_group,
            a.attendance_date, 
            a.attendance_time
        FROM attendance a
        INNER JOIN courses c ON c.course_id = a.course_id
        INNER JOIN assigned_courses ac ON ac.course_id = c.course_id
        WHERE ac.user_id = :user_id
        ORDER BY a.attendance_date DESC, a.attendance_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Extract unique values for filters (dropdowns)
$courses = [];
$sections = [];
$set_groups = [];
$attendance_dates = [];

foreach ($records as $session) {
    $courses[$session['course_name']] = $session['course_name'];
    $sections[$session['section']] = $session['section'];
    $set_groups[$session['set_group']] = $session['set_group'];
    $attendance_dates[$session['attendance_date']] = $session['attendance_date'];
}
// --- Sort filters alphabetically
ksort($courses);
ksort($sections);
ksort($attendance_dates);

// --- Order set groups manually (e.g., Set A, Set B)
$set_group_order = ['Set A', 'Set B'];
$ordered_set_groups = [];   

foreach ($set_group_order as $group) {
    if (isset($set_groups[$group])) {
        $ordered_set_groups[$group] = $group;
    }
}
$set_groups = $ordered_set_groups;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include shared head resources (e.g., meta tags, Bootstrap, custom CSS) -->
    <?php include 'head.php'; ?>
    <title>View Attendance - Smart Attendance Monitoring System</title>
</head>
<body>

    <!-- Sidebar navigation -->
    <?php include 'sidebar.php'; ?>

    <!-- Main content container -->
    <main class="main" id="main">
        <h1>View Attendance</h1>

        <!-- Search Form Card -->
        <div class="container mt-4 d-flex justify-content-center align-items-center">
            <div class="card shadow-lg p-4 w-100">
                <!-- Title -->
                <div class="mb-3">
                    <h2 class="text-center">Search Attendance</h2>
                </div>

                <!-- Search Filters -->
                <div id="attendanceSearch" class="mb-4">
                    <div class="row">
                        <!-- Filter: Course Name -->
                        <div class="col-md-3">
                            <label for="course_name" class="form-label">Course Name</label>
                            <select id="course_name" name="course_name" class="form-select">
                                <option value="">All Courses</option>
                                <?php foreach ($courses as $course_name): ?>
                                    <option value="<?= htmlspecialchars($course_name) ?>"><?= htmlspecialchars($course_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filter: Section -->
                        <div class="col-md-3">
                            <label for="section" class="form-label">Section</label>
                            <select id="section" name="section" class="form-select">
                                <option value="">All Sections</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?= htmlspecialchars($section) ?>"><?= htmlspecialchars($section) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filter: Set Group -->
                        <div class="col-md-2">
                            <label for="setGroup" class="form-label">Set Group</label>
                            <select id="setGroup" name="set_group" class="form-select">
                                <option value="">All Groups</option>
                                <?php foreach ($set_groups as $set_group): ?>
                                    <option value="<?= htmlspecialchars($set_group) ?>"><?= htmlspecialchars($set_group) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Filter: Date -->
                        <div class="col-md-2">
                            <label for="attendance_date" class="form-label">Attendance Date</label>
                            <div class="input-group">
                                <span class="input-group-text" id="datePickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-calendar"></i>
                                </span>
                                <input type="date" id="attendance_date" name="attendance_date" class="form-control">
                            </div>
                        </div>

                        <!-- Filter: Time -->
                        <div class="col-md-2">
                            <label for="attendance_time" class="form-label">Attendance Time</label>
                            <div class="input-group">
                                <span class="input-group-text" id="timePickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-clock"></i>
                                </span>
                                <input type="time" name="attendance_time" id="attendance_time" class="form-control" required>
                            </div>
                        </div>

                        <!-- Clear Button -->
                        <div class="text-center mt-4">
                            <button type="button" id="clearButton" class="btn btn-secondary">Clear</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Records Table -->
        <div class="attendance-records mt-4">
            <div class="mb-0">
                <h2 class="text-center" id="attendanceRecordsHeader">Attendance Records</h2>
            </div>

            <div class="table-wrapper">
                <table class="table table-striped table-bordered tContainer">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Course</th>
                            <th>Section</th>
                            <th>Set Group</th>
                            <th>Attendance Date</th>
                            <th>Attendance Time</th>
                            <th>Actions</th> <!-- Action column for viewing details -->
                        </tr>
                    </thead>    
                    <tbody id="attendanceRecordsBody">
                        <!-- Loop through attendance records from PHP -->
                        <?php foreach ($records as $index => $record): ?>
                            <tr>
                                <td><?= htmlspecialchars($index + 1); ?></td>
                                <td><?= htmlspecialchars($record['course_name']); ?></td>
                                <td><?= htmlspecialchars($record['section']); ?></td>
                                <td><?= htmlspecialchars($record['set_group']); ?></td>
                                <td><?= htmlspecialchars($record['attendance_date']); ?></td>
                                <td><?= date("h:i A", strtotime($record['attendance_time'])) ?></td>
                                <td>
                                    <!-- Button to open modal with student details -->
                                    <button 
                                        class="btn btn-sm btn-outline-primary view-attendance-btn"
                                        data-course-name="<?= htmlspecialchars($record['course_name']) ?>"
                                        data-section="<?= htmlspecialchars($record['section']) ?>"
                                        data-set-group="<?= htmlspecialchars($record['set_group']) ?>"
                                        data-date="<?= htmlspecialchars($record['attendance_date']) ?>"
                                        data-time="<?= htmlspecialchars($record['attendance_time']) ?>"
                                        data-bs-toggle="modal"
                                        data-bs-target="#attendanceModal"
                                    >
                                        View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal: Detailed View of Student Attendance for a Session -->
        <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-custom">
                <div class="modal-content">
                    <!-- Modal Header -->
                    <div class="modal-header d-flex justify-content-center">
                        <h5 class="modal-title" id="attendanceModalLabel">Course | Student Attendance</h5>
                        <button type="button" class="btn-close position-absolute end-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <!-- Modal Body -->
                    <div class="modal-body">
                        <!-- Search Field -->
                        <div class="search-wrapper-container my-3 text-center">
                            <input type="text" id="search-students" class="search-input text-center" placeholder="Search by Name, School ID">
                        </div>

                        <!-- Student Attendance Table -->
                        <div class="table-responsive" id="attendanceTable">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>#</th>
                                        <th>School Student ID</th>
                                        <th>Student Name</th>
                                        <th>Set Group</th>
                                        <th>Timestamp</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="attendanceDetailsBody">
                                    <!-- Rows populated dynamically via JavaScript -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Modal Footer with Action Buttons -->
                        <div class="modal-footer">
                            <button class="btn btn-primary mt-3" id="updateStatusBtn">Update</button>
                            <button class="btn btn-success mt-3" id="attendanceDownload">Download</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Main JS -->
    <script src="../assets/js/global.js"></script>

    <!-- Bootstrap JS and Popper.js -->
    <script src="../assets/js/popper.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>-->
    
    <script src="../assets/js/bootstrap.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>-->

    <!-- Include jQuery and Select2 JS -->
    <script src="../assets/js/jquery.min.js"></script>
    <!--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>-->
    <script src="../assets/js/select2.min.js"></script>
    <!--<script src="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/js/select2.min.js"></script>-->
    
    <script>

        // ========================================
        // JS: Filters, Modal Handling, and Updates
        // ========================================

        $(document).ready(function () {
            // Focus date input when calendar icon is clicked
            $('#datePickerIcon').click(function () {
                $('#attendance_date').focus();
                document.getElementById("attendance_date").showPicker();
            });

            // Focus time input when clock icon is clicked
            $('#timePickerIcon').click(function () {
                $('#attendance_time').focus();
                document.getElementById("attendance_time").showPicker();
            });
        });

        // ----------------------------------------
        // Filter attendance table based on inputs
        // ----------------------------------------
        function filterTable() {
            const selectedCourse = document.getElementById('course_name').value.toLowerCase();
            const selectedSection = document.getElementById('section').value.toLowerCase();
            const selectedSetGroup = document.getElementById('setGroup').value.toLowerCase();
            const selectedDate = document.getElementById('attendance_date').value;
            const selectedTime = document.getElementById('attendance_time').value;

            const rows = document.querySelectorAll('#attendanceRecordsBody tr');

            rows.forEach(row => {
                const course = row.cells[1].textContent.trim().toLowerCase();
                const section = row.cells[2].textContent.trim().toLowerCase();
                const setGroup = row.cells[3].textContent.trim().toLowerCase();
                const date = row.cells[4].textContent.trim();
                const time = row.cells[5].textContent.trim();

                const courseMatch = !selectedCourse || course === selectedCourse;
                const sectionMatch = !selectedSection || section === selectedSection;
                const groupMatch = !selectedSetGroup || setGroup === selectedSetGroup;
                const dateMatch = !selectedDate || date === selectedDate;
                const timeMatch = !selectedTime || convertTo24HourFormat(time) === selectedTime;

                row.style.display = (courseMatch && sectionMatch && groupMatch && dateMatch && timeMatch) ? '' : 'none';
            });
        }

        // Convert 12-hour formatted time to 24-hour for comparison
        function convertTo24HourFormat(timeStr) {
            const [time, modifier] = timeStr.split(' ');
            let [hours, minutes] = time.split(':');
            if (modifier === 'PM' && hours !== '12') hours = String(parseInt(hours) + 12);
            if (modifier === 'AM' && hours === '12') hours = '00';
            return `${hours.padStart(2, '0')}:${minutes}`;
        }

        // Trigger filter when inputs are changed
        document.querySelectorAll('#course_name, #section, #setGroup, #attendance_date, #attendance_time').forEach(element => {
            element.addEventListener('change', filterTable);
        });

        // Clear filters and reset the table
        document.getElementById('clearButton').addEventListener('click', () => {
            document.getElementById('course_name').value = '';
            document.getElementById('section').value = '';
            document.getElementById('setGroup').value = '';
            document.getElementById('attendance_date').value = '';
            document.getElementById('attendance_time').value = '';
            filterTable();
        });

        // -------------------------------------------
        // Modal: Search students inside attendance modal
        // -------------------------------------------
        $('#search-students').on('keyup', function () {
            const searchValue = $(this).val().toLowerCase();
            $('#attendanceDetailsBody tr').filter(function () {
                const rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.indexOf(searchValue) > -1);
            });
        });

        $(document).ready(function () {
            let currentAttendanceSession = {}; // Holds selected session info for download/update

            // -------------------------------
            // Handle "View" button click
            // -------------------------------
            $('.view-attendance-btn').on('click', function () {
                const course = $(this).data('course-name');
                const section = $(this).data('section');
                const group = $(this).data('set-group');
                const date = $(this).data('date');
                const time = $(this).data('time');

                currentAttendanceSession = { course_name: course, section, set_group: group, attendance_date: date, attendance_time: time };

                const modalTitle = `${course} - ${section} | ${group} | ${date} | ${formatAMPM(new Date(date + ' ' + time))}`;
                $('#attendanceModalLabel').text(modalTitle);

                // --- Load attendance data for selected session
                $.ajax({
                    url: '../Teacher/scripts/fetch_attendance_details.php',
                    type: 'GET',
                    data: currentAttendanceSession,
                    dataType: 'json',
                    success: function (data) {
                        const tbody = $('#attendanceDetailsBody');
                        tbody.empty();

                        if (data.length === 0) {
                            tbody.append('<tr><td colspan="6" class="text-center">No data found</td></tr>');
                            return;
                        }

                        data.forEach((row, index) => {
                            const timestamp = row.timestamp ? formatAMPM(new Date(row.timestamp)) : 'N/A';
                            const statusOptions = ['Present', 'Late', 'Absent', 'Excused'];
                            const statusSelect = `
                                <select class="form-select status-select" data-attendance-id="${row.attendance_id}">
                                    ${statusOptions.map(status => `<option value="${status}" ${row.status === status ? 'selected' : ''}>${status}</option>`).join('')}
                                </select>`;
                            const tr = `
                                <tr>
                                    <td>${index + 1}</td>
                                    <td>${row.school_student_id}</td>
                                    <td>${row.student_name}</td>
                                    <td>${row.set_group}</td>
                                    <td>${timestamp}</td>
                                    <td>${statusSelect}</td>
                                </tr>`;
                            tbody.append(tr);
                        });
                    },
                    error: function (error) {
                        console.error("Error fetching attendance details:", error);
                    }
                });
            });

            // ----------------------------------
            // Download attendance for session
            // ----------------------------------
            $('#attendanceDownload').on('click', function () {
                const { course_name, section, set_group, attendance_date, attendance_time } = currentAttendanceSession;

                if (!course_name || !attendance_date || !attendance_time) {
                    alert("Please click a View button first to select a session.");
                    return;
                }

                // Redirect to PHP file to download CSV or Excel
                window.location.href = `../Teacher/scripts/download_attendance.php?course_name=${encodeURIComponent(course_name)}&section=${encodeURIComponent(section)}&set_group=${encodeURIComponent(set_group)}&attendance_date=${encodeURIComponent(attendance_date)}&attendance_time=${encodeURIComponent(attendance_time)}`;
            });
        });

        // -----------------------------
        // Save updated attendance status
        // -----------------------------
        $('#updateStatusBtn').on('click', function () {
            const statusUpdates = [];

            // Collect selected statuses
            $('.status-select').each(function () {
                const attendanceId = $(this).data('attendance-id');
                const status = $(this).val();
                statusUpdates.push({ attendance_id: attendanceId, status: status });
            });

            // Send update via AJAX
            $.ajax({
                url: '../Teacher/scripts//update_attendance_status.php',
                type: 'POST',
                data: { status_updates: JSON.stringify(statusUpdates) },
                success: function (response) {
                    alert(response);
                    location.reload(); // Refresh page to reflect changes
                },
                error: function (error) {
                    console.error("Error updating attendance status:", error);
                }
            });
        });

        // Helper: Format Date object into h:mm AM/PM string
        function formatAMPM(date) {
            let hours = date.getHours();
            let minutes = date.getMinutes();
            let ampm = hours >= 12 ? 'PM' : 'AM';
            hours = hours % 12 || 12;
            minutes = minutes < 10 ? '0' + minutes : minutes;
            return `${hours}:${minutes} ${ampm}`;
        }

</script>
</body>
</html>
