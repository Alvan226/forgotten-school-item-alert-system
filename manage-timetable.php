<?php
session_start();
require_once 'includes/db.php';

// Only Course Admins can access this page.
if (!isset($_SESSION["course_admin_id"]) || $_SESSION["role"] !== "course_admin") {
    header("Location: login.php");
    exit();
}

$course_admin_id = $_SESSION["course_admin_id"];
$message = "";
$error = "";

$days = ["Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday"];

// Handle form submissions.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $action = $_POST["action"] ?? "";

    if ($action === "add" || $action === "update") {

        $class_unit = trim($_POST["class_unit"] ?? "");
        $day = $_POST["day_of_week"] ?? "";
        $start_time = $_POST["start_time"] ?? "";
        $end_time = $_POST["end_time"] ?? "";

        if (
            $class_unit === "" ||
            !in_array($day, $days, true) ||
            $start_time === "" ||
            $end_time === ""
        ) {
            $error = "Please fill in all fields correctly.";

        } elseif ($end_time <= $start_time) {
            $error = "The end time must be later than the start time.";

        } else {

            if ($action === "add") {

                $stmt = $conn->prepare(
                    "INSERT INTO TIMETABLE_ENTRY
                    (course_admin_id, class_unit, day_of_week, start_time, end_time)
                    VALUES (?, ?, ?, ?, ?)"
                );

                $stmt->bind_param(
                    "issss",
                    $course_admin_id,
                    $class_unit,
                    $day,
                    $start_time,
                    $end_time
                );

                if ($stmt->execute()) {
                    header("Location: manage-timetable.php?success=added");
                    exit();
                } else {
                    $error = "Could not add the timetable entry.";
                }

                $stmt->close();

            } else {

                $timetable_id = (int)($_POST["timetable_id"] ?? 0);

                $stmt = $conn->prepare(
                    "UPDATE TIMETABLE_ENTRY
                    SET class_unit = ?, day_of_week = ?,
                        start_time = ?, end_time = ?
                    WHERE timetable_id = ? AND course_admin_id = ?"
                );

                $stmt->bind_param(
                    "ssssii",
                    $class_unit,
                    $day,
                    $start_time,
                    $end_time,
                    $timetable_id,
                    $course_admin_id
                );

                if ($stmt->execute() && $stmt->affected_rows >= 0) {
                    header("Location: manage-timetable.php?success=updated");
                    exit();
                } else {
                    $error = "Could not update the timetable entry.";
                }

                $stmt->close();
            }
        }
    }

    // Delete a timetable entry.
    if ($action === "delete") {

        $timetable_id = (int)($_POST["timetable_id"] ?? 0);

        $stmt = $conn->prepare(
            "DELETE FROM TIMETABLE_ENTRY
            WHERE timetable_id = ? AND course_admin_id = ?"
        );

        $stmt->bind_param("ii", $timetable_id, $course_admin_id);

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            header("Location: manage-timetable.php?success=deleted");
            exit();
        } else {
            $error = "Could not delete the timetable entry.";
        }

        $stmt->close();
    }
}

// Display success messages.
if (isset($_GET["success"])) {
    $success = $_GET["success"];

    if ($success === "added") {
        $message = "Timetable entry added successfully.";
    } elseif ($success === "updated") {
        $message = "Timetable entry updated successfully.";
    } elseif ($success === "deleted") {
        $message = "Timetable entry deleted successfully.";
    }
}

// Load an entry for editing.
$edit_entry = null;

if (isset($_GET["edit"])) {

    $timetable_id = (int)$_GET["edit"];

    $stmt = $conn->prepare(
        "SELECT * FROM TIMETABLE_ENTRY
        WHERE timetable_id = ? AND course_admin_id = ?"
    );

    $stmt->bind_param("ii", $timetable_id, $course_admin_id);
    $stmt->execute();

    $result = $stmt->get_result();
    $edit_entry = $result->fetch_assoc();

    $stmt->close();
}

// Get all timetable entries belonging to this Course Admin.
$stmt = $conn->prepare(
    "SELECT * FROM TIMETABLE_ENTRY
    WHERE course_admin_id = ?
    ORDER BY FIELD(day_of_week,
    'Monday', 'Tuesday', 'Wednesday', 'Thursday',
    'Friday', 'Saturday', 'Sunday'), start_time"
);

