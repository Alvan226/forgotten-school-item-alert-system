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

<link rel="stylesheet" href="css/style.css?v=3">

<style>
    body {
        min-height: 100vh;
        background:
            radial-gradient(circle at 10% 15%, rgba(59, 130, 246, 0.16), transparent 30%),
            radial-gradient(circle at 90% 85%, rgba(139, 92, 246, 0.16), transparent 30%),
            linear-gradient(135deg, #eff6ff, #f5f3ff, #ecfeff);
    }

    .reminder-header {
        margin: 35px 0;
        padding: 35px;
        border-radius: 22px;
        color: white;
        background: linear-gradient(120deg, #1e40af, #4f46e5, #7c3aed);
        box-shadow: 0 12px 30px rgba(49, 46, 129, 0.18);
    }

    .reminder-header h2 {
        color: white;
        font-size: 30px;
        margin: 0 0 12px;
    }

    .reminder-header p {
        color: #e0e7ff;
        margin: 0;
        line-height: 1.7;
    }

    .reminder-label {
        display: inline-block;
        padding: 7px 13px;
        margin-bottom: 14px;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.17);
        font-size: 12px;
        font-weight: bold;
        letter-spacing: 1px;
    }

    .reminder-list {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 22px;
        margin: 25px 0 40px;
    }

    .reminder-card {
        padding: 25px;
        border-radius: 18px;
        background: rgba(255, 255, 255, 0.94);
        border: 1px solid #dbeafe;
        border-left: 6px solid #f97316;
        box-shadow: 0 8px 22px rgba(30, 64, 175, 0.08);
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .reminder-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 14px 28px rgba(30, 64, 175, 0.13);
    }

    .reminder-card h3 {
        color: #1e3a8a;
        font-size: 21px;
        margin: 0 0 18px;
    }

    .reminder-info {
        color: #475569;
        line-height: 1.8;
        margin: 9px 0;
    }

    .reminder-status {
        display: inline-block;
        margin-top: 12px;
        padding: 8px 13px;
        border-radius: 20px;
        background: #ffedd5;
        color: #c2410c;
        font-size: 13px;
        font-weight: bold;
    }

    .empty-state {
        padding: 45px 25px;
        margin: 30px 0;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #bbf7d0;
        box-shadow: 0 8px 22px rgba(22, 163, 74, 0.08);
        text-align: center;
    }

    .empty-icon {
        font-size: 48px;
        margin-bottom: 12px;
    }

    .empty-state h3 {
        color: #15803d;
        font-size: 24px;
    }

    .empty-state p {
        color: #64748b;
        line-height: 1.7;
    }

    @media (max-width: 700px) {
        .reminder-header {
            padding: 25px 20px;
            margin: 25px 0;
        }

        .reminder-header h2 {
            font-size: 24px;
        }

        .reminder-list {
            grid-template-columns: 1fr;
        }
    }
</style>

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
    <a href="checklist.php">Daily Checklist</a>
    <a href="notifications.php">Notifications</a>
    <a href="logout.php">Logout</a>
</nav>

</header>

<main class="container">

<section class="reminder-header">
    <span class="reminder-label">DAILY PREPARATION</span>

    <h2>🔔 Your School Item Reminders</h2>

    <p>
        Stay prepared for your lessons. Review the items below
        and make sure everything you need is packed before class.
    </p>
</section>

<h2>Items You Still Need to Pack</h2>

<p>Here are the outstanding items for your classes today.</p>

<?php if ($result->num_rows > 0): ?>

    <div class="reminder-list">

        <?php while ($row = $result->fetch_assoc()): ?>

            <article class="reminder-card">

                <h3>📚 <?php echo htmlspecialchars($row["item_name"]); ?></h3>

                <p class="reminder-info">
                    <strong>📖 Class:</strong>
                    <?php echo htmlspecialchars($row["class_unit"]); ?>
                </p>

                <p class="reminder-info">
                    <strong>🕒 Class starts:</strong>
                    <?php echo htmlspecialchars($row["start_time"]); ?>
                </p>

                <p class="reminder-info">
                    <strong>📝 Description:</strong>
                    <?php echo htmlspecialchars($row["description"] ?? "No description provided."); ?>
                </p>

                <span class="reminder-status">
                    ⚠ Not yet packed
                </span>

            </article>

        <?php endwhile; ?>

    </div>

<?php else: ?>

    <section class="empty-state">
        <div class="empty-icon">🎉</div>

        <h3>You're all caught up!</h3>

        <p>
            There are no outstanding school items for today's classes.
            You're ready for your school day!
        </p>
    </section>

<?php endif; ?>

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