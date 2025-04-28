<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle department deletion
if (isset($_GET['delete'])) {
    $department_id = $_GET['delete'];
    
    // Check if department has courses or students
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM courses WHERE department_id = ?");
    $stmt->execute([$department_id]);
    $course_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE major_department_id = ?");
    $stmt->execute([$department_id]);
    $student_count = $stmt->fetchColumn();
    
    if ($course_count > 0 || $student_count > 0) {
        $_SESSION['error'] = "Cannot delete department with associated courses or students";
    } else {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE department_id = ?");
        if ($stmt->execute([$department_id])) {
            $_SESSION['message'] = "Department deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete department";
        }
    }
    header("Location: manage_departments.php");
    exit();
}

// Get all departments
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$query = "SELECT * FROM departments WHERE 1=1";
$params = [];

if ($search_query) {
    $query .= " AND (department_name LIKE ? OR department_head LIKE ? OR email LIKE ?)";
    $search_param = "%$search_query%";
    $params = array_merge($params, [$search_param, $search_param, $search_param]);
}

$query .= " ORDER BY department_name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$departments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments - Student Enrollment System</title>
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
            <h2>Manage Departments</h2>
            <a href="add_department.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Department
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
                <form method="GET" class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search departments..." value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit" class="btn btn-outline-primary">Search</button>
                    <?php if ($search_query): ?>
                        <a href="manage_departments.php" class="btn btn-outline-secondary ms-2">Clear</a>
                    <?php endif; ?>
                </form>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Department Name</th>
                                <th>Department Head</th>
                                <th>Office Location</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $department): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($department['department_name']); ?></td>
                                <td><?php echo htmlspecialchars($department['department_head']); ?></td>
                                <td><?php echo htmlspecialchars($department['office_location']); ?></td>
                                <td><?php echo htmlspecialchars($department['email']); ?></td>
                                <td>
                                    <a href="edit_department.php?id=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="manage_departments.php?delete=<?php echo $department['department_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this department?')">
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