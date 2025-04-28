<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle student deletion
if (isset($_GET['delete'])) {
    $student_id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $_SESSION['message'] = "Student deleted successfully";
    header("Location: manage_students.php");
    exit();
}

// Get all students
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

$query = "SELECT s.*, d.department_name FROM students s 
          LEFT JOIN departments d ON s.major_department_id = d.department_id 
          WHERE 1=1";

$params = [];

if ($status_filter) {
    $query .= " AND s.current_status = ?";
    $params[] = $status_filter;
}

if ($search_query) {
    $query .= " AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.email LIKE ? OR s.username LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

$query .= " ORDER BY s.enrollment_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Students - Student Enrollment System</title>
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
            <h2>Manage Students</h2>
            <a href="add_student.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Student
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
                            <input type="text" name="search" class="form-control me-2" placeholder="Search students..." value="<?php echo htmlspecialchars($search_query); ?>">
                            <button type="submit" class="btn btn-outline-primary">Search</button>
                        </form>
                    </div>
                    <div class="col-md-6">
                        <form method="GET" class="d-flex justify-content-end">
                            <select name="status" class="form-select me-2" onchange="this.form.submit()">
                                <option value="">All Statuses</option>
                                <option value="Active" <?php echo $status_filter == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $status_filter == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Graduated" <?php echo $status_filter == 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                                <option value="Suspended" <?php echo $status_filter == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                            <?php if ($status_filter || $search_query): ?>
                                <a href="manage_students.php" class="btn btn-outline-secondary">Clear</a>
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
                                <th>ID</th>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Status</th>
                                <th>Enrollment Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['department_name'] ?? 'Undeclared'); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo strtolower($student['current_status']) == 'active' ? 'success' : (strtolower($student['current_status']) == 'inactive' ? 'warning' : (strtolower($student['current_status']) == 'graduated' ? 'info' : 'danger')); ?>">
                                        <?php echo htmlspecialchars($student['current_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($student['enrollment_date'])); ?></td>
                                <td>
                                    <a href="edit_student.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="view_student.php?id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-outline-info" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="manage_students.php?delete=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this student?')">
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