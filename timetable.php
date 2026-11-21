<?php
session_start();
require_once 'includes/db.php';

if (!isset($_SESSION["student_id"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION["student_id"];

$sql = "SELECT 
            t.class_unit,
            t.day_of_week,
            t.start_time,
            t.end_time
        FROM STUDENT_TIMETABLE st
        JOIN TIMETABLE_ENTRY t
            ON st.timetable_id = t.timetable_id
        WHERE st.student_id = ?
        ORDER BY 
            FIELD(t.day_of_week, 'Monday', 'Tuesday', 'Wednesday',
            'Thursday', 'Friday', 'Saturday', 'Sunday'),
            t.start_time";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Timetable | Forgotten School Item Alert System</title>

    <link rel="stylesheet" href="css/style.css">
    <script src="js/script.js" defer></script>
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

    <nav aria-label="Main navigation">
        <a href="student-dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <section class="page-heading">
        <p class="eyebrow">Timetable</p>
        <h1>My class timetable</h1>
        <p>Check the lessons and class times assigned to you.</p>
    </section>

    <div class="card table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Day</th>
                    <th>Class unit</th>
                    <th>Start time</th>
                    <th>End time</th>
                </tr>
            </thead>

            <tbody>

                <?php if ($result->num_rows > 0): ?>

                    <?php while ($row = $result->fetch_assoc()): ?>

                        <tr>
                            <td><?php echo htmlspecialchars($row["day_of_week"]); ?></td>

                            <td><?php echo htmlspecialchars($row["class_unit"]); ?></td>

                            <td><?php echo date("h:i A", strtotime($row["start_time"])); ?></td>

                            <td><?php echo date("h:i A", strtotime($row["end_time"])); ?></td>
                        </tr>

                    <?php endwhile; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="4">
                            No timetable entries have been assigned to you yet.
                        </td>
                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

<footer class="site-footer">
    <p>Forgotten School Item Alert System · Student project</p>
</footer>

</body>
</html>

<?php
$stmt->close();
?>
