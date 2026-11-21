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
            t.start_time,
            s.item_name,
            s.description
        FROM STUDENT_TIMETABLE st
        JOIN TIMETABLE_ENTRY t
            ON st.timetable_id = t.timetable_id
        JOIN CLASS_ITEM_LINK cil
            ON t.timetable_id = cil.timetable_id
        JOIN SCHOOL_ITEM s
            ON cil.item_id = s.item_id
        LEFT JOIN CHECKLIST_STATUS cs
            ON cs.link_id = cil.link_id
            AND cs.student_id = st.student_id
            AND cs.checklist_date = CURDATE()
        WHERE st.student_id = ?
            AND t.day_of_week = DAYNAME(CURDATE())
            AND COALESCE(cs.is_packed, 0) = 0
        ORDER BY t.start_time";

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

    <title>Reminders | Forgotten School Item Alert System</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<header>
    <h1>School Item Reminders</h1>

    <nav>
        <a href="student-dashboard.php">Dashboard</a>
        <a href="timetable.php">Timetable</a>
        <a href="items.php">School Items</a>
        <a href="checklist.php">Daily Checklist</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <h2>Items You Still Need to Pack</h2>

    <p>
        Check the reminders below before attending your classes today.
    </p>

    <?php if ($result->num_rows > 0): ?>

        <?php while ($row = $result->fetch_assoc()): ?>

            <div class="card">

                <h3>
                    <?php echo htmlspecialchars($row["item_name"]); ?>
                </h3>

                <p>
                    <strong>Class:</strong>
                    <?php echo htmlspecialchars($row["class_unit"]); ?>
                </p>

                <p>
                    <strong>Class starts:</strong>
                    <?php echo htmlspecialchars($row["start_time"]); ?>
                </p>

                <p>
                    <strong>Description:</strong>
                    <?php echo htmlspecialchars($row["description"] ?? "No description provided."); ?>
                </p>

                <p><strong>Status:</strong> Not yet packed</p>

            </div>

        <?php endwhile; ?>

    <?php else: ?>

        <div class="card">
            <h3>You're all caught up!</h3>
            <p>
                There are no outstanding school items for today's classes.
            </p>
        </div>

    <?php endif; ?>

</main>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
