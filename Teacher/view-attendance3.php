<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'head.php'; ?>
    <title>View Attendance - Smart Attendance Monitoring System</title>
</head>
<body>

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?> 

    <!-- Main Content -->
    <main class="main" id="main">
        <h1>View Attendance</h1>

        <div class="container d-flex justify-content-center align-items-center">
            <div class="card shadow-lg p-4">
                <div class="mb-3">
                    <h2 class="text-center">Filter Attendance</h2>
                </div>
                <form id="CreateAttendanceForm">
                    <div class="row">
                        <!-- Section Filter -->
                        <div class="col-md-3">
                            <label for="sectionFilter" class="form-label">Section</label>
                            <select id="sectionFilter" class="form-select">
                                <option value="" disabled selected>All Sections</option>
                                <option value="A">LFCA123141</option>
                                <option value="B">LFCA124125</option>
                                <option value="C">LFCA123142</option>
                            </select>
                        </div>

                        <!-- Course Selection -->
                        <div class="col-md-3">
                            <label for="setgroupFilter" class="form-label">Course</label>
                            <select id="setgroupFilter" class="form-select">
                                <option value="" disabled selected>All Courses</option>
                                <option value="Web Development">Web Development</option>
                                <option value="Data Science">Data Science</option>
                                <option value="Networking">Networking</option>
                            </select>
                        </div>

                        <!-- Set Group Selection -->
                        <div class="col-md-3">
                            <label for="set_group" class="form-label">Set Group</label>
                            <select name="set_group" id="set_group" class="form-select">
                                <option value="" disabled selected>----- Select Set Group -----</option>
                                <option value="Set A">Set A</option>
                                <option value="Set B">Set B</option>
                            </select>
                        </div>

                        <!-- Date Selection -->
                        <div class="col-md-3">
                            <label for="dateFilter" class="form-label">Date</label>
                            <div class="input-group">
                                <span class="input-group-text" id="datePickerIcon" style="cursor: pointer;">
                                    <i class="bi bi-calendar"></i>
                                </span>
                                <input type="date" name="dateFilter" id="dateFilter" class="form-control" required>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Attendance Records -->
        <div class="attendance-records">
            
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
                            <th>Actions</th>
                        </tr>
                    </thead>    
                    <tbody class="records-list">
                      
                        <tr>
                            <td>1</td>
                            <td>Computer Science 101</td>
                            <td>Section A</td>
                            <td>Group 1</td>
                            <td>2025-04-07</td>
                            <td><button class="btn btn-primary">View</button></td>
                        </tr>
                        <tr>
                            <td>2</td>
                            <td>Database Management</td>
                            <td>Section B</td>
                            <td>Group 2</td>
                            <td>2025-04-06</td>
                            <td><button class="btn btn-primary">View</button></td>
                        </tr>
                    </tbody>
                </table>
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
            document.getElementById("dateFilter").showPicker(); // Opens the date picker
        });
    </script>
</body>
</html>
