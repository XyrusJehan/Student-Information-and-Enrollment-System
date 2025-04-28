<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_students.php");
    exit();
}

$student_id = $_GET['id'];
$departments = $pdo->query("SELECT * FROM departments")->fetchAll();

// Get student data
$stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: manage_students.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'] ? $_POST['password'] : $student['password'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $address = $_POST['address'];
    $date_of_birth = $_POST['date_of_birth'];
    $major_department_id = $_POST['major_department_id'] ?: null;
    $current_status = $_POST['current_status'];

    // Check if username or email exists (excluding current student)
    $stmt = $pdo->prepare("SELECT * FROM students WHERE (username = ? OR email = ?) AND student_id != ?");
    $stmt->execute([$username, $email, $student_id]);
    if ($stmt->fetch()) {
        $error = "Username or email already exists";
    } else {
        $stmt = $pdo->prepare("UPDATE students SET username = ?, password = ?, first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, date_of_birth = ?, major_department_id = ?, current_status = ? WHERE student_id = ?");
        if ($stmt->execute([$username, $password, $first_name, $last_name, $email, $phone_number, $address, $date_of_birth, $major_department_id, $current_status, $student_id])) {
            $_SESSION['message'] = "Student updated successfully";
            header("Location: manage_students.php");
            exit();
        } else {
            $error = "Failed to update student";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - Student Enrollment System</title>
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
            <h2>Edit Student</h2>
            <a href="manage_students.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Students
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
                            <label for="first_name" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="last_name" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($student['username']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">Password (leave blank to keep current)</label>
                            <input type="password" class="form-control" id="password" name="password">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="phone_number" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($student['phone_number']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="date_of_birth" class="form-label">Date of Birth</label>
                            <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" value="<?php echo htmlspecialchars($student['date_of_birth']); ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Address</label>
                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($student['address']); ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="major_department_id" class="form-label">Department</label>
                            <select class="form-select" id="major_department_id" name="major_department_id">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['department_id']; ?>" <?php echo $student['major_department_id'] == $dept['department_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="current_status" class="form-label">Status</label>
                            <select class="form-select" id="current_status" name="current_status" required>
                                <option value="Active" <?php echo $student['current_status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $student['current_status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Graduated" <?php echo $student['current_status'] == 'Graduated' ? 'selected' : ''; ?>>Graduated</option>
                                <option value="Suspended" <?php echo $student['current_status'] == 'Suspended' ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Student</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>