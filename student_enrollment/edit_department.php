<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_departments.php");
    exit();
}

$department_id = $_GET['id'];

// Get department data
$stmt = $pdo->prepare("SELECT * FROM departments WHERE department_id = ?");
$stmt->execute([$department_id]);
$department = $stmt->fetch();

if (!$department) {
    header("Location: manage_departments.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $department_name = $_POST['department_name'];
    $department_head = $_POST['department_head'];
    $office_location = $_POST['office_location'];
    $phone_number = $_POST['phone_number'];
    $email = $_POST['email'];

    // Check if department name exists (excluding current department)
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE department_name = ? AND department_id != ?");
    $stmt->execute([$department_name, $department_id]);
    if ($stmt->fetch()) {
        $error = "Department name already exists";
    } else {
        $stmt = $pdo->prepare("UPDATE departments SET department_name = ?, department_head = ?, office_location = ?, phone_number = ?, email = ? WHERE department_id = ?");
        if ($stmt->execute([$department_name, $department_head, $office_location, $phone_number, $email, $department_id])) {
            $_SESSION['message'] = "Department updated successfully";
            header("Location: manage_departments.php");
            exit();
        } else {
            $error = "Failed to update department";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Department - Student Enrollment System</title>
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
            <h2>Edit Department</h2>
            <a href="manage_departments.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Departments
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="department_name" class="form-label">Department Name</label>
                        <input type="text" class="form-control" id="department_name" name="department_name" value="<?php echo htmlspecialchars($department['department_name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="department_head" class="form-label">Department Head</label>
                        <input type="text" class="form-control" id="department_head" name="department_head" value="<?php echo htmlspecialchars($department['department_head']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="office_location" class="form-label">Office Location</label>
                        <input type="text" class="form-control" id="office_location" name="office_location" value="<?php echo htmlspecialchars($department['office_location']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($department['phone_number']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($department['email']); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Update Department</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>