
<?php
session_start();
require_once 'includes/db.php';

// Only Administrators can access reports.
if (!isset($_SESSION["student_id"]) || $_SESSION["role"] !== "admin") {
    header("Location: login.php");
    exit();
}

// Get system statistics.
$student_result = $conn->query(
    "SELECT COUNT(*) AS total FROM STUDENT WHERE role = 'student'"
);
$total_students = $student_result->fetch_assoc()["total"];

$admin_result = $conn->query(
    "SELECT COUNT(*) AS total FROM COURSE_ADMIN"
);
$total_course_admins = $admin_result->fetch_assoc()["total"];

$timetable_result = $conn->query(
    "SELECT COUNT(*) AS total FROM TIMETABLE_ENTRY"
);
$total_timetable = $timetable_result->fetch_assoc()["total"];

$checklist_result = $conn->query(
    "SELECT COUNT(*) AS total FROM CHECKLIST_STATUS"
);
$total_checklists = $checklist_result->fetch_assoc()["total"];

$packed_result = $conn->query(
    "SELECT COUNT(*) AS total FROM CHECKLIST_STATUS WHERE is_packed = 1"
);
$total_packed = $packed_result->fetch_assoc()["total"];

$pending_result = $conn->query(
    "SELECT COUNT(*) AS total FROM CHECKLIST_STATUS WHERE is_packed = 0"
);
$total_pending = $pending_result->fetch_assoc()["total"];

// Get the latest checklist activity.
$activity_sql = "
    SELECT
        s.full_name,
        si.item_name,
        cs.checklist_date,
        cs.is_packed
    FROM CHECKLIST_STATUS cs
    JOIN STUDENT s
        ON cs.student_id = s.student_id
    JOIN CLASS_ITEM_LINK cil
        ON cs.link_id = cil.link_id
    JOIN SCHOOL_ITEM si
        ON cil.item_id = si.item_id
    ORDER BY cs.checklist_date DESC, cs.status_id DESC
    LIMIT 10
";

$activity_result = $conn->query($activity_sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>System Reports | Forgotten School Item Alert System</title>

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
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <section class="page-heading">

        <p class="eyebrow">Administrator</p>

        <h1>System Reports</h1>

        <p>View student accounts, timetable records and checklist activity.</p>

    </section>

    <section class="grid">

        <article class="card metric">
            <span>Registered Students</span>
            <strong><?php echo $total_students; ?></strong>
        </article>

        <article class="card metric">
            <span>Course Administrators</span>
            <strong><?php echo $total_course_admins; ?></strong>
        </article>

        <article class="card metric">
            <span>Timetable Entries</span>
            <strong><?php echo $total_timetable; ?></strong>
        </article>

        <article class="card metric">
            <span>Checklist Records</span>
            <strong><?php echo $total_checklists; ?></strong>
        </article>

        <article class="card metric">
            <span>Items Packed</span>
            <strong><?php echo $total_packed; ?></strong>
        </article>

        <article class="card metric">
            <span>Items Not Packed</span>
            <strong><?php echo $total_pending; ?></strong>
        </article>

    </section>

    <section class="card">

        <h2>Recent Checklist Activity</h2>

        <?php if ($activity_result && $activity_result->num_rows > 0): ?>

            <div style="overflow-x: auto;">

                <table>
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>School Item</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($row = $activity_result->fetch_assoc()): ?>

                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($row["full_name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row["item_name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row["checklist_date"]); ?>
                                </td>

                                <td>
                                    <?php
                                    echo $row["is_packed"]
                                        ? "Packed"
                                        : "Not Packed";
                                    ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    </tbody>
                </table>

            </div>

        <?php else: ?>

            <p>No checklist activity has been recorded yet.</p>

        <?php endif; ?>

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
