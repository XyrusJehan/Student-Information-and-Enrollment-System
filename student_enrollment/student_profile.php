<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'student') {
    header("Location: login.php");
    exit();
}

$student = $_SESSION['user'];

// Get department information if student has a major
$department = null;
if ($student['major_department_id']) {
    $stmt = $pdo->prepare("SELECT department_name FROM departments WHERE department_id = ?");
    $stmt->execute([$student['major_department_id']]);
    $department = $stmt->fetch();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $date_of_birth = $_POST['date_of_birth'];

    // Check if email exists (excluding current student)
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? AND student_id != ?");
    $stmt->execute([$email, $student['student_id']]);
    if ($stmt->fetch()) {
        $error = "Email already exists";
    } else {
        $stmt = $pdo->prepare("UPDATE students SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, date_of_birth = ? WHERE student_id = ?");
        if ($stmt->execute([$first_name, $last_name, $email, $phone_number, $address, $date_of_birth, $student['student_id']])) {
            // Update session data
            $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
            $stmt->execute([$student['student_id']]);
            $_SESSION['user'] = $stmt->fetch();
            $student = $_SESSION['user'];
            
            $success = "Profile updated successfully";
        } else {
            $error = "Failed to update profile";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="main-style.css"> <!-- Include main styles -->
    <style>
        .profile-header {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 5px solid white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
        }
        .info-card {
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center py-4">
            <h4>Student Portal</h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="student_dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="student_profile.php">
                    <i class="bi bi-person-circle"></i> My Profile
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="student_courses.php">
                    <i class="bi bi-book-half"></i> My Courses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="student_schedule.php">
                    <i class="bi bi-calendar-week"></i> My Schedule
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="student_enroll.php">
                    <i class="bi bi-plus-circle"></i> Enroll in Courses
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-left"></i> Logout
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col-12">
                    <h2>My Profile</h2>
                </div>
            </div>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-4">
                    <div class="info-card card">
                        <div class="card-body text-center">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['first_name'].'+'.$student['last_name']); ?>&background=random" 
                                 alt="Profile Picture" class="profile-picture mb-3">
                            <h4><?php echo htmlspecialchars($student['first_name'].' '.$student['last_name']); ?></h4>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars($student['email']); ?></p>
                            <span class="badge status-badge bg-<?php 
                                echo $student['current_status'] == 'Active' ? 'success' : 
                                    ($student['current_status'] == 'Graduated' ? 'info' : 
                                    ($student['current_status'] == 'Suspended' ? 'danger' : 'warning')); 
                            ?>">
                                <?php echo htmlspecialchars($student['current_status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="info-card card">
                        <div class="card-body">
                            <h5 class="card-title">Academic Information</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Student ID</span>
                                    <strong><?php echo htmlspecialchars($student['student_id']); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Enrollment Date</span>
                                    <strong><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Department</span>
                                    <strong><?php echo $department ? htmlspecialchars($department['department_name']) : 'Undeclared'; ?></strong>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="info-card card">
                        <div class="card-body">
                            <h5 class="card-title">Personal Information</h5>
                            <form method="POST">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($student['email']); ?>" required>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone_number" name="phone_number" 
                                               value="<?php echo htmlspecialchars($student['phone_number']); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="date_of_birth" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                               value="<?php echo htmlspecialchars($student['date_of_birth']); ?>">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>