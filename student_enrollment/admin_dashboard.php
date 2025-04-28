<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

$admin = $_SESSION['user'];

// Get statistics
$totalStudents = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
$activeStudents = $pdo->query("SELECT COUNT(*) FROM students WHERE current_status = 'Active'")->fetchColumn();
$totalCourses = $pdo->query("SELECT COUNT(*) FROM courses")->fetchColumn();
$currentEnrollments = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE status = 'Active'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Student Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="main-style.css"> <!-- Include main styles -->
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #212529;
            color: white;
            position: fixed;
            width: 250px;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .main-content {
            margin-left: 250px;
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="text-center py-4">
            <h4>Admin Dashboard</h4>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="admin_dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_students.php">
                    <i class="bi bi-people-fill"></i> Manage Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_courses.php">
                    <i class="bi bi-book-half"></i> Manage Courses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_departments.php">
                    <i class="bi bi-building"></i> Manage Departments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_enrollments.php">
                    <i class="bi bi-clipboard-check"></i> Manage Enrollments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="manage_semesters.php">
                    <i class="bi bi-calendar-range"></i> Manage Semesters
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
        <div class="welcome-header">
            <h2>Welcome, <?php echo htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']); ?></h2>
            <p class="mb-0">Administrator Dashboard</p>
        </div>

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card card bg-primary text-white p-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Students</h5>
                        <h2 class="card-text"><?php echo $totalStudents; ?></h2>
                        <a href="manage_students.php" class="text-white">View all</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-success text-white p-3">
                    <div class="card-body">
                        <h5 class="card-title">Active Students</h5>
                        <h2 class="card-text"><?php echo $activeStudents; ?></h2>
                        <a href="manage_students.php?status=Active" class="text-white">View active</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-info text-white p-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Courses</h5>
                        <h2 class="card-text"><?php echo $totalCourses; ?></h2>
                        <a href="manage_courses.php" class="text-white">View courses</a>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card card bg-warning text-dark p-3">
                    <div class="card-body">
                        <h5 class="card-title">Current Enrollments</h5>
                        <h2 class="card-text"><?php echo $currentEnrollments; ?></h2>
                        <a href="manage_enrollments.php" class="text-dark">View enrollments</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Enrollments</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("SELECT e.enrollment_date, s.first_name, s.last_name, c.course_name 
                                                         FROM enrollments e
                                                         JOIN students s ON e.student_id = s.student_id
                                                         JOIN courses c ON e.course_id = c.course_id
                                                         ORDER BY e.enrollment_date DESC LIMIT 5");
                                    while ($row = $stmt->fetch()):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['course_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($row['enrollment_date'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Recent Students</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $stmt = $pdo->query("SELECT first_name, last_name, email, current_status 
                                                         FROM students 
                                                         ORDER BY enrollment_date DESC LIMIT 5");
                                    while ($row = $stmt->fetch()):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><span class="badge bg-<?php echo $row['current_status'] == 'Active' ? 'success' : 'warning'; ?>">
                                            <?php echo htmlspecialchars($row['current_status']); ?>
                                        </span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>