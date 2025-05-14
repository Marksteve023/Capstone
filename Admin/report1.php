<?php 
session_start();
include '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

$sql = "SELECT DISTINCT 
    u.user_id, u.full_name,
    c.course_id, c.course_name, c.section,
    a.set_group
FROM assigned_courses ac
INNER JOIN users u ON ac.user_id = u.user_id
INNER JOIN courses c ON ac.course_id = c.course_id
LEFT JOIN attendance a ON a.course_id = c.course_id
WHERE u.role = 'teacher'
ORDER BY u.full_name, c.course_name ASC";

$stmt = $conn->prepare($sql);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare unique filter values
$courses = [];
$sections = [];
$set_groups = [];
$teachers = [];

foreach ($records as $session) {
    $courses[$session['course_name']] = $session['course_name'];
    $sections[$session['section']] = $session['section'];
    if (!empty($session['set_group'])) {
        $set_groups[$session['set_group']] = $session['set_group'];
    }
    $teachers[$session['full_name']] = $session['full_name'];
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Include shared head elements (e.g., Bootstrap, meta tags, CSS) -->
    <?php include 'head.php'; ?>
    <title>Reports - Smart Attendance Monitoring System</title>
</head>
<body>

<!-- Include sidebar navigation -->
<?php include 'sidebar.php'; ?>

<main class="main" id="main">
    <h1>Reports</h1>

    <!-- Filter Form Section -->
    <div class="container mt-4">

        <div class="card shadow-lg">
            <div class="card-header">   
                <h2 class="mb-0 text-center">
                    Attendance Report
                </h2>
            </div>

            <div class="card-body">
                <form id="filterForm">
                    <div class="row g-3">

                        <!-- Month Picker -->
                        <div class="col-md-2 mb-3">
                            <label for="month" class="form-label">Select Month:</label>
                            <div class="input-group">
                                <span class="input-group-text" id="monthPickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-calendar-month"></i>
                                </span>
                                <input type="month" name="month" id="month" class="form-control">
                            </div>
                        </div>

                        <!-- Week Picker -->
                        <div class="col-md-2 mb-3">
                            <label for="week" class="form-label">Select Week:</label>
                            <div class="input-group">
                                <span class="input-group-text" id="weekPickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-calendar2-week"></i>
                                </span>
                                <input type="week" id="week" name="week" class="form-control">
                            </div>
                        </div>

                        <!-- Course Dropdown -->
                        <div class="col-md-2 mb-3">
                            <label for="course_name" class="form-label">Course Name</label>
                            <select name="course_name" id="course_name" class="form-select select2">
                                <option value="" selected>All Courses</option>
                                <?php foreach ($courses as $course_name): ?>
                                    <option value="<?= htmlspecialchars($course_name) ?>"><?= htmlspecialchars($course_name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Section Dropdown -->
                        <div class="col-md-2 mb-3">
                            <label for="section" class="form-label">Section</label>
                            <select id="section" name="section" class="form-select select2">
                                <option value="" selected>All Sections</option>
                                <?php foreach ($sections as $section): ?>
                                    <option value="<?= htmlspecialchars($section) ?>"><?= htmlspecialchars($section) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                            <!-- Set Group Dropdown -->
                            <div class="col-md-2 mb-3">
                                <label for="setGroup" class="form-label">Set Group</label>
                                <select name="set_group" id="setGroup" class="form-select">
                                    <option value="" selected>All Groups</option>
                                    <option value="Set A">Set A</option>
                                    <option value="Set B">Set B</option>
                                </select>
                            </div>

                        <!-- Teacher Dropdown -->
                        <div class="col-md-2 mb-3">
                            <label for="teacher" class="form-label">Teacher</label>
                            <select name="teacher" id="teacher" class="form-select select2">
                                <option value="" selected>All Teachers</option>
                                <?php foreach ($teachers as $name): ?>
                                    <option value="<?= htmlspecialchars($name) ?>"><?= htmlspecialchars($name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- View Report Button -->
                    <div class="row mt-4">
                        <div class="col text-center">
                            <button type="button" id="clearFilters" class="btn btn-outline-primary ms-2">Clear Filters</button>
                        </div>
                    </div>
                </form>
                <div class="" id="message-container"></div> 
            </div>
        </div>
    </div>

    <!-- Student Attendance Table -->
    <div class="attendance-report mt-4">
        <div class="mb-0">
            <h2 class="text-center">Attendance report</h2>
        </div>
        
        <!-- Table Container -->
        <div class="table-wrapper">
            <table class="table table-striped table-bordered tContainer">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Course Name</th>
                        <th>Section</th>
                        <th>Set Group</th>
                        <th>Attendance Date</th>
                        <th>Attendance Time</th>
                        <th>Teacher</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="attendanceReport">
                    <!-- Table body will be populated by AJAX -->
                </tbody>
            </table>
        </div>
    </div>


    <!-- Modal: Detailed View of Student Attendance for a Session -->
    <div class="modal fade" id="attendanceModal" tabindex="-1" aria-labelledby="attendanceModalLabel" aria-hidden="true" >
        <div class="modal-dialog modal-custom">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header d-flex justify-content-center">
                    <h5 class="modal-title" id="attendanceModalLabel">Course | Attendance</h5>
                    <button type="button" class="btn-close position-absolute end-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">
                    <div class="title-chart">
                        <h2 class="text-center">Class Attendance</h2>
                        <h6 id="reportTitle" class="text-center"></h6>
                    </div>

                    <!-- Charts -->
                    <div class="chart-row d-flex flex-wrap gap-4 justify-content-center mt-4">
                        <!-- Pie Chart for Attendance -->
                        <div class="chart-card p-3 shadow-sm border rounded">
                            <h5 class="text-center">Pie Chart</h5>
                            <canvas id="attendancePieChart"></canvas>
                        </div>

                        <!-- Bar Chart for Attendance -->
                        <div class="chart-card p-3 shadow-sm border rounded">
                            <h5 class="text-center">Bar Chart</h5>
                            <canvas id="attendanceBarChart"></canvas>
                        </div>
                    </div>

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
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="attendanceDetailsBody">
                                <!-- Rows populated dynamically via JavaScript -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal Footer with Action Buttons -->
                    <div class="modal-footer">
                        <button class="btn btn-outline-success mt-3" id="attendanceDownload">Download</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>
<!--=============== MAIN JS ===============-->
<script src="../assets/js/global.js"></script>
<script src="../assets/js/popper.min.js"></script>
<script src="../assets/js/bootstrap.min.js"></script>
<script src="../assets/js/chart.min.js"></script>
<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/select2.min.js"></script>

<script>
    function formatToAmPm(timeStr) {
        if (!timeStr) return '';
        const [hourStr, minuteStr] = timeStr.split(':');
        let hour = parseInt(hourStr, 10);
        const minute = parseInt(minuteStr, 10);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        hour = hour % 12 || 12; // Convert hour 0 to 12
        return `${hour.toString().padStart(2, '0')}:${minuteStr} ${ampm}`;
    }


    // --- Get color class based on attendance status
    function getStatusColor(status) {
        switch (status) {
            case 'Present': return 'primary';
            case 'Absent': return 'success';
            case 'Late': return 'danger';
            case 'Excused': return 'purple';
            default: return 'secondary';
        }
    }

    $(document).ready(function () {
        // Initialize select2 for dropdowns
        //$('.select2').select2({ width: 'resolve' });

        // Initialize date pickers for month and week
        $('#monthPickerIcon').click(() => {
            const input = $('#month')[0];
            if (input?.showPicker) input.showPicker();
            else input.focus();
        });

        $('#weekPickerIcon').click(() => {
            const input = $('#week')[0];
            if (input?.showPicker) input.showPicker();
            else input.focus();
        });

        let allAttendanceRecords = [];

        // Fetch all attendance records from the server
        function fetchAttendanceData() {
            $.ajax({
                url: '../Admin/scripts/fetch_attendance_records.php', // Adjust path if necessary
                type: 'POST',
                dataType: 'json',
                success: function (response) {
                    allAttendanceRecords = response;
                    renderTable(allAttendanceRecords); // Initially render the full data
                },
                error: function () {
                    $('#attendanceReport').html('<tr><td colspan="8" class="text-center text-danger">Failed to load data.</td></tr>');
                }
            });
        }

        // Render table rows based on data
        function renderTable(data) {
            const $tbody = $('#attendanceReport');
            $tbody.empty();

            if (data.length === 0) {
                $tbody.append('<tr><td colspan="8" class="text-center">No attendance records found.</td></tr>');
                return;
            }

            // Render rows dynamically based on attendance records
            $.each(data, function (index, record) {
                const formattedTime = formatToAmPm(record.attendance_time); // Format time

                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${record.course_name}</td>
                        <td>${record.section}</td>
                        <td>${record.set_group}</td>
                        <td>${record.attendance_date}</td>
                        <td>${formattedTime}</td> <!-- Use formatted time -->
                        <td>${record.teacher_name}</td>
                        <td>
                            <div class="d-flex gap-3 justify-content-center">
                              <button 
                                    class="btn btn-sm btn-outline-primary view-attendance-btn"
                                    data-course-name="${record.course_name}"
                                    data-section="${record.section}"
                                    data-set-group="${record.set_group}"
                                    data-date="${record.attendance_date}"
                                    data-time="${record.attendance_time}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#attendanceModal"
                                >
                                    <i class="bi bi-eye"></i> View
                                </button>

                                <!-- Delete Button -->
                                <button type="button" class="btn btn-outline-danger btn-sm delete-button" 
                                    data-course-id="${record.course_id}" 
                                    data-section="${record.section}" 
                                    data-set-group="${record.set_group || ''}"
                                    data-attendance-date="${record.attendance_date}"
                                    data-attendance-time="${record.attendance_time}">
                                    <i class="bi bi-trash"></i>
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>`;
                $tbody.append(row);
            });

            // Add event listeners for view and delete buttons
            addEventListeners();
        }

        // Add event listeners for view and delete buttons
        function addEventListeners() {
            $('.view-attendance-btn').on('click', function () {
                const courseName = $(this).data('course-name');
                const section = $(this).data('section');
                const setGroup = $(this).data('set-group');
                const attendanceDate = $(this).data('attendance-date');
                const attendanceTime = $(this).data('attendance-time');
            
                viewReport(courseName, section, setGroup, attendanceDate, attendanceTime);
            });
            // Add event listener for delete buttons
            $('.delete-button').on('click', function () {
                const courseId = $(this).data('course-id');
                const section = $(this).data('section');
                const setGroup = $(this).data('set-group');
                const attendanceDate = $(this).data('attendance-date');
                const attendanceTime = $(this).data('attendance-time');

                deleteCourse(courseId, section, setGroup, attendanceDate, attendanceTime);
            });
        }
      

        function deleteCourse(course_id, section, set_group, attendance_date, attendance_time) {
            if (!confirm("Are you sure you want to delete this attendance record?")) return;

            $.ajax({
                url: '../Admin/scripts/delete_attendance.php',
                type: 'POST',
                data: {
                    course_id,
                    section,
                    set_group,
                    attendance_date,
                    attendance_time
                },
                success: function (response) {
                    if (response === 'success') {
                        showMessage('Attendance record deleted successfully.', 'success');
                        fetchAttendanceData();
                    } else {
                        showMessage('Failed to delete attendance. Error: ' + response, 'danger');
                    }
                },
                error: function () {
                    showMessage('AJAX error occurred.', 'danger');
                }
            });
        }

        function showMessage(message, type = 'success') {
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            $('#message-container').html(`
                <div class="alert ${alertClass} alert-dismissible fade show mt-3" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);

             // Auto-hide after 3 seconds (3000 ms)
            setTimeout(() => {
                $('#message-container .alert').fadeOut('slow', function () {
                    $(this).remove();
                });
            }, 3000);
        }

      

        function isSameWeek(dateObj, weekStr) {
            const [inputYear, inputWeek] = weekStr.split('-W').map(Number);

            // Set input date to Monday of the input week
            const inputDate = new Date(inputYear, 0, 1 + (inputWeek - 1) * 7);
            const inputWeekStart = new Date(inputDate);
            inputWeekStart.setDate(inputDate.getDate() - inputDate.getDay() + 1); // Monday
            const inputWeekEnd = new Date(inputWeekStart);
            inputWeekEnd.setDate(inputWeekStart.getDate() + 6); // Sunday

            return dateObj >= inputWeekStart && dateObj <= inputWeekEnd;
        }



       function applyFilters() {
            const course = $('#course_name').val()?.toLowerCase();
            const section = $('#section').val()?.toLowerCase();
            const setGroup = $('#setGroup').val()?.toLowerCase();
            const month = $('#month').val();
            const week = $('#week').val();
            const teacher = $('#teacher').val()?.toLowerCase();
            const date = $('#attendance_date').val();
            const time = $('#attendance_time').val();

            const filteredRecords = allAttendanceRecords.filter(record => {
                const matchCourse = !course || record.course_name.toLowerCase().includes(course);
                const matchSection = !section || record.section.toLowerCase().includes(section);
                const matchSetGroup = !setGroup || record.set_group?.toLowerCase().includes(setGroup);
                const matchMonth = !month || isSameMonth(new Date(record.attendance_date), month);
                const matchWeek = !week || isSameWeek(new Date(record.attendance_date), week);
                const matchTeacher = !teacher || record.teacher_name.toLowerCase().includes(teacher);
                const matchDate = !date || record.attendance_date.includes(date);
                const matchTime = !time || record.attendance_time.includes(time);

                return matchCourse && matchSection && matchSetGroup && matchMonth && matchWeek && matchTeacher && matchDate && matchTime;
            });

            renderTable(filteredRecords);
        }

        // Attach change event to filter inputs
        $('#course_name, #section, #setGroup, #teacher, #attendance_date, #attendance_time, #month, #week').on('change', applyFilters);

        // Clear filters button
        $('#clearFilters').on('click', function () {
            $('#filterForm')[0].reset();
            $('.select2').val('').trigger('change');
            renderTable(allAttendanceRecords);
        });

        // Fetch attendance data on page load
        fetchAttendanceData();

    });
</script>


</body>
</html>
