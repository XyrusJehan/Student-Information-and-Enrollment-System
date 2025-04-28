<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle course deletion
if (isset($_GET['delete'])) {
    $course_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $_SESSION['message'] = "Course deleted successfully";
    header("Location: manage_courses.php");
    exit();
}

// Get all courses with department info
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

$query = "SELECT c.*, d.department_name FROM courses c 
          LEFT JOIN departments d ON c.department_id = d.department_id 
          WHERE 1=1";

$params = [];

if ($status_filter !== '') {
    $query .= " AND c.is_active = ?";
    $params[] = $status_filter;
}

if ($search_query) {
    $query .= " AND (c.course_code LIKE ? OR c.course_name LIKE ? OR d.department_name LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

$query .= " ORDER BY c.course_code";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$courses = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - Student Enrollment System</title>
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
            <h2>Manage Courses</h2>
            <a href="add_course.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Course
            </a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header">
                <div class="row">
                    <div class="col-md-6">
                        <form method="GET" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" placeholder="Search courses..." value="<?php echo htmlspecialchars($search_query); ?>">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex justify-content-end">
                            <select name="status" class="form-select me-2" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="1" <?php echo $status_filter === '1' ? 'selected' : ''; ?>>Active</option>
                                <option value="0" <?php echo $status_filter === '0' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                            <?php if ($status_filter !== '' || $search_query): ?>
                                <a href="manage_courses.php" class="btn btn-outline-secondary">Clear</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Course Name</th>
                                <th>Department</th>
                                <th>Credits</th>
                                <th>Capacity</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($courses as $course): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_code']); ?></td>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['department_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($course['credits']); ?></td>
                                <td><?php echo htmlspecialchars($course['max_capacity']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $course['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $course['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="edit_course.php?id=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="manage_courses.php?delete=<?php echo $course['course_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this course?')">
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