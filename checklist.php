
<?php
session_start();
require_once 'includes/db.php';

// Allow only logged-in students.
if (!isset($_SESSION["student_id"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION["student_id"];
$message = "";

// Get today's required items.
function getTodayItems($conn, $student_id) {

    $stmt = $conn->prepare(
        "SELECT
            cil.link_id,
            t.class_unit,
            t.start_time,
            si.item_name,
            si.description,
            cs.is_packed
         FROM STUDENT_TIMETABLE st
         JOIN TIMETABLE_ENTRY t
            ON st.timetable_id = t.timetable_id
         JOIN CLASS_ITEM_LINK cil
            ON t.timetable_id = cil.timetable_id
         JOIN SCHOOL_ITEM si
            ON cil.item_id = si.item_id
         LEFT JOIN CHECKLIST_STATUS cs
            ON cs.link_id = cil.link_id
            AND cs.student_id = st.student_id
            AND cs.checklist_date = CURDATE()
         WHERE st.student_id = ?
            AND t.day_of_week = DAYNAME(CURDATE())
         ORDER BY t.start_time, si.item_name"
    );

    $stmt->bind_param("i", $student_id);
    $stmt->execute();

    return $stmt->get_result();
}

// Save checklist when submitted.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $selected_items = $_POST["packed"] ?? [];

    // Retrieve the items assigned to this student today.
    $items = getTodayItems($conn, $student_id);

    $today_ids = [];

    while ($item = $items->fetch_assoc()) {
        $today_ids[] = (int)$item["link_id"];
    }

    // First mark today's items as not packed.
    $stmt = $conn->prepare(
        "UPDATE CHECKLIST_STATUS
         SET is_packed = FALSE
         WHERE student_id = ?
         AND checklist_date = CURDATE()"
    );

    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stmt->close();

    // Save each selected item as packed.
    foreach ($today_ids as $link_id) {

        $is_packed = in_array(
            $link_id,
            array_map('intval', $selected_items),
            true
        );

        $stmt = $conn->prepare(
            "SELECT status_id
             FROM CHECKLIST_STATUS
             WHERE student_id = ?
             AND link_id = ?
             AND checklist_date = CURDATE()
             LIMIT 1"
        );

        $stmt->bind_param("ii", $student_id, $link_id);
        $stmt->execute();

        $existing = $stmt->get_result();

        if ($existing->num_rows > 0) {

            $status = $existing->fetch_assoc();
            $status_id = $status["status_id"];

            $stmt->close();

            $stmt = $conn->prepare(
                "UPDATE CHECKLIST_STATUS
                 SET is_packed = ?
                 WHERE status_id = ?"
            );

            $stmt->bind_param("ii", $is_packed, $status_id);

        } else {

            $stmt->close();

            $stmt = $conn->prepare(
                "INSERT INTO CHECKLIST_STATUS
                 (student_id, link_id, checklist_date, is_packed)
                 VALUES (?, ?, CURDATE(), ?)"
            );

            $stmt->bind_param(
                "iii",
                $student_id,
                $link_id,
                $is_packed
            );
        }

        $stmt->execute();
        $stmt->close();
    }

    $message = "Your checklist has been saved successfully.";
}

// Load the updated checklist.
$items = getTodayItems($conn, $student_id);

$total_items = $items->num_rows;
$packed_count = 0;

$checklist_items = [];

while ($item = $items->fetch_assoc()) {

    if ($item["is_packed"]) {
        $packed_count++;
    }

    $checklist_items[] = $item;
}

$percentage = $total_items > 0
    ? round(($packed_count / $total_items) * 100)
    : 0;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Daily Checklist | Forgotten School Item Alert System</title>

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

        <p class="eyebrow">Daily checklist</p>

        <h1>My Daily Packing Checklist</h1>

        <p>
            Tick each item after packing it for today's classes.
        </p>

    </section>

    <?php if (!empty($message)): ?>

        <div class="card">
            <p style="color: green;">
                <?php echo htmlspecialchars($message); ?>
            </p>
        </div>

    <?php endif; ?>

    <div class="card">

        <h2>Today's Packing Progress</h2>

        <p>
            <?php echo $packed_count; ?>
            out of
            <?php echo $total_items; ?>
            items packed.
        </p>

        <div style="background: #ddd; height: 15px; border-radius: 10px; overflow: hidden;">

            <div style="
                width: <?php echo $percentage; ?>%;
                background: #218838;
                height: 15px;
            "></div>

        </div>

        <p><?php echo $percentage; ?>% completed</p>

    </div>

    <form method="POST" action="checklist.php">

        <div class="card checklist">

            <h2>Items to Pack Today</h2>

            <?php if ($total_items > 0): ?>

                <?php foreach ($checklist_items as $item): ?>

                    <label style="display: block; margin: 15px 0;">

                        <input
                            type="checkbox"
                            name="packed[]"
                            value="<?php echo (int)$item["link_id"]; ?>"
                            <?php echo $item["is_packed"] ? "checked" : ""; ?>
                        >

                        <strong>
                            <?php echo htmlspecialchars($item["item_name"]); ?>
                        </strong>

                        — <?php echo htmlspecialchars($item["class_unit"]); ?>

                        <br>

                        <small>
                            <?php echo htmlspecialchars($item["description"] ?? ""); ?>
                        </small>

                    </label>

                <?php endforeach; ?>

                <br>

                <button type="submit" class="button">
                    Save Checklist
                </button>

            <?php else: ?>

                <p>
                    No school items have been assigned to your classes today.
                </p>

            <?php endif; ?>

        </div>

    </form>

</main>

<footer class="site-footer">
    <p>Forgotten School Item Alert System · Student project</p>
</footer>

</body>
</html>

<?php
$conn->close();
?>