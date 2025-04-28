<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_courses.php");
    exit();
}

$course_id = $_GET['id'];
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();

// Get course data
$stmt = $pdo->prepare("SELECT * FROM courses WHERE course_id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch();

if (!$course) {
    header("Location: manage_courses.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_code = $_POST['course_code'];
    $course_name = $_POST['course_name'];
    $description = $_POST['description'];
    $credits = $_POST['credits'];
    $department_id = $_POST['department_id'] ?: null;
    $max_capacity = $_POST['max_capacity'];
    $prerequisites = $_POST['prerequisites'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Check if course code exists (excluding current course)
    $stmt = $pdo->prepare("SELECT * FROM courses WHERE course_code = ? AND course_id != ?");
    $stmt->execute([$course_code, $course_id]);
    if ($stmt->fetch()) {
        $error = "Course code already exists";
    } else {
        $stmt = $pdo->prepare("UPDATE courses SET course_code = ?, course_name = ?, description = ?, credits = ?, department_id = ?, max_capacity = ?, prerequisites = ?, is_active = ? WHERE course_id = ?");
        if ($stmt->execute([$course_code, $course_name, $description, $credits, $department_id, $max_capacity, $prerequisites, $is_active, $course_id])) {
            $_SESSION['message'] = "Course updated successfully";
            header("Location: manage_courses.php");
            exit();
        } else {
            $error = "Failed to update course";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Course - Student Enrollment System</title>
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
            <h2>Edit Course</h2>
            <a href="manage_courses.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Courses
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="course_code" class="form-label">Course Code</label>
                            <input type="text" class="form-control" id="course_code" name="course_code" value="<?php echo htmlspecialchars($course['course_code']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="course_name" class="form-label">Course Name</label>
                            <input type="text" class="form-control" id="course_name" name="course_name" value="<?php echo htmlspecialchars($course['course_name']); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($course['description']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="credits" class="form-label">Credits</label>
                            <input type="number" class="form-control" id="credits" name="credits" min="1" max="10" value="<?php echo htmlspecialchars($course['credits']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="department_id" class="form-label">Department</label>
                            <select class="form-select" id="department_id" name="department_id">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department_id']; ?>" <?php echo $course['department_id'] == $dept['department_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="max_capacity" class="form-label">Max Capacity</label>
                            <input type="number" class="form-control" id="max_capacity" name="max_capacity" min="1" value="<?php echo htmlspecialchars($course['max_capacity']); ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="prerequisites" class="form-label">Prerequisites</label>
                        <input type="text" class="form-control" id="prerequisites" name="prerequisites" value="<?php echo htmlspecialchars($course['prerequisites']); ?>" placeholder="e.g., CS101, MATH201">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_active" name="is_active" <?php echo $course['is_active'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_active">Active Course</label>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>