$stmt->bind_param("i", $course_admin_id);
$stmt->execute();

$timetable_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Timetable | Forgotten School Item Alert System</title>

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
        <a href="course-admin-dashboard.php">Dashboard</a>
        <a href="manage-timetable.php">Manage Timetable</a>
        <a href="manage-items.php">Manage Items</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <section class="page-heading">
        <p class="eyebrow">Course Administration</p>

        <h1>Manage Timetable Entries</h1>

        <p>Add, edit or delete class units and lesson times.</p>
    </section>

    <?php if ($message !== ""): ?>
        <div class="card">
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="card">
            <p><?php echo htmlspecialchars($error); ?></p>
        </div>
    <?php endif; ?>

    <section class="card form-card">

        <h2>
            <?php echo $edit_entry ? "Edit Timetable Entry" : "Add Timetable Entry"; ?>
        </h2>

        <form method="POST" action="manage-timetable.php">

            <input
                type="hidden"
                name="action"
                value="<?php echo $edit_entry ? "update" : "add"; ?>"
            >

            <?php if ($edit_entry): ?>
                <input
                    type="hidden"
                    name="timetable_id"
                    value="<?php echo (int)$edit_entry["timetable_id"]; ?>"
                >
            <?php endif; ?>

            <label for="class_unit">Class Unit</label>

            <input
                type="text"
                id="class_unit"
                name="class_unit"
                placeholder="Enter class unit"
                value="<?php echo htmlspecialchars($edit_entry["class_unit"] ?? ""); ?>"
                required
            >

            <label for="day_of_week">Day of Week</label>

            <select id="day_of_week" name="day_of_week" required>

                <?php foreach ($days as $day): ?>

                    <option
                        value="<?php echo $day; ?>"
                        <?php
                        if (($edit_entry["day_of_week"] ?? "Monday") === $day) {
                            echo "selected";
                        }
                        ?>
                    >
                        <?php echo $day; ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <label for="start_time">Start Time</label>

            <input
                type="time"
                id="start_time"
                name="start_time"
                value="<?php echo htmlspecialchars($edit_entry["start_time"] ?? ""); ?>"
                required
            >

            <label for="end_time">End Time</label>

            <input
                type="time"
                id="end_time"
                name="end_time"
                value="<?php echo htmlspecialchars($edit_entry["end_time"] ?? ""); ?>"
                required
            >

            <button class="button" type="submit">
                <?php echo $edit_entry ? "Update Timetable Entry" : "Add Timetable Entry"; ?>
            </button>

            <?php if ($edit_entry): ?>
                <a href="manage-timetable.php">Cancel Editing</a>
            <?php endif; ?>

        </form>

    </section>

    <section class="card">

        <h2>Existing Timetable Entries</h2>

        <?php if ($timetable_result->num_rows > 0): ?>

            <?php while ($row = $timetable_result->fetch_assoc()): ?>

                <div class="card">

                    <h3>
                        <?php echo htmlspecialchars($row["class_unit"]); ?>
                    </h3>

                    <p>
                        <strong>Day:</strong>
                        <?php echo htmlspecialchars($row["day_of_week"]); ?>
                    </p>

                    <p>
                        <strong>Time:</strong>
                        <?php echo htmlspecialchars(date("H:i", strtotime($row["start_time"]))); ?>
                        -
                        <?php echo htmlspecialchars(date("H:i", strtotime($row["end_time"]))); ?>
                    </p>

                    <a
                        class="button"
                        href="manage-timetable.php?edit=<?php echo (int)$row["timetable_id"]; ?>"
                    >
                        Edit
                    </a>

                    <form method="POST" action="manage-timetable.php"
                          onsubmit="return confirm('Are you sure you want to delete this timetable entry?');">

                        <input type="hidden" name="action" value="delete">

                        <input
                            type="hidden"
                            name="timetable_id"
                            value="<?php echo (int)$row["timetable_id"]; ?>"
                        >

                        <button class="button" type="submit">
                            Delete
                        </button>

                    </form>

                </div>

            <?php endwhile; ?>

        <?php else: ?>

            <p>No timetable entries have been added yet.</p>

        <?php endif; ?>

    </section>

</main>

<footer class="site-footer">
    <p>Forgotten School Item Alert System · Student Project</p>
</footer>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>