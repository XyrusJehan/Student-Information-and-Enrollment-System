<div class="sidebar">
    <div class="text-center py-4">
        <h4>Admin Dashboard</h4>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : ''; ?>" href="admin_dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_students.php' ? 'active' : ''; ?>" href="manage_students.php">
                <i class="bi bi-people-fill"></i> Manage Students
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_courses.php' ? 'active' : ''; ?>" href="manage_courses.php">
                <i class="bi bi-book-half"></i> Manage Courses
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_departments.php' ? 'active' : ''; ?>" href="manage_departments.php">
                <i class="bi bi-building"></i> Manage Departments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_enrollments.php' ? 'active' : ''; ?>" href="manage_enrollments.php">
                <i class="bi bi-clipboard-check"></i> Manage Enrollments
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_semesters.php' ? 'active' : ''; ?>" href="manage_semesters.php">
                <i class="bi bi-calendar-range"></i> Manage Semesters
            </a>
        </li>
        <li class="nav-item mt-3">
            <a class="nav-link" href="logout.php">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </li>
    </ul>
</div>