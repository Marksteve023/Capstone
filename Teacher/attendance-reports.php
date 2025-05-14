<?php
// ============================
// Attendance Report Page (Teacher)
// Enables teachers to generate and view attendance reports based on filters
// ============================

session_start();
include '../config/db.php';

// --- Access control: Redirect if user is not logged in or not a teacher
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'teacher') {
    header("Location: ../index.php");
    exit();
}

$user_id = intval($_SESSION['user_id']);

// --- Fetch distinct attendance sessions for courses assigned to this teacher
$sql = "SELECT DISTINCT 
            c.course_id,
            c.course_name, 
            c.section, 
            a.set_group,
            a.attendance_date, 
            a.attendance_time,
            a.timestamp
        FROM attendance a
        INNER JOIN courses c ON c.course_id = a.course_id
        INNER JOIN assigned_courses ac ON ac.course_id = c.course_id
        WHERE ac.user_id = :user_id";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// --- Helper function: Convert 24-hour time to 12-hour AM/PM format
function formatTimeToAMPM($time) {
    $date = DateTime::createFromFormat('H:i', $time);
    return $date ? $date->format('g:i A') : $time;
}

// --- Extract unique values for dropdown filters
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
    <div class="container mt-4 d-flex justify-content-center align-items-center">
        <div class="card shadow-lg p-4">
            <h2 class="text-center mb-4">Attendance Report</h2>

            <form id="attendanceForm">
                <div class="row g-3">
                    <!-- Course Dropdown -->
                    <div class="col-md-3 mb-3">
                        <label for="course_name" class="form-label">Course Name</label>
                        <select id="course_name" class="form-select">
                            <option value="" disabled selected>All Courses</option>
                            <!-- Populate course options from PHP -->
                            <?php foreach ($courses as $course_name): ?>
                                <option value="<?= htmlspecialchars($course_name) ?>"><?= htmlspecialchars($course_name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Section Dropdown -->
                    <div class="col-md-3 mb-3">
                        <label for="section" class="form-label">Section</label>
                        <select id="section" class="form-select" required>
                            <option value="" disabled selected>All Sections</option>
                            <?php foreach ($sections as $section): ?>
                                <option value="<?= htmlspecialchars($section) ?>"><?= htmlspecialchars($section) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Set Group Dropdown -->
                    <div class="col-md-2 mb-3">
                        <label for="setGroup" class="form-label">Set Group</label>
                        <select id="setGroup" class="form-select" required>
                            <option value="" disabled selected>All Groups</option>
                            <?php foreach ($set_groups as $set_group): ?>
                                <option value="<?= htmlspecialchars($set_group) ?>"><?= htmlspecialchars($set_group) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date Picker -->
                    <div class="col-md-2 mb-3">
                        <label for="attendance_date" class="form-label">Attendance Date</label>
                        <div class="input-group">
                            <span class="input-group-text" id="datePickerIcon" style="cursor: pointer;">
                                <i class="bi bi-calendar"></i>
                            </span>
                            <input type="date" name="attendance_date" id="attendance_date" class="form-control">
                        </div>
                    </div>

                    <!-- Time Picker -->
                    <div class="col-md-2 mb-3">
                        <label for="attendance_time" class="form-label">Time</label>
                        <div class="input-group">
                            <span class="input-group-text" id="timePickerIcon" style="cursor: pointer;">
                                <i class="bi bi-clock"></i>
                            </span>
                            <input type="time" name="attendance_time" id="attendance_time" class="form-control">
                        </div>
                    </div>
                </div>

                <!-- View Report Button -->
                <div class="row mt-4">
                    <div class="col text-center">
                        <button type="submit" id="viewReport" class="btn btn-secondary">View Report</button>
                    </div>
                </div>
            </form>

            <!-- Message displayed when no data is found -->
            <div id="noDataMessage" class="mt-4 text-center alert alert-danger alert-dismissible fade show" style="display: none;">
                No attendance report found for the selected filters.
            </div>
        </div>
    </div>

    <!-- Report Charts and Table Section (Initially Hidden) -->
    <div id="reportSection" class="container mt-5" style="display: none;">
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

        <!-- Student Attendance Table -->
        <div class="attendance-report mt-5">
            <div class="mb-0">
                <h2 class="text-center">Attendance report</h2>
            </div>

            <!-- Search Field for Table -->
            <div class="search-wrapper my-3 text-center">
                <input type="text" id="search-students" class="search-input text-center" placeholder="Search by Name, School ID">
            </div>

            <!-- Table Container -->
            <div class="table-wrapper">
                <table class="table table-striped table-bordered tContainer">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>School Student ID</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Section</th>
                            <th>Set Group</th>
                            <th>Timestamp</th>
                            <th>Status</th>
                            <th>Action</th> <!-- View student report modal trigger -->
                        </tr>
                    </thead>
                    <tbody id="attendanceReportsBody">
                        <!-- Rows populated dynamically -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Download Report Button -->
        <div class="mb-3 text-center">
            <button class="btn btn-success mt-3" id="attendanceReport">Download Reports</button>
        </div>
    </div>

    <!-- Modal: Student Attendance Report History -->
    <div class="modal fade" id="studentReportModal" tabindex="-1" aria-labelledby="studentReportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header d-flex justify-content-center">
                    <h5 class="modal-title text-dark" id="studentReportModalLabel">Attendance Reports History</h5>
                    <button type="button" class="btn-close position-absolute end-0 me-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Modal Body -->
                <div class="modal-body">

                    <!-- Student Chart Titles -->
                    <div class="title-chart">
                        <h2 class="text-center text-dark">Attendance History</h2>
                        <h6 id="studentReportTitle" class="text-center"></h6>
                    </div>

                    <!-- Student Attendance Charts -->
                    <div class="chart-row d-flex flex-wrap gap-4 justify-content-center mt-4">
                        <div class="chart-card p-3 shadow-sm border rounded">
                            <h5 class="text-center">Pie Chart</h5>
                            <canvas id="studentPieChart"></canvas>
                        </div>
                        <div class="chart-card p-3 shadow-sm border rounded">
                            <h5 class="text-center">Bar Chart</h5>
                            <canvas id="studentBarChart"></canvas>
                        </div>
                    </div>

                    <div class="card p-4 rounded shadow-sm mt-4">

                        <h3 class="mb-3 text-center">Filter Attendance Records</h3>

                        <div class="row mb-3">
                            <div class="col-md-4 offset-md-2">
                                <label for="attendanceMonthFilter" class="form-label">Select Month</label>
                                <div class="input-group">
                                    <span class="input-group-text" id="monthPickerIcon"><i class="bi bi-calendar3"></i></span>
                                    <input type="month" id="attendanceMonthFilter" class="form-control" />
                                </div>
                            </div>
                            <!-- Status Filter -->
                            <div class="col-md-4">
                                <label for="statusFilter" class="form-label">Select Status</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-filter-circle"></i></span>
                                    <select id="statusFilter" class="form-select">
                                        <option value="">All</option>
                                        <option value="Present">Present</option>
                                        <option value="Late">Late</option>
                                        <option value="Absent">Absent</option>
                                        <option value="Excused">Excused</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 offset-md-4">
                                <button class="btn btn-outline-secondary w-100" id="clearFilters">
                                    <i class="bi bi-x-circle me-2"></i>Clear Filters
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Table with Student Attendance History -->
                    <div class="table-responsive" id="attendanceTable">
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>#</th>
                                    <th>Course</th>
                                    <th>Section</th>
                                    <th>Set Group</th>
                                    <th>Attendance Date</th>
                                    <th>Attendance Time</th>
                                    <th>Timestamp</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="reportDetailsBody">
                                <!-- Modal report records inserted here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Modal Footer with Download Button -->
                    <div class="modal-footer">
                        <button class="btn btn-outline-success mt-3" id="studentReportDownload">Download</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<!--=============== MAIN JS ===============-->
<script src="../assets/js/global.js"></script>

<!-- Bootstrap JS and Popper.js -->
<script src="../assets/js/popper.min.js"></script>
<!--<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>-->

<script src="../assets/js/bootstrap.min.js"></script>
<!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>-->

<script src="../assets/js/chart.min.js"></script>
<!--<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>-->

<!-- Include jQuery and Select2 JS -->
 <script src="../assets/js/jquery.min.js"></script>
<!--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>-->
    
<script>

// ============================
// Attendance Report Scripts
// Handles UI events, filtering, chart updates, and report generation
// ============================

$(document).ready(function () {
    // --- Date Picker trigger
    $('#datePickerIcon').click(function () {
        const dateInput = document.getElementById("attendance_date");
        $('#attendance_date').focus();
        if (dateInput.showPicker) dateInput.showPicker();
    });

    // --- Time Picker trigger
    $('#timePickerIcon').click(function () {
        const timeInput = document.getElementById("attendance_time");
        $('#attendance_time').focus();
        if (timeInput.showPicker) timeInput.showPicker();
    });

    // --- Table Search: Attendance Reports
    $('#search-students').on('input', function () {
        const query = $(this).val().trim().toLowerCase();
        $('#attendanceReportsBody tr').each(function () {
            const schoolId = $(this).find('td:nth-child(2)').text().toLowerCase();
            const studentName = $(this).find('td:nth-child(3)').text().toLowerCase();
            $(this).toggle(schoolId.includes(query) || studentName.includes(query));
        });
    });


    function applyStudentReportFilters() {
    const selectedMonth = $('#attendanceMonthFilter').val(); // Format: YYYY-MM
    const selectedStatus = $('#statusFilter').val().toLowerCase(); // present, late, etc.

    const statusCounts = { 'present': 0, 'late': 0, 'absent': 0, 'excused': 0 };

    $('#reportDetailsBody tr').each(function () {
        const date = $(this).find('td:nth-child(5)').text().trim(); // YYYY-MM-DD
        const status = $(this).find('td:nth-child(8)').text().trim().toLowerCase();

        let show = true;

        if (selectedMonth && !date.startsWith(selectedMonth)) {
            show = false;
        }

        if (selectedStatus && status !== selectedStatus) {
            show = false;
        }

        $(this).toggle(show);

        if (show && statusCounts.hasOwnProperty(status)) {
            statusCounts[status]++;
        }
    });

    updateStudentChart({
        Present: statusCounts['present'],
        Late: statusCounts['late'],
        Absent: statusCounts['absent'],
        Excused: statusCounts['excused']
    });
}
    $('#monthPickerIcon').click(() => $('#attendanceMonthFilter')[0]?.showPicker?.());

    $('#attendanceMonthFilter, #statusFilter').on('change', applyStudentReportFilters);

        $('#clearFilters').on('click', function () {
        $('#attendanceMonthFilter').val('');
        $('#statusFilter').val('');
        applyStudentReportFilters();
    });

});

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

// --- Convert HH:mm string to AM/PM format
function formatTimeStringToAMPM(time) {
    const date = new Date(`1970-01-01T${time}:00Z`);
    const hours = date.getUTCHours();
    const minutes = date.getUTCMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;
    return `${hour12}:${minutes.toString().padStart(2, '0')} ${ampm}`;
}

// --- Convert full timestamp to AM/PM format
function formatTimestampToAMPM(timestamp) {
    const date = new Date(timestamp);
    const hours = date.getHours();
    const minutes = date.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const hour12 = hours % 12 || 12;
    return `${hour12}:${minutes.toString().padStart(2, '0')} ${ampm}`;
}

// --- Chart variables
let attendanceChart = null;
let studentChart = null;

// --- Update Class Attendance Charts
function updateChart(counts) {
    const pieCtx = document.getElementById('attendancePieChart').getContext('2d');
    const barCtx = document.getElementById('attendanceBarChart').getContext('2d');

    const labels = ['Present', 'Late', 'Absent', 'Excused'];
    const colors = ['#0d6efd', '#dc3545', '#198754', '#6f42c1'];

    if (attendanceChart?.pie) attendanceChart.pie.destroy();
    if (attendanceChart?.bar) attendanceChart.bar.destroy();

    attendanceChart = {
        pie: new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels,
                datasets: [{
                    data: labels.map(label => counts[label] || 0),
                    backgroundColor: colors
                }]
            },
            options: { responsive: true }
        }),
        bar: new Chart(barCtx, {
            type: 'bar',
            data: {
                labels,
                datasets: labels.map((label, i) => ({
                    label,
                    data: labels.map((_, j) => j === i ? (counts[label] || 0) : 0),
                    backgroundColor: colors[i]
                }))
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true },
                    title: { display: true, text: 'Class Attendance - Bar' }
                },
                scales: {
                    x: { stacked: false },
                    y: { beginAtZero: true }
                }
            }
        })
    };
}

