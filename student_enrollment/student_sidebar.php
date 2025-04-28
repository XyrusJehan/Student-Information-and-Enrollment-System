<div class="sidebar">
    <div class="text-center py-4">
        <h4>Student Portal</h4>
    </div>
    <ul class="nav flex-column">
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_dashboard.php' ? 'active' : ''; ?>" href="student_dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_profile.php' ? 'active' : ''; ?>" href="student_profile.php">
                <i class="bi bi-person-circle"></i> My Profile
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_courses.php' ? 'active' : ''; ?>" href="student_courses.php">
                <i class="bi bi-book-half"></i> My Courses
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_schedule.php' ? 'active' : ''; ?>" href="student_schedule.php">
                <i class="bi bi-calendar-week"></i> My Schedule
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'student_enroll.php' ? 'active' : ''; ?>" href="student_enroll.php">
                <i class="bi bi-plus-circle"></i> Enroll in Courses
            </a>
        </li>
        <li class="nav-item mt-3">
            <a class="nav-link" href="logout.php">
                <i class="bi bi-box-arrow-left"></i> Logout
            </a>
        </li>
    </ul>
</div>