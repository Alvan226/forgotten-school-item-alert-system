
<?php
session_start();

require_once 'includes/db.php';

// Only logged-in students can access notifications.
if (
    !isset($_SESSION["student_id"]) ||
    $_SESSION["role"] !== "student"
) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION["student_id"];

$sql = "SELECT
            n.notification_id,
            n.message,
            n.sent_at,
            n.delivery_status,
            s.item_name
        FROM NOTIFICATION n
        LEFT JOIN CLASS_ITEM_LINK cil
            ON n.link_id = cil.link_id
        LEFT JOIN SCHOOL_ITEM s
            ON cil.item_id = s.item_id
        WHERE n.student_id = ?
        ORDER BY n.sent_at DESC, n.notification_id DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Unable to load notifications.");
}

$stmt->bind_param("i", $student_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Notifications | Forgotten School Item Alert System</title>

    <link rel="stylesheet" href="css/style.css?v=4">

    <style>
        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at 10% 15%, rgba(59, 130, 246, 0.16), transparent 30%),
                radial-gradient(circle at 90% 85%, rgba(139, 92, 246, 0.16), transparent 30%),
                linear-gradient(135deg, #eff6ff, #f5f3ff, #ecfeff);
        }

        .notification-header {
            margin: 35px 0;
            padding: 35px;
            border-radius: 22px;
            color: white;
            background: linear-gradient(120deg, #1e40af, #4f46e5, #7c3aed);
            box-shadow: 0 12px 30px rgba(49, 46, 129, 0.18);
        }

        .notification-header h2 {
            color: white;
            font-size: 30px;
            margin: 0 0 12px;
        }

        .notification-header p {
            color: #e0e7ff;
            line-height: 1.7;
            margin: 0;
        }

        .notification-label {
            display: inline-block;
            padding: 7px 13px;
            margin-bottom: 14px;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.17);
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .notification-list {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 22px;
            margin: 25px 0 40px;
        }

        .notification-card {
            padding: 25px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #ddd6fe;
            border-left: 6px solid #8b5cf6;
            box-shadow: 0 8px 22px rgba(30, 64, 175, 0.08);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .notification-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 28px rgba(30, 64, 175, 0.13);
        }

        .notification-card h3 {
            color: #312e81;
            font-size: 21px;
            margin: 0 0 15px;
        }

        .notification-message {
            color: #475569;
            line-height: 1.8;
        }

        .notification-date {
            color: #64748b;
            font-size: 14px;
            margin-top: 15px;
        }

        .notification-status {
            display: inline-block;
            margin-top: 12px;
            padding: 8px 13px;
            border-radius: 20px;
            background: #dcfce7;
            color: #15803d;
            font-size: 13px;
            font-weight: bold;
        }

        .empty-notifications {
            padding: 45px 25px;
            margin: 30px 0;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #ddd6fe;
            box-shadow: 0 8px 22px rgba(30, 64, 175, 0.08);
            text-align: center;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 12px;
        }

        .empty-notifications h3 {
            color: #3730a3;
            font-size: 24px;
        }

        .empty-notifications p {
            color: #64748b;
            line-height: 1.7;
        }

        @media (max-width: 700px) {
            .notification-header {
                padding: 25px 20px;
                margin: 25px 0;
            }

            .notification-header h2 {
                font-size: 24px;
            }

            .notification-list {
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
        <a href="reminders.php">Reminders</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <section class="notification-header">

        <span class="notification-label">STAY UPDATED</span>

        <h2>🔔 My Notifications</h2>

        <p>
            Keep track of your school item reminders and updates.
            Check your notifications regularly so you are always prepared.
        </p>

    </section>

    <h2>Your Recent Notifications</h2>

    <p>Here are the reminders and updates sent to your account.</p>

    <?php if ($result->num_rows > 0): ?>

        <div class="notification-list">

            <?php while ($row = $result->fetch_assoc()): ?>

                <article class="notification-card">

                    <h3>
                        🔔
                        <?php echo htmlspecialchars(
                            $row["item_name"] ?? "School Item Reminder",
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </h3>

                    <p class="notification-message">
                        <?php echo htmlspecialchars(
                            $row["message"] ?? "",
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </p>

                    <p class="notification-date">
                        <strong>📅 Date:</strong>

                        <?php echo htmlspecialchars(
                            $row["sent_at"] ?? "Not available",
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </p>

                    <span class="notification-status">
                        Status:
                        <?php echo htmlspecialchars(
                            $row["delivery_status"] ?? "Pending",
                            ENT_QUOTES,
                            'UTF-8'
                        ); ?>
                    </span>

                </article>

            <?php endwhile; ?>

        </div>

    <?php else: ?>

        <section class="empty-notifications">

            <div class="empty-icon">📭</div>

            <h3>No notifications yet</h3>

            <p>
                You will see your school item reminders and updates here
                whenever new notifications are created.
            </p>

        </section>

    <?php endif; ?>

</main>

<footer class="site-footer">
    <p>Forgotten School Item Alert System · Student Project</p>
</footer>

</body>
</html>