// --- Update Student Attendance Charts
function updateStudentChart(counts) {
    const pieCtx = document.getElementById('studentPieChart').getContext('2d');
    const barCtx = document.getElementById('studentBarChart').getContext('2d');

    const labels = ['Present', 'Late', 'Absent', 'Excused'];
    const colors = ['#0d6efd', '#dc3545', '#198754', '#6f42c1'];

    if (studentChart?.pie) studentChart.pie.destroy();
    if (studentChart?.bar) studentChart.bar.destroy();

    studentChart = {
        pie: new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels,
                datasets: [{
                    data: labels.map(label => counts[label] || 0),
                    backgroundColor: colors
                }]
            },
            options: { responsive: true }
        }),
        bar: new Chart(barCtx, {
            type: 'bar',
            data: {
                labels,
                datasets: labels.map((label, i) => ({
                    label,
                    data: labels.map((_, j) => j === i ? (counts[label] || 0) : 0),
                    backgroundColor: colors[i]
                }))
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: true },
                    title: { display: true, text: 'Student Attendance - Bar' }
                },
                scales: {
                    x: { stacked: false },
                    y: { beginAtZero: true }
                }
            }
        })
    };
}

// --- Handle Form Submission
let currentAttendanceReport = {};
$('#attendanceForm').on('submit', function (e) {
    e.preventDefault();

    const course = $('#course_name').val();
    const section = $('#section').val();
    const setGroup = $('#setGroup').val();
    const date = $('#attendance_date').val();
    const time = $('#attendance_time').val();

    currentAttendanceReport = { course_name: course, section, set_group: setGroup, attendance_date: date, attendance_time: time };

    $.ajax({
        url: '../Teacher/scripts/fetch_attendance_reports.php',
        type: 'GET',
        data: currentAttendanceReport,
        dataType: 'json',
        success: function (data) {
            const tbody = $('#attendanceReportsBody');
            tbody.empty();

            if (!data || data.length === 0) {
                $('#reportSection').hide();
                $('#noDataMessage').show().delay(3000).fadeOut();
                return;
            }

            $('#reportSection').show();
            $('#noDataMessage').hide();

            const statusCounts = { 'Present': 0, 'Late': 0, 'Absent': 0, 'Excused': 0 };

            $.each(data, function (index, entry) {
                const color = getStatusColor(entry.status);
                const timestamp = entry.timestamp ? formatTimestampToAMPM(entry.timestamp) : 'N/A';
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td>${entry.school_student_id}</td>
                        <td>${entry.student_name}</td>
                        <td>${course}</td>
                        <td>${section}</td>
                        <td>${entry.set_group}</td>
                        <td>${timestamp}</td>
                        <td><span class="badge bg-${color}">${entry.status}</span></td>
                        <td>
                            <button 
                                class="btn btn-sm btn-outline-primary view-student-report"
                                data-student-id="${entry.student_id}"
                                data-school-id="${entry.school_student_id}"
                                data-course="${course}"
                                data-section="${section}"
                            >
                                <i class="bi bi-eye"></i> View Report
                            </button>
                        </td>
                    </tr>`;
                tbody.append(row);
                if (statusCounts[entry.status] !== undefined) {
                    statusCounts[entry.status]++;
                }
            });

            const formattedTime = formatTimeStringToAMPM(time);
            $('#reportTitle').text(`${course} - ${section} | ${setGroup} | ${date} | ${formattedTime}`);
            updateChart(statusCounts);
        },
        error: function () {
            $('#reportSection').hide();
            $('#noDataMessage').show().delay(3000).fadeOut();
        }
    });
});

// --- Download Full Attendance Report
$('#attendanceReport').on('click', function () {
    const { course_name, section, set_group, attendance_date, attendance_time } = currentAttendanceReport;

    if (!course_name || !section || !set_group || !attendance_date || !attendance_time) {
        alert('Please select all filters before downloading the report.');
        return;
    }

    const query = `?course_name=${encodeURIComponent(course_name)}&section=${encodeURIComponent(section)}&set_group=${encodeURIComponent(set_group)}&attendance_date=${encodeURIComponent(attendance_date)}&attendance_time=${encodeURIComponent(attendance_time)}`;
    window.location.href = `../Teacher/scripts/download_report.php${query}`;
});

$('#studentReportDownload').on('click', function () {
    const { student_id, course_name, section } = StudentReport;
    const selectedMonth = $('#attendanceMonthFilter').val();
    const selectedStatus = $('#statusFilter').val();

    const queryParams = new URLSearchParams({
        student_id,
        course_name,
        section,
    });

    if (selectedMonth) queryParams.append('month', selectedMonth);
    if (selectedStatus) queryParams.append('status', selectedStatus);

    window.location.href = `../Teacher/scripts/download_student_report.php?${queryParams.toString()}`;
});


// --- View Student Report Modal
let StudentReport = {};
$(document).on('click', '.view-student-report', function () {
    const studentId = $(this).data('student-id');
    const studentName = $(this).closest('tr').find('td:nth-child(3)').text();
    const course = $(this).data('course');
    const section = $(this).data('section');
    const schoolId = $(this).data('school-id');

    StudentReport = { student_id: studentId, course_name: course, section };

    $('#studentReportModalLabel').text(`${studentName} - ${schoolId} - ${course} - ${section} - Attendance Report History`);
    $('#studentReportModal').modal('show');

    $.ajax({
        url: '../Teacher/scripts/fetch_student_report.php',
        method: 'GET',
        data: StudentReport,
        dataType: 'json',
        success: function (data) {
            const tbody = $('#reportDetailsBody');
            tbody.empty();

            const statusCounts = { 'Present': 0, 'Late': 0, 'Absent': 0, 'Excused': 0 };
            data.forEach(row => {
                if (statusCounts[row.status] !== undefined) statusCounts[row.status]++;
            });

            updateStudentChart(statusCounts);

            if (data.length > 0) {
                const rows = data.map((row, i) => `
                    <tr>
                        <td>${i + 1}</td>
                        <td>${row.course_name}</td>
                        <td>${row.section}</td>
                        <td>${row.set_group || 'N/A'}</td>
                        <td>${row.attendance_date}</td>
                        <td>${row.attendance_time ? formatTimeStringToAMPM(row.attendance_time.slice(0, 5)) : 'N/A'}</td>
                        <td>${row.timestamp ? formatTimestampToAMPM(row.timestamp) : 'N/A'}</td>
                        <td><span class="badge bg-${getStatusColor(row.status)}">${row.status}</span></td>
                    </tr>`).join('');
                tbody.html(rows);
            } else {
                tbody.html('<tr><td colspan="8">No attendance records found.</td></tr>');
            }
        },
        error: function () {
            $('#reportDetailsBody').html('<tr><td colspan="8">Failed to fetch student report.</td></tr>');
        }
    });
});
</script>

</body>
</html>