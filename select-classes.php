<?php
session_start();

require_once 'includes/db.php';

// Only logged-in students can access this page.
if (!isset($_SESSION["student_id"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit();
}

$student_id = (int) $_SESSION["student_id"];
$message = "";
$message_type = "";

// Create CSRF token.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

// Process selected classes.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        $message = "Invalid request. Please try again.";
        $message_type = "error";

    } else {

        $selected_ids = $_POST["classes"] ?? [];

        if (!is_array($selected_ids)) {
            $selected_ids = [];
        }

        $selected_ids = array_unique(
            array_filter(
                array_map('intval', $selected_ids),
                fn($id) => $id > 0
            )
        );

        try {
            $conn->begin_transaction();

            // Get valid timetable entries.
            $valid_ids = [];

            if (!empty($selected_ids)) {

                $placeholders = implode(
                    ",",
                    array_fill(0, count($selected_ids), "?")
                );

                $types = str_repeat("i", count($selected_ids));

                $stmt = $conn->prepare(
                    "SELECT timetable_id
                     FROM TIMETABLE_ENTRY
                     WHERE timetable_id IN ($placeholders)"
                );

                $stmt->bind_param($types, ...$selected_ids);
                $stmt->execute();

                $result = $stmt->get_result();

                while ($row = $result->fetch_assoc()) {
                    $valid_ids[] = (int) $row["timetable_id"];
                }

                $stmt->close();
            }

            // Remove previous selections that are no longer selected.
            if (!empty($valid_ids)) {

                $placeholders = implode(
                    ",",
                    array_fill(0, count($valid_ids), "?")
                );

                $types = "i" . str_repeat("i", count($valid_ids));

                $stmt = $conn->prepare(
                    "DELETE FROM STUDENT_TIMETABLE
                     WHERE student_id = ?
                     AND timetable_id NOT IN ($placeholders)"
                );

                $params = array_merge([$student_id], $valid_ids);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();

            } else {

                $stmt = $conn->prepare(
                    "DELETE FROM STUDENT_TIMETABLE
                     WHERE student_id = ?"
                );

                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $stmt->close();
            }

            // Add newly selected classes.
            foreach ($valid_ids as $timetable_id) {

                $stmt = $conn->prepare(
                    "SELECT assignment_id
                     FROM STUDENT_TIMETABLE
                     WHERE student_id = ?
                     AND timetable_id = ?
                     LIMIT 1"
                );

                $stmt->bind_param("ii", $student_id, $timetable_id);
                $stmt->execute();

                $exists = $stmt->get_result()->num_rows > 0;
                $stmt->close();

                if (!$exists) {

                    $stmt = $conn->prepare(
                        "INSERT INTO STUDENT_TIMETABLE
                         (student_id, timetable_id, assigned_at)
                         VALUES (?, ?, NOW())"
                    );

                    $stmt->bind_param("ii", $student_id, $timetable_id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            $conn->commit();

            $message = "Your class selections have been saved successfully!";
            $message_type = "success";

        } catch (Exception $e) {

            $conn->rollback();

            $message = "Unable to save your selections. Please try again.";
            $message_type = "error";
        }
    }
}

// Get all available classes.
$sql = "
    SELECT
        t.timetable_id,
        t.class_unit,
        t.day_of_week,
        t.start_time,
        t.end_time,
        ca.full_name AS admin_name,
        CASE
            WHEN st.assignment_id IS NOT NULL THEN 1
            ELSE 0
        END AS is_selected
    FROM TIMETABLE_ENTRY t
    LEFT JOIN COURSE_ADMIN ca
        ON t.course_admin_id = ca.course_admin_id
    LEFT JOIN STUDENT_TIMETABLE st
        ON t.timetable_id = st.timetable_id
        AND st.student_id = ?
    ORDER BY
        FIELD(
            t.day_of_week,
            'Monday',
            'Tuesday',
            'Wednesday',
            'Thursday',
            'Friday',
            'Saturday',
            'Sunday'
        ),
        t.start_time
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();

$classes = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Select Classes | Forgotten School Item Alert System</title>

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
        <a href="timetable.php">My Timetable</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <section class="page-heading">

        <p class="eyebrow">Student classes</p>

        <h1>Select My Classes</h1>

        <p>
            Choose the classes you attend. Your timetable and school
            item requirements will be based on your selections.
        </p>

    </section>

    <?php if ($message !== ""): ?>
        <div class="<?php echo htmlspecialchars($message_type); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="select-classes.php">

        <input
            type="hidden"
            name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>"
        >

        <?php if ($classes->num_rows > 0): ?>

            <div class="grid">

                <?php while ($class = $classes->fetch_assoc()): ?>

                    <label class="card">

                        <input
                            type="checkbox"
                            name="classes[]"
                            value="<?php echo (int) $class["timetable_id"]; ?>"
                            <?php echo $class["is_selected"] ? "checked" : ""; ?>
                        >

                        <strong>
                            <?php echo htmlspecialchars($class["class_unit"]); ?>
                        </strong>

                        <p>
                            Day:
                            <?php echo htmlspecialchars($class["day_of_week"]); ?>
                        </p>

                        <p>
                            Time:
                            <?php echo htmlspecialchars(date("g:i A", strtotime($class["start_time"]))); ?>
                            -
                            <?php echo htmlspecialchars(date("g:i A", strtotime($class["end_time"]))); ?>
                        </p>

                        <small class="muted">
                            Course Admin:
                            <?php echo htmlspecialchars($class["admin_name"] ?? "Not specified"); ?>
                        </small>

                        <?php if ($class["is_selected"]): ?>
                            <p><span class="status">Selected</span></p>
                        <?php endif; ?>

                    </label>

                <?php endwhile; ?>

            </div>

            <button type="submit" class="button">
                Save My Classes
            </button>

        <?php else: ?>

            <div class="card">
                <h2>No classes available yet</h2>

                <p>
                    Your Course Admin has not added any timetable entries.
                    Please check again later.
                </p>
            </div>

        <?php endif; ?>

    </form>

</main>

<footer class="site-footer">
    <p>Forgotten School Item Alert System · Student project</p>
</footer>

</body>
</html>

<?php
$stmt->close();
?>