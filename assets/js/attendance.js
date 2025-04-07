const socket = new WebSocket("ws://127.0.0.1:9000");

socket.onopen = function () {
    console.log("Connected to WebSocket server.");
    
    // Example: Send a test message to the server
    socket.send(JSON.stringify({ type: "init", message: "Attendance system connected" }));
};

socket.onmessage = function (event) {
    console.log("Message from server:", event.data);

    try {
        const data = JSON.parse(event.data);
        
        if (data.type === "attendance_update") {
            updateAttendanceDisplay(data);
        }
    } catch (error) {
        console.error("Invalid JSON from server:", event.data);
    }
};

socket.onclose = function () {
    console.log("Disconnected from WebSocket server.");
};

socket.onerror = function (error) {
    console.error("WebSocket Error:", error);
};

// Function to send attendance data
function sendAttendance(studentId, courseId, status) {
    const attendanceData = {
        type: "attendance",
        student_id: studentId,
        course_id: courseId,
        status: status,
        timestamp: new Date().toISOString()
    };
    socket.send(JSON.stringify(attendanceData));
}

// Function to update the attendance display dynamically
function updateAttendanceDisplay(data) {
    const attendanceList = document.getElementById("attendance-list");
    
    if (!attendanceList) return;

    const listItem = document.createElement("li");
    listItem.textContent = `Student ${data.student_id} marked as ${data.status} at ${data.timestamp}`;
    attendanceList.appendChild(listItem);
}
