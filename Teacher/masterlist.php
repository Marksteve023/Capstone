<?php
// Create an empty array to store students grouped by course
$students_per_course = [];

// Loop through each course to get its students
foreach ($courses as $course) {
    $course_id = $course['course_id'];

    // SQL query to get all students enrolled in this course
    $sql = "SELECT DISTINCT sc.student_course_id, sc.student_id, s.school_student_id, s.student_name, 
                c.course_name, c.section, c.academic_year, sc.set_group, sc.enrolled_at
            FROM student_courses sc
            INNER JOIN students s ON sc.student_id = s.student_id
            INNER JOIN courses c ON sc.course_id = c.course_id
            WHERE sc.course_id = :course_id
            ORDER BY s.student_name ASC";

    // Prepare and execute the query 
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->execute();

    // Store the result in the array using course_id as key
    $students_per_course[$course_id] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!-- Display student data in a responsive table -->
<div class="table-responsive" id="masterlistTable">
    <table class="table table-bordered table-striped" style="table-layout: fixed; width: 100%;">
        <thead>
            <tr>
                <th>#</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th>Course</th>
                <th>Section</th>
                <th>Academic Year</th>
                <th>Set Group</th>
            </tr>
        </thead>
        <tbody id="masterlistBody">
            <?php foreach ($students_per_course as $course_id => $students): ?>
                <?php foreach ($students as $index => $student): ?>
                    <tr data-course-id="<?php echo $course_id; ?>">
                        <td><?php echo htmlspecialchars($index + 1); ?></td>
                        <td><?php echo htmlspecialchars($student['school_student_id']); ?></td>
                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['course_name']); ?></td>
                        <td><?php echo htmlspecialchars($student['section']); ?></td>
                        <td><?php echo htmlspecialchars($student['academic_year']); ?></td>
                        <td>
                            <!-- Dropdown to select set group (A or B) -->
                            <select class="form-select select2 set-group-select" 
                                    name="set_group" 
                                    data-student-id="<?php echo htmlspecialchars($student['student_course_id']); ?>"
                                    id="set_group_<?php echo htmlspecialchars($student['student_course_id']); ?>">
                                <option value="N/A" disabled <?php echo empty($student['set_group']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($student['set_group']) ?: 'Select Group'; ?>
                                </option>
                                <option value="Set A" <?php echo ($student['set_group'] === 'Set A') ? 'selected' : ''; ?>>Set A</option>
                                <option value="Set B" <?php echo ($student['set_group'] === 'Set B') ? 'selected' : ''; ?>>Set B</option>
                            </select>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="modal-footer">
        <!-- Buttons to save or auto-assign groups -->
        <button class="btn btn-primary mt-3" id="SaveSetGroup">Save</button>
        <button class="btn btn-primary mt-3" onclick="autoAssignSet()">Auto-Assigned Set</button>
        <button class="btn btn-success mt-3" id="MasterlistDownload">Download Masterlist</button>
    </div>
</div>

<!-- Include jQuery and Select2 JS -->
<script src="../assets/js/jquery.min.js"></script>
<!--<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>-->

<script>    
    // Auto-assign Set A or Set B based on student name (alphabetically)
    function autoAssignSet() {
        let students = $('.set-group-select').toArray().sort((a, b) => {
            let nameA = $(a).closest('tr').find('td:nth-child(3)').text().toLowerCase();
            let nameB = $(b).closest('tr').find('td:nth-child(3)').text().toLowerCase();
            return nameA.localeCompare(nameB);
        });

        let midpoint = Math.ceil(students.length / 2);
        let data = [];

        students.forEach((student, index) => {
            let setGroup = (index < midpoint) ? 'Set A' : 'Set B';
            $(student).val(setGroup); // Update UI

            let studentCourseId = $(student).data('student-id');
            data.push({ student_course_id: studentCourseId, set_group: setGroup });
        });

        // Send updated group assignments to the server
        $.ajax({
            url: '../Teacher/scripts/save_set_group.php',
            type: 'POST',
            data: { students: JSON.stringify(data) },
            success: function (response) {
                alert('Set groups saved successfully!');
            },
            error: function () {
                alert('Error saving set groups.');
            }
        });
    }

    // Manual Save Button Click
    $(document).ready(function () {
        $('#SaveSetGroup').click(function () {
            let data = [];

            // Collect all selected set group values
            $('.set-group-select').each(function () {
                let studentCourseId = $(this).data('student-id');
                let selectedGroup = $(this).val();

                if (selectedGroup) {
                    data.push({ student_course_id: studentCourseId, set_group: selectedGroup });
                }
            });

            if (data.length === 0) {
                alert('No changes detected.');
                return;
            }

            // Send data to the backend for saving
            $.ajax({
                url: '../Teacher/scripts/save_set_group.php',
                type: 'POST',
                data: { students: JSON.stringify(data) },
                success: function (response) {
                    alert(response);
                //refresh the page or update the UI here
                location.reload(); 
                },
                error: function () {
                    alert('Error updating set groups.');
                }
            });
        });
    });

    // Download Masterlist Button
    $(document).ready(function () {
        $('#MasterlistDownload').click(function () {
            let courseId = document.getElementById('studentModalLabel').dataset.courseId;

            if (!courseId) {
                alert('No course selected.');
                return;
            }

            // Request download from the server
            $.ajax({
                url: '../Teacher/scripts/download_masterlist.php',
                type: 'GET',
                data: { course_id: courseId },
                xhrFields: { responseType: 'blob' },
                success: function (data, status, xhr) {
                    let filename = xhr.getResponseHeader('Content-Disposition').split('filename=')[1].replace(/"/g, '');
                    let blob = new Blob([data], { type: xhr.getResponseHeader('Content-Type') });
                    let link = document.createElement('a');
                    link.href = window.URL.createObjectURL(blob);
                    link.download = filename;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                },
                error: function () {
                    alert('Error downloading the masterlist.');
                }
            });
        });
    });
</script>