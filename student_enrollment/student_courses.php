<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'student') {
    header("Location: login.php");
    exit();
}

$student = $_SESSION['user'];

// Get enrolled courses with grades
$stmt = $pdo->prepare("SELECT e.*, c.course_code, c.course_name, c.credits, d.department_name, 
                               sem.semester_name, cs.days, cs.start_time, cs.end_time, cs.location
                        FROM enrollments e
                        JOIN courses c ON e.course_id = c.course_id
                        JOIN departments d ON c.department_id = d.department_id
                        JOIN semesters sem ON e.semester_id = sem.semester_id
                        LEFT JOIN class_schedule cs ON c.course_id = cs.course_id AND sem.semester_id = cs.semester_id
                        WHERE e.student_id = ?
                        ORDER BY sem.start_date DESC, c.course_code");
$stmt->execute([$student['student_id']]);
$enrollments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Student Enrollment System</title>
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
        .badge-active {
            background-color: #28a745; /* Green for active */
            color: white; /* White text */
        }
        .badge-dropped {
            background-color: #dc3545; /* Red for dropped */
            color: white; /* White text */
        }
        .badge-completed {
            background-color: #007bff; /* Blue for completed */
            color: white; /* White text */
        }
        .badge-withdrawn {
            background-color: #ffc107; /* Yellow for withdrawn */
            color: black; /* Black text */
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
                <a class="nav-link active" href="student_courses.php">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>My Courses</h2>
            <a href="student_enroll.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Enroll in Courses
            </a>
        </div>

        <?php if (empty($enrollments)): ?>
            <div class="alert alert-info">You are not currently enrolled in any courses.</div>
        <?php else: ?>
            <div class="accordion" id="coursesAccordion">
                <?php 
                $current_semester = null;
                foreach ($enrollments as $enrollment): 
                    if ($enrollment['semester_name'] != $current_semester): 
                        $current_semester = $enrollment['semester_name'];
                ?>
                <div class="accordion-item">
                    <h2 class="accordion-header" id="heading<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $current_semester); ?>">
                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $current_semester); ?>" aria-expanded="true" aria-controls="collapse<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $current_semester); ?>">
                            <?php echo htmlspecialchars($current_semester); ?>
                        </button>
                    </h2>
                    <div id="collapse<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $current_semester); ?>" class="accordion-collapse collapse show" aria-labelledby="heading<?php echo preg_replace('/[^a-zA-Z0-9]/', '', $current_semester); ?>" data-bs-parent="#coursesAccordion">
                        <div class="accordion-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Schedule</th>
                                            <th>Location</th>
                                            <th>Credits</th>
                                            <th>Status</th>
                                            <th>Grade</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        foreach ($enrollments as $e): 
                                            if ($e['semester_name'] == $current_semester):
                                        ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($e['course_code']); ?></strong><br>
                                                <?php echo htmlspecialchars($e['course_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($e['department_name']); ?></small>
                                            </td>
                                            <td>
                                                <?php if ($e['days'] && $e['start_time']): ?>
                                                    <?php echo htmlspecialchars($e['days']); ?><br>
                                                    <?php echo date('h:i A', strtotime($e['start_time'])); ?> - <?php echo date('h:i A', strtotime($e['end_time'])); ?>
                                                <?php else: ?>
                                                    TBA
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($e['location'] ?? 'TBA'); ?></td>
                                            <td><?php echo htmlspecialchars($e['credits']); ?></td>
                                            <td>
                                                <span class="badge 
                                                    <?php 
                                                    switch (strtolower($e['status'])) {
                                                        case 'active':
                                                            echo 'badge-active';
                                                            break;
                                                        case 'dropped':
                                                            echo 'badge-dropped';
                                                            break;
                                                        case 'completed':
                                                            echo 'badge-completed';
                                                            break;
                                                        case 'withdrawn':
                                                            echo 'badge-withdrawn';
                                                            break;
                                                        default:
                                                            echo 'badge-secondary'; // Fallback for any unexpected status
                                                    }
                                                    ?>">
                                                    <?php echo htmlspecialchars($e['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($e['grade'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>