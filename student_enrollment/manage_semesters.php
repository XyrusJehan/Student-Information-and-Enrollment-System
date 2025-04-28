<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Handle semester deletion
if (isset($_GET['delete'])) {
    $semester_id = $_GET['delete'];
    
    // Check if semester has enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE semester_id = ?");
    $stmt->execute([$semester_id]);
    $enrollment_count = $stmt->fetchColumn();
    
    if ($enrollment_count > 0) {
        $_SESSION['error'] = "Cannot delete semester with associated enrollments";
    } else {
        $stmt = $pdo->prepare("DELETE FROM semesters WHERE semester_id = ?");
        if ($stmt->execute([$semester_id])) {
            $_SESSION['message'] = "Semester deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete semester";
        }
    }
    header("Location: manage_semesters.php");
    exit();
}

// Get all semesters
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY start_date DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Semesters - Student Enrollment System</title>
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
            <h2>Manage Semesters</h2>
            <a href="add_semester.php" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add New Semester
            </a>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['message']; unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Semester ID</th>
                                <th>Semester Name</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Registration Period</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($semesters as $semester): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($semester['semester_id']); ?></td>
                                <td><?php echo htmlspecialchars($semester['semester_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($semester['start_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($semester['end_date'])); ?></td>
                                <td>
                                    <?php if ($semester['registration_start'] && $semester['registration_end']): ?>
                                        <?php echo date('M d', strtotime($semester['registration_start'])); ?> - 
                                        <?php echo date('M d, Y', strtotime($semester['registration_end'])); ?>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="edit_semester.php?id=<?php echo $semester['semester_id']; ?>" class="btn btn-sm btn-outline-primary" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="manage_semesters.php?delete=<?php echo $semester['semester_id']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this semester?')">
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