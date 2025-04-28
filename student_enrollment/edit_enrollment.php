<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_enrollments.php");
    exit();
}

$enrollment_id = $_GET['id'];

// Get enrollment data
$stmt = $pdo->prepare("SELECT e.*, s.first_name, s.last_name, c.course_code, c.course_name, sem.semester_name 
                       FROM enrollments e
                       JOIN students s ON e.student_id = s.student_id
                       JOIN courses c ON e.course_id = c.course_id
                       JOIN semesters sem ON e.semester_id = sem.semester_id
                       WHERE e.enrollment_id = ?");
$stmt->execute([$enrollment_id]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    header("Location: manage_enrollments.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = $_POST['status'];
    $grade = $_POST['grade'];

    $stmt = $pdo->prepare("UPDATE enrollments SET status = ?, grade = ? WHERE enrollment_id = ?");
    if ($stmt->execute([$status, $grade, $enrollment_id])) {
        $_SESSION['message'] = "Enrollment updated successfully";
        header("Location: manage_enrollments.php");
        exit();
    } else {
        $error = "Failed to update enrollment";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Enrollment - Student Enrollment System</title>
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
            background-color: rgba(255, 255, 255            .nav-link.active {
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
                <h2>Edit Enrollment</h2>
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
                            <label for="student" class="form-label">Student</label>
                            <input type="text" class="form-control" id="student" value="<?php echo htmlspecialchars($enrollment['first_name'] . ' ' . $enrollment['last_name']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="course" class="form-label">Course</label>
                            <input type="text" class="form-control" id="course" value="<?php echo htmlspecialchars($enrollment['course_code'] . ' - ' . $enrollment['course_name']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="semester" class="form-label">Semester</label>
                            <input type="text" class="form-control" id="semester" value="<?php echo htmlspecialchars($enrollment['semester_name']); ?>" disabled>
                        </div>
                        <div class="mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="Active" <?php echo $enrollment['status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Dropped" <?php echo $enrollment['status'] == 'Dropped' ? 'selected' : ''; ?>>Dropped</option>
                                <option value="Completed" <?php echo $enrollment['status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="Withdrawn" <?php echo $enrollment['status'] == 'Withdrawn' ? 'selected' : ''; ?>>Withdrawn</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="grade" class="form-label">Grade</label>
                            <input type="text" class="form-control" id="grade" name="grade" value="<?php echo htmlspecialchars($enrollment['grade'] ?? ''); ?>" placeholder="A, B, C, etc.">
                        </div>
                        <button type="submit" class="btn btn-primary">Update Enrollment</button>
                    </form>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </body>
</html>