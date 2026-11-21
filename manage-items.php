
<?php
session_start();
require_once 'includes/db.php';

// Only Course Admins can access this page
if (
    !isset($_SESSION["course_admin_id"]) ||
    ($_SESSION["role"] ?? "") !== "course_admin"
) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION["course_admin_id"];
$message = "";
$error = "";

// Add a required item to a class
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $timetable_id = (int)($_POST["timetable_id"] ?? 0);
    $item_name = trim($_POST["item_name"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($timetable_id <= 0 || $item_name === "") {

        $error = "Please select a class and enter an item name.";

    } else {

        // Confirm that the selected class belongs to this Course Admin
        $check = $conn->prepare(
            "SELECT timetable_id
             FROM TIMETABLE_ENTRY
             WHERE timetable_id = ? AND course_admin_id = ?"
        );

        $check->bind_param("ii", $timetable_id, $admin_id);
        $check->execute();
        $class_result = $check->get_result();

        if ($class_result->num_rows !== 1) {

            $error = "Invalid class selection.";

        } else {

            // Create the school item
            $stmt = $conn->prepare(
                "INSERT INTO SCHOOL_ITEM (item_name, description)
                 VALUES (?, ?)"
            );

            $stmt->bind_param("ss", $item_name, $description);

            if ($stmt->execute()) {

                $item_id = $conn->insert_id;

                // Link the item to the selected class
                $link = $conn->prepare(
                    "INSERT INTO CLASS_ITEM_LINK (timetable_id, item_id)
                     VALUES (?, ?)"
                );

                $link->bind_param("ii", $timetable_id, $item_id);

                if ($link->execute()) {
                    $message = "Required item added successfully!";
                } else {
                    $error = "The item was created but could not be assigned.";
                }

                $link->close();

            } else {
                $error = "Unable to add the item.";
            }

            $stmt->close();
        }

        $check->close();
    }
}

// Get classes belonging to this Course Admin
$class_stmt = $conn->prepare(
    "SELECT timetable_id, class_unit, day_of_week, start_time
     FROM TIMETABLE_ENTRY
     WHERE course_admin_id = ?
     ORDER BY class_unit, day_of_week"
);

$class_stmt->bind_param("i", $admin_id);
$class_stmt->execute();
$classes = $class_stmt->get_result();

// Get items assigned to this Course Admin's classes
$item_stmt = $conn->prepare(
    "SELECT
        s.item_name,
        s.description,
        t.class_unit,
        t.day_of_week
     FROM CLASS_ITEM_LINK l
     JOIN SCHOOL_ITEM s ON l.item_id = s.item_id
     JOIN TIMETABLE_ENTRY t ON l.timetable_id = t.timetable_id
     WHERE t.course_admin_id = ?
     ORDER BY t.class_unit, s.item_name"
);

$item_stmt->bind_param("i", $admin_id);
$item_stmt->execute();
$items = $item_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Class Items</title>

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

    <nav>
        <a href="course-admin-dashboard.php">Dashboard</a>
        <a href="manage-timetable.php">Timetable</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <section class="page-heading">
        <p class="eyebrow">Course Admin</p>
        <h1>Manage Required Class Items</h1>

        <p>
            Add the school items students need to bring
            for their respective classes.
        </p>
    </section>

    <?php if ($message !== ""): ?>
        <p style="color: green;">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <p style="color: red;">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <section class="card">

        <h2>Add Required Item</h2>

        <form method="POST" action="manage-items.php">

            <label for="timetable_id">Select Class:</label>

            <select name="timetable_id" id="timetable_id" required>

                <option value="">Choose a class</option>

                <?php while ($class = $classes->fetch_assoc()): ?>

                    <option value="<?php echo $class["timetable_id"]; ?>">
                        <?php
                        echo htmlspecialchars(
                            $class["class_unit"] . " - " .
                            $class["day_of_week"] . " - " .
                            $class["start_time"]
                        );
                        ?>
                    </option>

                <?php endwhile; ?>

            </select>

            <br><br>

            <label for="item_name">Item Name:</label>

            <input
                type="text"
                name="item_name"
                id="item_name"
                placeholder="e.g. Mathematics Textbook"
                required
            >

            <br><br>

            <label for="description">Description:</label>

            <textarea
                name="description"
                id="description"
                placeholder="Describe the required item"
            ></textarea>

            <br><br>

            <button type="submit">Add Required Item</button>

        </form>

    </section>

    <section class="card">

        <h2>Items Assigned to Classes</h2>

        <?php if ($items->num_rows > 0): ?>

            <table>

                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Day</th>
                        <th>Required Item</th>
                        <th>Description</th>
                    </tr>
                </thead>

                <tbody>

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

                </tbody>

            </table>

        <?php else: ?>

            <p>No required items have been assigned yet.</p>

        <?php endif; ?>

    </section>

    <p>
        <a href="course-admin-dashboard.php">← Back to Dashboard</a>
    </p>

</main>

<footer class="site-footer">
    <p>Forgotten School Item Alert System · Student project</p>
</footer>

</body>
</html>

<?php
$class_stmt->close();
$item_stmt->close();
$conn->close();
?>
