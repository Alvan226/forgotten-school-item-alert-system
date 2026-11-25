
<?php
session_start();

if (
    !isset($_SESSION["course_admin_id"]) ||
    $_SESSION["role"] !== "course_admin"
) {
    header("Location: login.php");
    exit();
}

$admin_name = htmlspecialchars($_SESSION["full_name"]);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Course Admin Dashboard | Forgotten School Item Alert System</title>

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
        <a href="course-admin-dashboard.php">Dashboard</a>
        <a href="manage-timetable.php">Timetable</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <section class="page-heading">

        <p class="eyebrow">Course Admin</p>

        <h1>Welcome, <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!</h1>

        <p>
            Manage class timetables and assign the school items
            students need for their lessons.
        </p>

    </section>

    <div class="grid">

        <a class="card link-card" href="manage-timetable.php">

            <span>Timetable Management</span>

            <h2>Manage Timetable →</h2>

            <p>
                Add and manage class units, lesson days
                and lesson times.
            </p>

        </a>

        <a class="card link-card" href="manage-items.php">

            <span>School Item Management</span>

            <h2>Manage Class Items →</h2>

            <p>
                Add required school items such as textbooks,
                exercise books, calculators and stationery
                for specific classes.
            </p>

        </a>

    </div>

</main>

<footer class="site-footer">

    <p>Forgotten School Item Alert System · Student project</p>

</footer>

</body>
</html>