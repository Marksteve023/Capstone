
/*====================
    COURSE & SECTION 
======================*/

// Account Deletion Function
function deleteCourse(courseID) {
    if (!confirm('Are you sure you want to delete this course & section?')) return;

    fetch('scripts/delete-course.php', {
        method: 'POST',
        body: new URLSearchParams({ course_id: courseID }),
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data); 
        if (data.success) document.querySelector(`tr[data-course-id='${courseID}']`)?.remove();
    })
    .catch(() => showMessage({ success: false, message: "Request failed. Please check your connection." }));
    
}
/*==============================
    MaNAGE TEACHER OR ASSIGNED 
===============================*/

function deleteAssigned(assignedID) {
    console.log("Attempting to delete assigned_course_id:", assignedID);

    if (!confirm('Are you sure you want to delete this Assigned course?')) return;

    fetch('scripts/delete-assigned.php', {
        method: 'POST',
        body: new URLSearchParams({ assigned_course_id: assignedID }),
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data);
        if (data.success) {
            document.querySelector(`tr[data-assigned-course-id='${assignedID}']`)?.remove();
        }
    })
    .catch(() => {
        showMessage({ success: false, message: "Request failed. Please check your connection." });
    });
}
/*=================
    MaNAGE STUDENT
===================*/

function deleteStudent(studentID) {
    console.log("Attempting to delete student_id:", studentID);

    if (!confirm('Are you sure you want to delete this Student?')) return;

    fetch('scripts/delete-student.php', {
        method: 'POST',
        body: new URLSearchParams({ student_id: studentID }),
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data);
        if (data.success) {
            document.querySelector(`tr[data-student-id='${studentID}']`)?.remove();
        }
    })
    .catch(() => {
        showMessage({ success: false, message: "Request failed. Please check your connection." });
    });
}


/* ==================
    ENROLLED STUDENT
==================== */
function deleteEnrolled(enrolledID) {
    if (!confirm('Are you sure you want to delete this Enrolled student course?')) return;

    fetch('scripts/delete-enrolled.php', {
        method: 'POST',
        body: new URLSearchParams({ student_course_id : enrolledID }),
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data);
        if (data.success) {
            document.querySelector(`tr[data-enrolled-id='${enrolledID}']`)?.remove();
        }
    })
    .catch(() => {
        showMessage({ success: false, message: "Request failed. Please check your connection." });
    });

}

/*=======================
    MANAGE USER ACCOUNT
=========================*/

// Account Deletion Function
function deleteUser(userID) {
    if (!confirm('Are you sure you want to delete this user?')) return;

    fetch('scripts/delete-account.php', {
        method: 'POST',
        body: new URLSearchParams({ user_id: userID }),
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded' }
    })
    .then(response => response.json())
    .then(data => {
        showMessage(data);
        if (data.success) document.querySelector(`tr[data-user-id='${userID}']`)?.remove();
    })
    .catch(() => showMessage({ success: false, message: "Request failed. Please check your connection." }));
}







// Show Bootstrap Alert Message
function showMessage(response) {
    var messageContainer = document.getElementById("message-container");
    if (!messageContainer) return; // Prevent errors if container is missing

    // Clear previous messages
    messageContainer.innerHTML = "";

    // Bootstrap Alert Type
    var alertType = response.success ? "alert-success" : "alert-danger";

    // Create Bootstrap Alert
    var alertDiv = document.createElement("div");
    alertDiv.className = `alert ${alertType} fade show`;
    alertDiv.setAttribute("role", "alert");
    alertDiv.innerHTML = `${response.message}`;

    // Append Alert to Container
    messageContainer.appendChild(alertDiv);

    // Auto-hide after 3 seconds
    setTimeout(() => {
        alertDiv.classList.remove("show");
        alertDiv.classList.add("fade");
        setTimeout(() => alertDiv.remove(), 500); // Remove after fade-out
    }, 3000);
}




