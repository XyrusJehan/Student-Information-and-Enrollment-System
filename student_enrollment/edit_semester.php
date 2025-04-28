<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user']) || $_SESSION['userType'] != 'admin') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: manage_semesters.php");
    exit();
}

$semester_id = $_GET['id'];

// Get semester data
$stmt = $pdo->prepare("SELECT * FROM semesters WHERE semester_id = ?");
$stmt->execute([$semester_id]);
$semester = $stmt->fetch();

if (!$semester) {
    header("Location: manage_semesters.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_semester_id = $_POST['semester_id'];
    $semester_name = $_POST['semester_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $registration_start = $_POST['registration_start'] ?: null;
    $registration_end = $_POST['registration_end'] ?: null;

    // Check if semester ID exists (excluding current semester)
    if ($new_semester_id != $semester_id) {
        $stmt = $pdo->prepare("SELECT * FROM semesters WHERE semester_id = ?");
        $stmt->execute([$new_semester_id]);
        if ($stmt->fetch()) {
            $error = "Semester ID already exists";
        }
    }

    if (!isset($error)) {
        $stmt = $pdo->prepare("UPDATE semesters SET semester_id = ?, semester_name = ?, start_date = ?, end_date = ?, registration_start = ?, registration_end = ? WHERE semester_id = ?");
        if ($stmt->execute([$new_semester_id, $semester_name, $start_date, $end_date, $registration_start, $registration_end, $semester_id])) {
            $_SESSION['message'] = "Semester updated successfully";
            header("Location: manage_semesters.php");
            exit();
        } else {
            $error = "Failed to update semester";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Semester - Student Enrollment System</title>
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
            <h2>Edit Semester</h2>
            <a href="manage_semesters.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Semesters
            </a>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="semester_id" class="form-label">Semester ID</label>
                        <input type="text" class="form-control" id="semester_id" name="semester_id" value="<?php echo htmlspecialchars($semester['semester_id']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="semester_name" class="form-label">Semester Name</label>
                        <input type="text" class="form-control" id="semester_name" name="semester_name" value="<?php echo htmlspecialchars($semester['semester_name']); ?>" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($semester['start_date']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($semester['end_date']); ?>" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="registration_start" class="form-label">Registration Start Date</label>
                            <input type="date" class="form-control" id="registration_start" name="registration_start" value="<?php echo htmlspecialchars($semester['registration_start']); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="registration_end" class="form-label">Registration End Date</label>
                            <input type="date" class="form-control" id="registration_end" name="registration_end" value="<?php echo htmlspecialchars($semester['registration_end']); ?>">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Semester</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>