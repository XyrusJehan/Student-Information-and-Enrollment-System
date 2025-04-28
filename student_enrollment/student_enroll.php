<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'student') {
    header("Location: login.php");
    exit();
}

$student = $_SESSION['user'];

// Get current semester (most recent one that registration is open for)
$current_semester = $pdo->query("SELECT * FROM semesters 
                                WHERE registration_start <= CURDATE() 
                                AND registration_end >= CURDATE() 
                                ORDER BY start_date LIMIT 1")->fetch();

// Get available courses (not already enrolled and not full)
$available_courses = [];
if ($current_semester) {
    $stmt = $pdo->prepare("SELECT c.course_id, c.course_code, c.course_name, c.description, c.credits, 
                                  d.department_name, cs.days, cs.start_time, cs.end_time, cs.location,
                                  (SELECT COUNT(*) FROM enrollments 
                                   WHERE course_id = c.course_id AND semester_id = ? AND status = 'Active') AS enrolled_count,
                                  c.max_capacity
                           FROM courses c
                           JOIN departments d ON c.department_id = d.department_id
                           LEFT JOIN class_schedule cs ON c.course_id = cs.course_id AND cs.semester_id = ?
                           WHERE c.is_active = 1
                           AND c.course_id NOT IN (
                               SELECT course_id FROM enrollments 
                               WHERE student_id = ? AND semester_id = ? AND status = 'Active'
                           )
                           ORDER BY c.course_code");
    $stmt->execute([$current_semester['semester_id'], $current_semester['semester_id'], $student['student_id'], $current_semester['semester_id']]);
    $available_courses = $stmt->fetchAll();
}

// Handle enrollment
// In student_enroll.php, ensure the following code is present to handle POST requests
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];
    
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
        // Enroll student
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, semester_id, status) VALUES (?, ?, ?, 'Active')");
        if ($stmt->execute([$student['student_id'], $course_id, $current_semester['semester_id']])) {
            $_SESSION['message'] = "Enrollment successful";
            header("Location: student_courses.php");
            exit();
        } else {
            $error = "Failed to enroll in course";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll in Courses - Student Enrollment System</title>
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
        .course-card {
            transition: transform 0.3s;
            margin-bottom: 20px;
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
            border-radius: 3px;        }
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
                <a class="nav-link active" href="student_enroll.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Enroll in Courses</h2>
            <?php if ($current_semester): ?>
                <span class="badge bg-primary"><?php echo htmlspecialchars($current_semester['semester_name']); ?></span>
            <?php endif; ?>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if (!$current_semester): ?>
            <div class="alert alert-warning">
                Registration is currently closed. Please check back during the registration period.
            </div>
        <?php elseif (empty($available_courses)): ?>
            <div class="alert alert-info">
                You are already enrolled in all available courses for this semester.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($available_courses as $course): ?>
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
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>