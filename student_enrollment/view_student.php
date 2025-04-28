<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_students.php");
    exit();
}

$student_id = $_GET['id'];

// Get student data
$stmt = $pdo->prepare("SELECT s.*, d.department_name 
                       FROM students s 
                       LEFT JOIN departments d ON s.major_department_id = d.department_id 
                       WHERE s.student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: manage_students.php");
    exit();
}

// Get enrolled courses
$stmt = $pdo->prepare("SELECT e.*, c.course_code, c.course_name, sem.semester_name 
                       FROM enrollments e
                       JOIN courses c ON e.course_id = c.course_id
                       JOIN semesters sem ON e.semester_id = sem.semester_id
                       WHERE e.student_id = ?
                       ORDER BY sem.start_date DESC, c.course_code");
$stmt->execute([$student_id]);
$enrollments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Student - Student Enrollment System</title>
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
    <?php include 'admin_sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Student Details</h2>
                        <div>
                            <a href="edit_student.php?id=<?php echo $student_id; ?>" class="btn btn-outline-primary">
                                <i class="bi bi-pencil"></i> Edit
                            </a>
                            <a href="manage_students.php" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Students
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="info-card card">
                        <div class="card-body text-center">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['first_name'].'+'.$student['last_name']); ?>&background=random" 
                                 alt="Profile Picture" class="profile-picture mb-3">
                            <h4><?php echo htmlspecialchars($student['first_name'].' '.$student['last_name']); ?></h4>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars($student['email']); ?></p>
                            <span class="badge bg-<?php 
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
                                    <span>Username</span>
                                    <strong><?php echo htmlspecialchars($student['username']); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Enrollment Date</span>
                                    <strong><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></strong>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <span>Major</span>
                                    <strong><?php echo $student['department_name'] ? htmlspecialchars($student['department_name']) : 'Undeclared'; ?></strong>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="info-card card">
                        <div class="card-body">
                            <h5 class="card-title">Contact Information</h5>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <i class="bi bi-envelope me-2"></i>
                                    <?php echo htmlspecialchars($student['email']); ?>
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-phone me-2"></i>
                                    <?php echo htmlspecialchars($student['phone_number'] ?: 'N/A'); ?>
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-calendar me-2"></i>
                                    <?php echo $student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'N/A'; ?>
                                </li>
                                <li class="list-group-item">
                                    <i class="bi bi-geo-alt me-2"></i>
                                    <?php echo htmlspecialchars($student['address'] ?: 'N/A'); ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="info-card card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="card-title mb-0">Course Enrollments</h5>
                                <a href="add_enrollment.php?student_id=<?php echo $student_id; ?>" class="btn btn-sm btn-primary">
                                    <i class="bi bi-plus-circle"></i> Add Enrollment
                                </a>
                            </div>
                            
                            <?php if (empty($enrollments)): ?>
                                <div class="alert alert-info">This student has no course enrollments.</div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Semester</th>
                                                <th>Course</th>
                                                <th>Enrollment Date</th>
                                                <th>Status</th>
                                                <th>Grade</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($enrollments as $enrollment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($enrollment['semester_name']); ?></td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($enrollment['course_code']); ?></strong><br>
                                                    <small><?php echo htmlspecialchars($enrollment['course_name']); ?></small>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo strtolower($enrollment['status']) == 'active' ? 'success' : (strtolower($enrollment['status']) == 'dropped' ? 'danger' : (strtolower($enrollment['status']) == 'completed' ? 'info' : 'warning')); ?>">
                                                        <?php echo htmlspecialchars($enrollment['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($enrollment['grade'] ?: 'N/A'); ?></td>
                                                <td>
                                                    <a href="edit_enrollment.php?id=<?php echo $enrollment['enrollment_id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary" title="Edit">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>