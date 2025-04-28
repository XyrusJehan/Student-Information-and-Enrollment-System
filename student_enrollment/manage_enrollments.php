<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle enrollment deletion
if (isset($_GET['delete'])) {
    $enrollment_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE enrollment_id = ?");
    $stmt->execute([$enrollment_id]);
    $_SESSION['message'] = "Enrollment deleted successfully";
    header("Location: manage_enrollments.php");
    exit();
}

// Handle enrollment status update
if (isset($_POST['update_status'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $status = $_POST['status'];
    $grade = $_POST['grade'];
    
    $stmt = $pdo->prepare("UPDATE enrollments SET status = ?, grade = ? WHERE enrollment_id = ?");
    if ($stmt->execute([$status, $grade, $enrollment_id])) {
        $_SESSION['message'] = "Enrollment updated successfully";
    } else {
        $_SESSION['error'] = "Failed to update enrollment";
    }
    header("Location: manage_enrollments.php");
    exit();
}

// Get all enrollments with student and course info
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$semester_filter = isset($_GET['semester']) ? $_GET['semester'] : '';
$course_filter = isset($_GET['course']) ? $_GET['course'] : '';

$query = "SELECT e.*,
                s.first_name AS student_first_name, s.last_name AS student_last_name, s.student_id,
                c.course_code, c.course_name,
                sem.semester_name
          FROM enrollments e
          JOIN students s ON e.student_id = s.student_id
          JOIN courses c ON e.course_id = c.course_id
          JOIN semesters sem ON e.semester_id = sem.semester_id
          WHERE 1=1";

$params = [];

if ($status_filter) {
    $query .= " AND e.status = ?";
    $params[] = $status_filter;
}

if ($semester_filter) {
    $query .= " AND e.semester_id = ?";
    $params[] = $semester_filter;
}

if ($course_filter) {
    $query .= " AND e.course_id = ?";
    $params[] = $course_filter;
}

$query .= " ORDER BY e.enrollment_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$enrollments = $stmt->fetchAll();

// Get semesters for filter dropdown
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY start_date DESC")->fetchAll();

// Get courses for filter dropdown
$courses = $pdo->query("SELECT course_id, course_code, course_name FROM courses ORDER BY course_code")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Enrollments - Student Enrollment System</title>
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Enrollments</h2>
            <a href="add_enrollment.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Enrollment
            </a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">All Statuses</option>
                            <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Dropped" <?php echo $status_filter == 'Dropped' ? 'selected' : ''; ?>>Dropped</option>
                            <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="Withdrawn" <?php echo $status_filter == 'Withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="semester" class="form-select" onchange="this.form.submit()">
                            <option value="">All Semesters</option>
                            <?php foreach ($semesters as $semester): ?>
                            <option value="<?php echo $semester['semester_id']; ?>" <?php echo $semester_filter == $semester['semester_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($semester['semester_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="course" class="form-select" onchange="this.form.submit()">
                            <option value="">All Courses</option>
                            <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>" <?php echo $course_filter == $course['course_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <?php if ($status_filter || $semester_filter || $course_filter): ?>
                            <a href="manage_enrollments.php" class="btn btn-outline-secondary">Clear Filters</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Course</th>
                                <th>Semester</th>
                                <th>Enrollment Date</th>
                                <th>Status</th>
                                <th>Grade</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($enrollment['student_first_name'] . ' ' . $enrollment['student_last_name']); ?>
                                    <br><small class="text-muted">ID: <?php echo htmlspecialchars($enrollment['student_id']); ?></small>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($enrollment['course_code']); ?>
                                    <br><small><?php echo htmlspecialchars($enrollment['course_name']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($enrollment['semester_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo strtolower($enrollment['status']) == 'active' ? 'success' : (strtolower($enrollment['status']) == 'dropped' ? 'danger' : (strtolower($enrollment['status']) == 'completed' ? 'info' : 'warning')); ?>">
                                        <?php echo htmlspecialchars($enrollment['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($enrollment['grade'] ?? 'N/A'); ?></td>
                                <td>
                                    <a href="edit_enrollment.php?id=<?php echo $enrollment['enrollment_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="manage_enrollments.php?delete=<?php echo $enrollment['enrollment_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this enrollment?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>