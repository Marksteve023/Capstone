<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Attendance Tracking System</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #66a6ff, #89f7fe);
            color: #fff;
            text-align: center;
        }

        /* Title Styling */
        .title {
            font-weight: bold;
            opacity: 0;
            animation: fadeInUp 1.5s forwards;
        }

        .title h1 {
            font-size: 3rem;
            font-weight: 600;
            background: linear-gradient(135deg, #ff7f50, #ff6347, #1e90ff);
            background-clip: text;
            color: transparent;
            animation: fadeInUp 1.5s forwards;
        }

        /* Login Button Styling */
        .login-btn {
            margin-top: 20px;
            opacity: 0;
            animation: fadeInUp 2s 0.5s forwards;
        }

        .login-btn a {
            display: inline-block;
            padding: 12px 24px;
            font-size: 18px;
            background: transparent;
            color: white;
            text-decoration: none;
            border: 2px solid white;
            border-radius: 8px;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .login-btn a:hover {
            transform: scale(1.1);
            background-color: rgba(255, 255, 255, 0.2);
            border-color: #ff6347;
            color: black;
        }

        /* Animation for Smooth Fade-In Effect */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>

    <header class="title">
        <h1>Smart Attendance Monitoring System</h1>
    </header>

    <div class="login-btn">
        <a href="login.php">Login</a>
    </div>

</body>
</html>
