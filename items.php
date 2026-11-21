
<?php
session_start();
require_once 'includes/db.php';

// Only logged-in students can access this page.
if (!isset($_SESSION["student_id"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION["student_id"];

// Get the school items assigned to the student's classes.
$stmt = $conn->prepare(
    "SELECT
        t.class_unit,
        t.day_of_week,
        si.item_name,
        si.description
     FROM STUDENT_TIMETABLE st
     JOIN TIMETABLE_ENTRY t
        ON st.timetable_id = t.timetable_id
     JOIN CLASS_ITEM_LINK cil
        ON t.timetable_id = cil.timetable_id
     JOIN SCHOOL_ITEM si
        ON cil.item_id = si.item_id
     WHERE st.student_id = ?
     ORDER BY FIELD(
        t.day_of_week,
        'Monday', 'Tuesday', 'Wednesday',
        'Thursday', 'Friday', 'Saturday', 'Sunday'
     ), t.start_time"
);

$stmt->bind_param("i", $student_id);
$stmt->execute();
$items = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>School Items | Forgotten School Item Alert System</title>

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
        <a href="timetable.php">Timetable</a>
        <a href="items.php">School Items</a>
        <a href="logout.php">Logout</a>
    </nav>
</header>

<main class="container">

    <section class="page-heading">
        <p class="eyebrow">School items</p>
        <h1>My Required School Items</h1>
        <p>View the items assigned to your classes by your Course Admin.</p>
    </section>

    <div class="card table-wrap">

        <h2>Items for My Classes</h2>

        <table>
            <thead>
                <tr>
                    <th>Class / Unit</th>
                    <th>Lesson Day</th>
                    <th>Required Item</th>
                    <th>Description</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($items->num_rows > 0): ?>

                <?php while ($item = $items->fetch_assoc()): ?>

                    <tr>
                        <td>
                            <?php echo htmlspecialchars($item["class_unit"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($item["day_of_week"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($item["item_name"]); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($item["description"] ?? ""); ?>
                        </td>
                    </tr>

                <?php endwhile; ?>

            <?php else: ?>

                <tr>
                    <td colspan="4">
                        No school items have been assigned to your classes yet.
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
$conn->close();
?>
