<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'student') {
    header("Location: login.php");
    exit();
}

$student = $_SESSION['user'];

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];
    
    // Get current semester
    $current_semester = $pdo->query("SELECT * FROM semesters 
                                    WHERE registration_start <= CURDATE() 
                                    AND registration_end >= CURDATE() 
                                    ORDER BY start_date LIMIT 1")->fetch();
    
    if (!$current_semester) {
        $error = "Registration is currently closed";
    } else {
        // Check if course is still available
        $stmt = $pdo->prepare("SELECT c.course_id, 
                                      (SELECT COUNT(*) FROM enrollments 
                                       WHERE course_id = c.course_id AND semester_id = ? AND status = 'Active') AS enrolled_count,
                                      c.max_capacity
                               FROM courses c
                               WHERE c.course_id = ? AND c.is_active = 1");
        $stmt->execute([$current_semester['semester_id'], $course_id]);
        $course = $stmt->fetch();
        
        if (!$course) {
            $error = "Course not available for enrollment";
        } elseif ($course['enrolled_count'] >= $course['max_capacity']) {
            $error = "Course is already full";
        } else {
            // Check if student is already enrolled
            $stmt = $pdo->prepare("SELECT * FROM enrollments 
                                  WHERE student_id = ? AND course_id = ? 
                                  AND semester_id = ? AND status = 'Active'");
            $stmt->execute([$student['student_id'], $course_id, $current_semester['semester_id']]);
            if ($stmt->fetch()) {
                $error = "You are already enrolled in this course";
            } else {
                // Enroll student
                $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, semester_id, status) 
                                      VALUES (?, ?, ?, 'Active')");
                if ($stmt->execute([$student['student_id'], $course_id, $current_semester['semester_id']])) {
                    $_SESSION['message'] = "Enrollment successful";
                    header("Location: student_dashboard.php");
                    exit();
                } else {
                    $error = "Failed to enroll in course";
                }
            }
        }
    }
}

// Get enrolled courses
$stmt = $pdo->prepare("SELECT c.course_code, c.course_name, cs.days, cs.start_time, cs.end_time, cs.location, e.status, 
                       (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id AND status = 'Active') AS enrolled_count,
                       c.max_capacity
                       FROM enrollments e
                       JOIN courses c ON e.course_id = c.course_id
                       JOIN class_schedule cs ON c.course_id = cs.course_id
                       WHERE e.student_id = ? AND e.status = 'Active'");
$stmt->execute([$student['student_id']]);
$enrolledCourses = $stmt->fetchAll();

// Get available courses (only those in current semester where registration is open)
$current_semester = $pdo->query("SELECT * FROM semesters 
                                WHERE registration_start <= CURDATE() 
                                AND registration_end >= CURDATE() 
                                ORDER BY start_date LIMIT 1")->fetch();

$availableCourses = [];
if ($current_semester) {
    $stmt = $pdo->prepare("SELECT c.course_id, c.course_code, c.course_name, c.description, c.credits, d.department_name, 
                                  (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id AND semester_id = ? AND status = 'Active') AS enrolled_count,
                                  c.max_capacity 
                           FROM courses c
                           JOIN departments d ON c.department_id = d.department_id
                           WHERE c.is_active = 1
                           AND c.course_id NOT IN (
                               SELECT course_id FROM enrollments 
                               WHERE student_id = ? AND semester_id = ? AND status = 'Active'
                           )");
    $stmt->execute([$current_semester['semester_id'], $student['student_id'], $current_semester['semester_id']]);
    $availableCourses = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Student Enrollment System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="main-style.css">
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
        .profile-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .welcome-header {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .course-card {
            transition: transform 0.3s;
        }
        .course-card:hover {
            transform: translateY(-5px);
        }
        .capacity-bar {
            height: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
            margin-top: 5px;
        }
        .capacity-progress {
            height: 100%;
            border-radius: 3px;
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
                <a class="nav-link active" href="student_dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="student_profile.php">
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
        <div class="welcome-header">
            <h2>Welcome, <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
            <p class="mb-0">Student ID: <?php echo htmlspecialchars($student['student_id']); ?></p>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="profile-card card">
                    <div class="card-body text-center">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($student['first_name'].'+'.$student['last_name']); ?>&background=random" 
                             alt="Profile" class="rounded-circle mb-3" style="width: 150px; height: 150px; object-fit: cover;">
                        <h4><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h4>
                        <p class="text-muted"><?php echo htmlspecialchars($student['email']); ?></p>
                        <span class="badge bg-<?php echo $student['current_status'] == 'Active' ? 'success' : 'warning'; ?>">
                            <?php echo htmlspecialchars($student['current_status']); ?>
                        </span>
                        <div class="mt-3">
                            <a href="student_profile.php" class="btn btn-primary btn-sm">Edit Profile</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>My Current Schedule</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($enrolledCourses)): ?>
                            <div class="alert alert-info">You are not currently enrolled in any courses.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Schedule</th>
                                            <th>Location</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($enrolledCourses as $course): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($course['course_code']); ?></strong><br>
                                                <?php echo htmlspecialchars($course['course_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($course['days']); ?><br>
                                                <?php echo date('h:i A', strtotime($course['start_time'])); ?> - <?php echo date('h:i A', strtotime($course['end_time'])); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($course['location']); ?></td>
                                            <td><span class="badge bg-success"><?php echo htmlspecialchars($course['status']); ?></span></td>
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

        <div class="card mb-4">
            <div class="card-header">
                <h5>Available Courses</h5>
            </div>
            <div class="card-body">
                <?php if ($current_semester && !empty($availableCourses)): ?>
                    <div class="row">
                        <?php foreach ($availableCourses as $course): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="course-card card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($course['course_code']); ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($course['course_name']); ?></h6>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($course['description'], 0, 100)); ?>...</p>
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <span class="badge bg-info"><?php echo htmlspecialchars($course['credits']); ?> credits</span>
                                            <span class="text-muted"><?php echo htmlspecialchars($course['department_name']); ?></span>
                                        </div>
                                        <div class="capacity-info">
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($course['enrolled_count']); ?> / <?php echo htmlspecialchars($course['max_capacity']); ?> enrolled
                                            </small>
                                            <div class="capacity-bar">
                                                <div class="capacity-progress bg-<?php echo ($course['enrolled_count'] / $course['max_capacity'] * 100) >= 90 ? 'danger' : (($course['enrolled_count'] / $course['max_capacity'] * 100) >= 75 ? 'warning' : 'success'); ?>" 
                                                     style="width: <?php echo min(100, ($course['enrolled_count'] / $course['max_capacity'] * 100)); ?>%">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-transparent">
                                        <form method="POST">
                                            <input type="hidden" name="course_id" value="<?php echo $course['course_id']; ?>">
                                            <button type="submit" class="btn btn-primary w-100">Enroll</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif (!$current_semester): ?>
                    <div class="alert alert-warning">
                        Registration is currently closed. Please check back during the registration period.
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        You are already enrolled in all available courses for this semester.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>