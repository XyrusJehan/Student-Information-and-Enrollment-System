<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'student') {
    header("Location: login.php");
    exit();
}

$student = $_SESSION['user'];

// Get current semester (most recent one that hasn't ended)
$current_semester = $pdo->query("SELECT * FROM semesters WHERE end_date >= CURDATE() ORDER BY start_date LIMIT 1")->fetch();

// Get enrolled courses for current semester
$enrolled_courses = [];
if ($current_semester) {
    $stmt = $pdo->prepare("SELECT c.course_code, c.course_name, cs.days, cs.start_time, cs.end_time, cs.location
                           FROM enrollments e
                           JOIN courses c ON e.course_id = c.course_id
                           JOIN class_schedule cs ON c.course_id = cs.course_id AND e.semester_id = cs.semester_id
                           WHERE e.student_id = ? AND e.semester_id = ? AND e.status = 'Active'
                           ORDER BY cs.start_time");
    $stmt->execute([$student['student_id'], $current_semester['semester_id']]);
    $enrolled_courses = $stmt->fetchAll();
}

// Group courses by day
$schedule_by_day = [
    'Monday' => [],
    'Tuesday' => [],
    'Wednesday' => [],
    'Thursday' => [],
    'Friday' => [],
    'Saturday' => [],
    'Sunday' => []
];

foreach ($enrolled_courses as $course) {
    $days = explode(',', $course['days']);
    foreach ($days as $day) {
        $day = trim($day);
        if (array_key_exists($day, $schedule_by_day)) {
            $schedule_by_day[$day][] = $course;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Student Enrollment System</title>
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
        .schedule-day {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .schedule-header {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            padding: 10px 15px;
            font-weight: bold;
            background-color: #007bff;
            color: white;
        }
        .schedule-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        .schedule-item:last-child {
            border-bottom: none;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
        }
        .time-badge {
            font-size: 0.9rem;
            padding: 5px 10px;
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
                <a class="nav-link active" href="student_schedule.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Schedule</h2>
            <?php if ($current_semester): ?>
                <span class="badge bg-primary"><?php echo htmlspecialchars($current_semester['semester_name']); ?></span>
            <?php endif; ?>
        </div>

        <?php if (empty($enrolled_courses)): ?>
            <div class="alert alert-info">You are not currently enrolled in any courses for this semester.</div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($schedule_by_day as $day => $courses): ?>
                    <?php if (!empty($courses)): ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="schedule-day bg-white">
                                <div class="schedule-header">
                                    <?php echo htmlspecialchars($day); ?>
                                </div>
                                <?php foreach ($courses as $course): ?>
                                    <div class="schedule-item">
                                        <h5><?php echo htmlspecialchars($course['course_code']); ?></h5>
                                        <p class="mb-1"><?php echo htmlspecialchars($course['course_name']); ?></p>
                                        <span class="badge bg-secondary time-badge">
                                            <?php echo date('h:i A', strtotime($course['start_time'])); ?> - <?php echo date('h:i A', strtotime($course['end_time'])); ?>
                                        </span>
                                        <p class="mt-2 mb-0"><i class="bi bi-geo-alt"></i> <?php echo htmlspecialchars($course['location']); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>