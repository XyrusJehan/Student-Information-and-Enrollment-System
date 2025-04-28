<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get students, courses, and semesters for dropdowns
$students = $pdo->query("SELECT student_id, first_name, last_name FROM students ORDER BY last_name, first_name")->fetchAll();
$courses = $pdo->query("SELECT course_id, course_code, course_name FROM courses WHERE is_active = 1 ORDER BY course_code")->fetchAll();
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY start_date DESC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];
    $semester_id = $_POST['semester_id'];
    $status = $_POST['status'];

    // Check if enrollment already exists
    $stmt = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ? AND semester_id = ?");
    $stmt->execute([$student_id, $course_id, $semester_id]);
    if ($stmt->fetch()) {
        $error = "This student is already enrolled in this course for the selected semester";
    } else {
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id, semester_id, status) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$student_id, $course_id, $semester_id, $status])) {
            $_SESSION['message'] = "Enrollment added successfully";
            header("Location: manage_enrollments.php");
            exit();
        } else {
            $error = "Failed to add enrollment";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Enrollment - Student Enrollment System</title>
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
            <h2>Add New Enrollment</h2>
            <a href="manage_enrollments.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Enrollments
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="student_id" class="form-label">Student</label>
                        <select class="form-select" id="student_id" name="student_id" required>
                            <option value="">Select Student</option>
                            <?php foreach ($students as $student): ?>
                            <option value="<?php echo $student['student_id']; ?>">
                                <?php echo htmlspecialchars($student['last_name'] . ', ' . $student['first_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="course_id" class="form-label">Course</label>
                        <select class="form-select" id="course_id" name="course_id" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $course): ?>
                            <option value="<?php echo $course['course_id']; ?>">
                                <?php echo htmlspecialchars($course['course_code'] . ' - ' . $course['course_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="semester_id" class="form-label">Semester</label>
                        <select class="form-select" id="semester_id" name="semester_id" required>
                            <option value="">Select Semester</option>
                            <?php foreach ($semesters as $semester): ?>
                            <option value="<?php echo $semester['semester_id']; ?>">
                                <?php echo htmlspecialchars($semester['semester_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="Active" selected>Active</option>
                            <option value="Dropped">Dropped</option>
                            <option value="Completed">Completed</option>
                            <option value="Withdrawn">Withdrawn</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Add Enrollment</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>