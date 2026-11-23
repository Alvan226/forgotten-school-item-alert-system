<?php
session_start();

// Check whether a student is logged in.
if (!isset($_SESSION["student_id"]) || $_SESSION["role"] !== "student") {
    header("Location: login.php");
    exit();
}

$student_name = $_SESSION["full_name"];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard | Forgotten School Item Alert System</title>

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
        <a href="index.php">Home</a>
        <a href="student-dashboard.php">Student</a>
        <a href="select-classes.php">Select Classes</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <section class="page-heading">

        <p class="eyebrow">Student dashboard</p>

        <h1>
            Welcome, <?php echo htmlspecialchars($student_name); ?>!
        </h1>

        <p>
            Use the sections below to stay organised and prepare for your classes.
        </p>

    </section>

    <div class="grid">

        <a class="card link-card" href="select-classes.php">
            <span>New feature</span>
            <h2>Select My Classes →</h2>
            <p>Choose the classes you attend and personalise your timetable.</p>
        </a>

        <a class="card link-card" href="timetable.php">
            <span>Open section</span>
            <h2>View timetable →</h2>
            <p>Check your scheduled classes.</p>
        </a>

        <a class="card link-card" href="items.php">
            <span>Open section</span>
            <h2>Manage school items →</h2>
            <p>View and manage your school items.</p>
        </a>

        <a class="card link-card" href="checklist.php">
            <span>Open section</span>
            <h2>Daily packing checklist →</h2>
            <p>Check the items you need to pack.</p>
        </a>

        <a class="card link-card" href="reminders.php">
            <span>Open section</span>
            <h2>View reminders →</h2>
            <p>Stay updated with your reminders.</p>
        </a>

    </div>

</main>

<footer class="site-footer">
    <p>Forgotten School Item Alert System · Student project</p>
</footer>

</body>
</html>
