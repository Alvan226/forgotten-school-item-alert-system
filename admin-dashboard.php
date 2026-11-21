
<?php
session_start();
require_once 'includes/db.php';

// Only Administrators can access this page.
if (!isset($_SESSION["student_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Function to count database records.
function getCount($conn, $table) {
    $allowed_tables = [
        "STUDENT",
        "COURSE_ADMIN",
        "TIMETABLE_ENTRY",
        "SCHOOL_ITEM",
        "NOTIFICATION"
    ];

    if (!in_array($table, $allowed_tables, true)) {
        return 0;
    }

    $result = $conn->query("SELECT COUNT(*) AS total FROM `$table`");

    if ($result) {
        $row = $result->fetch_assoc();
        return $row["total"];
    }

    return 0;
}

$total_students = getCount($conn, "STUDENT");
$total_admins = getCount($conn, "COURSE_ADMIN");
$total_timetable = getCount($conn, "TIMETABLE_ENTRY");
$total_items = getCount($conn, "SCHOOL_ITEM");
$total_notifications = getCount($conn, "NOTIFICATION");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Administrator Dashboard | Forgotten School Item Alert System</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header class="site-header">

    <div class="brand">
        <span class="brand-icon">FS</span>

        <div>
            <strong>Forgotten School Item Alert System</strong>
            <small>Plan your day. Pack with confidence.</small>
        </div>
    </div>

    <nav>
        <a href="admin-dashboard.php">Dashboard</a>
        <a href="reports.php">Reports</a>
        <a href="manage-users.php">Manage Users</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <section class="page-heading">

        <p class="eyebrow">Administrator</p>

        <h1>Administrator Dashboard</h1>

        <p>Monitor system activity and view important statistics.</p>

    </section>

    <section class="grid">

        <div class="card">
            <h2><?php echo $total_students; ?></h2>
            <p>Registered Students</p>
        </div>

        <div class="card">
            <h2><?php echo $total_admins; ?></h2>
            <p>Course Administrators</p>
        </div>

        <div class="card">
            <h2><?php echo $total_timetable; ?></h2>
            <p>Timetable Entries</p>
        </div>

        <div class="card">
            <h2><?php echo $total_items; ?></h2>
            <p>School Items</p>
        </div>

        <div class="card">
            <h2><?php echo $total_notifications; ?></h2>
            <p>Notifications</p>
        </div>

    </section>

    <section class="grid">

        <a class="card link-card" href="reports.php">
            <span>System Administration</span>
            <h2>View System Reports →</h2>
            <p>View information about system activity.</p>
        </a>

        <a class="card link-card" href="manage-users.php">
            <span>User Administration</span>
            <h2>Manage Users →</h2>
            <p>View and manage registered Students and Course Administrators.</p>
        </a>

    </section>

</main>

<footer class="site-footer">
    <p>Forgotten School Item Alert System · Student Project</p>
</footer>

</body>
</html>

<?php
$conn->close();
?>
