<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $userType = $_POST['userType'];

    if ($userType == 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ? AND password = ?");
    } else {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE username = ? AND password = ?");
    }

    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user'] = $user;
        $_SESSION['userType'] = $userType;
        if ($userType == 'admin') {
            header("Location: admin_dashboard.php");
        } else {
            header("Location: student_dashboard.php");
        }
        exit();
    } else {
        $error = "Invalid username or password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Student Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --text-color: #5a5c69;
        }
        
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            font-family: 'Nunito', sans-serif;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .login-container:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }
        
        .login-header {
            background: var(--primary-color);
            color: white;
            padding: 30px;
            text-align: center;
            position: relative;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }
        
        .login-body {
            padding: 30px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .logo i {
            font-size: 40px;
            color: var(--primary-color);
        }
        
        .form-control {
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #e3e6f0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(78, 115, 223, 0.25);
        }
        
        .btn-login {
            background: var(--primary-color);
            border: none;
            padding: 12px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            background: var(--accent-color);
            transform: translateY(-2px);
        }
        
        .user-type-select {
            position: relative;
        }
        
        .user-type-select i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primary-color);
            pointer-events: none;
        }
        
        .form-floating label {
            color: var(--text-color);
        }
        
        .register-link {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .register-link:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
        .input-group-text {
            background: var(--secondary-color);
            border: 1px solid #e3e6f0;
        }
        
        .animate__animated {
            animation-duration: 0.5s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container animate__animated animate__fadeIn">
            <div class="login-header">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h2>Welcome Back!</h2>
                <p class="mb-0">Sign in to access your account</p>
            </div>
            
            <div class="login-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger animate__animated animate__shakeX">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="mb-4 user-type-select">
                        <label for="userType" class="form-label">Login As</label>
                        <select class="form-select" id="userType" name="userType" required>
                            <option value="student">Student</option>
                            <option value="admin">Administrator</option>
                        </select>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    
                    <div class="mb-4">
                        <label for="username" class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                        </div>
                    </div>
                    
                    <div class="d-grid mb-3">
                        <button type="submit" class="btn btn-primary btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>Login
                        </button>
                    </div>
                    
                    <div class="text-center">
                        <p class="mb-0">Don't have an account? <a href="register.php" class="register-link">Register here</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add animation when elements are focused
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('animate__animated', 'animate__pulse');
            });
            
            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('animate__animated', 'animate__pulse');
            });
        });
    </script>
</body>
</html>