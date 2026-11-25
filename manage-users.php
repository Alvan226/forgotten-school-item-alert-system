
<?php
session_start();
require_once 'includes/db.php';

// Only Administrators can access this page.
if (!isset($_SESSION["student_id"]) || ($_SESSION["role"] ?? "") !== "admin") {
    header("Location: login.php");
    exit();
}

// Generate CSRF protection token.
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$error = "";

// Handle form submissions.
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $token = $_POST["csrf_token"] ?? "";

    if (!hash_equals($_SESSION["csrf_token"], $token)) {
        $error = "Invalid request. Please refresh the page.";

    } else {

        $action = $_POST["action"] ?? "";

        // CREATE COURSE ADMIN ACCOUNT.
        if ($action === "create_course_admin") {

            $full_name = trim($_POST["full_name"] ?? "");
            $email = trim($_POST["email"] ?? "");
            $password = $_POST["password"] ?? "";
            $confirm_password = $_POST["confirm_password"] ?? "";

            if ($full_name === "" || $email === "" ||
                $password === "" || $confirm_password === "") {

                $error = "Please fill in all the fields.";

            } elseif (strlen($full_name) > 100) {

                $error = "The full name is too long.";

            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
                      strlen($email) > 100) {

                $error = "Please enter a valid email address.";

            } elseif (strlen($password) < 8) {

                $error = "The password must contain at least 8 characters.";

            } elseif ($password !== $confirm_password) {

                $error = "The passwords do not match.";

            } else {

                // Check whether the email already exists.
                $stmt = $conn->prepare(
                    "SELECT email FROM COURSE_ADMIN WHERE email = ?
                     UNION
                     SELECT email FROM STUDENT WHERE email = ?"
                );

                $stmt->bind_param("ss", $email, $email);
                $stmt->execute();

                $existing_account = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($existing_account) {

                    $error = "An account with this email address already exists.";

                } else {

                    // Securely hash the password.
                    $password_hash = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    $stmt = $conn->prepare(
                        "INSERT INTO COURSE_ADMIN
                         (full_name, email, password_hash)
                         VALUES (?, ?, ?)"
                    );

                    $stmt->bind_param(
                        "sss",
                        $full_name,
                        $email,
                        $password_hash
                    );

                    if ($stmt->execute()) {

                        $message = "Course Admin account created successfully.";

                    } else {

                        $error = "Could not create the Course Admin account.";

                    }

                    $stmt->close();
                }
            }

        // HANDLE ACCOUNT DELETION.
        } elseif ($action === "delete_user") {

            $user_type = $_POST["user_type"] ?? "";
            $user_id = (int)($_POST["user_id"] ?? 0);

            if ($user_id <= 0) {

                $error = "Invalid account selected.";

            } elseif ($user_type === "student") {

                // Find the student account.
                $stmt = $conn->prepare(
                    "SELECT email FROM STUDENT
                     WHERE student_id = ? AND role = 'student'"
                );

                $stmt->bind_param("i", $user_id);
                $stmt->execute();

                $student = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$student) {

                    $error = "Student account not found.";

                } else {

                    try {

                        $conn->begin_transaction();

                        // Remove checklist records.
                        $stmt = $conn->prepare(
                            "DELETE FROM CHECKLIST_STATUS WHERE student_id = ?"
                        );
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();

                        // Remove timetable assignments.
                        $stmt = $conn->prepare(
                            "DELETE FROM STUDENT_TIMETABLE WHERE student_id = ?"
                        );
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();

                        // Remove session records.
                        $stmt = $conn->prepare(
                            "DELETE FROM SESSION WHERE student_id = ?"
                        );
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();

                        // Remove notifications.
                        $stmt = $conn->prepare(
                            "DELETE FROM NOTIFICATION WHERE student_id = ?"
                        );
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();

                        // Remove password reset records.
                        $stmt = $conn->prepare(
                            "DELETE FROM PASSWORD_RESET WHERE email = ?"
                        );
                        $stmt->bind_param("s", $student["email"]);
                        $stmt->execute();
                        $stmt->close();

                        // Finally delete the student.
                        $stmt = $conn->prepare(
                            "DELETE FROM STUDENT
                             WHERE student_id = ? AND role = 'student'"
                        );
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();

                        $conn->commit();

                        $message = "Student account deleted successfully.";

                    } catch (Throwable $e) {

                        $conn->rollback();
                        $error = "Could not delete the student account.";

                    }
                }

            } elseif ($user_type === "course_admin") {

                // Find the Course Admin account.
                $stmt = $conn->prepare(
                    "SELECT email FROM COURSE_ADMIN
                     WHERE course_admin_id = ?"
                );

                $stmt->bind_param("i", $user_id);
                $stmt->execute();

                $admin = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$admin) {

                    $error = "Course Admin account not found.";

                } else {

                    try {

                        $conn->begin_transaction();

                        // Remove checklist records linked to this admin's classes.
                        $stmt = $conn->prepare(
                            "DELETE cs FROM CHECKLIST_STATUS cs
                             JOIN CLASS_ITEM_LINK cil
                                ON cs.link_id = cil.link_id
                             JOIN TIMETABLE_ENTRY t
                                ON cil.timetable_id = t.timetable_id
                             WHERE t.course_admin_id = ?"
                        );
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();

                        // Remove student timetable assignments.
                        $stmt = $conn->prepare(
                            "DELETE st FROM STUDENT_TIMETABLE st
                             JOIN TIMETABLE_ENTRY t
                                ON st.timetable_id = t.timetable_id
                             WHERE t.course_admin_id = ?"
                        );
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();

                        // Remove class-item links.
                        $stmt = $conn->prepare(
                            "DELETE cil FROM CLASS_ITEM_LINK cil
                             JOIN TIMETABLE_ENTRY t
                                ON cil.timetable_id = t.timetable_id
                             WHERE t.course_admin_id = ?"
                        );
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();

                        // Remove timetable entries.
                        $stmt = $conn->prepare(
                            "DELETE FROM TIMETABLE_ENTRY
                             WHERE course_admin_id = ?"
                        );
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();

                        // Remove password reset records.
                        $stmt = $conn->prepare(
                            "DELETE FROM PASSWORD_RESET WHERE email = ?"
                        );
                        $stmt->bind_param("s", $admin["email"]);
                        $stmt->execute();
                        $stmt->close();

                        // Finally delete the Course Admin.
                        $stmt = $conn->prepare(
                            "DELETE FROM COURSE_ADMIN
                             WHERE course_admin_id = ?"
                        );
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $stmt->close();

                        $conn->commit();

                        $message = "Course Admin account and associated timetable records deleted successfully.";

                    } catch (Throwable $e) {

                        $conn->rollback();
                        $error = "Could not delete the Course Admin account.";

                    }
                }

            } else {

                $error = "Invalid account type.";

            }

        } else {

            $error = "Invalid request.";

        }
    }
}

// Get registered students.
$students = $conn->query(
    "SELECT student_id, full_name, email
     FROM STUDENT
     WHERE role = 'student'
     ORDER BY full_name"
);

// Get registered Course Admins.
$course_admins = $conn->query(
    "SELECT course_admin_id, full_name, email
     FROM COURSE_ADMIN
     ORDER BY full_name"
);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Users | Forgotten School Item Alert System</title>

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
        <a href="admin-dashboard.php">Dashboard</a>
        <a href="reports.php">Reports</a>
        <a href="manage-users.php">Manage Users</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="container">

    <section class="page-heading">

        <p class="eyebrow">Administrator</p>

        <h1>User Management</h1>

        <p>View, create and manage Students and Course Administrators.</p>

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


    <!-- ADD COURSE ADMIN FORM -->
    <section class="card">

        <h2>Add Course Administrator</h2>

        <p>Create a Course Admin account using the form below.</p>

        <form method="POST" action="manage-users.php">

            <input type="hidden" name="csrf_token"
                   value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

            <input type="hidden" name="action"
                   value="create_course_admin">

            <div class="form-group">
                <label for="full_name">Full Name</label>

                <input type="text"
                       id="full_name"
                       name="full_name"
                       maxlength="100"
                       required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>

                <input type="email"
                       id="email"
                       name="email"
                       maxlength="100"
                       required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>

                <input type="password"
                       id="password"
                       name="password"
                       minlength="8"
                       required>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>

                <input type="password"
                       id="confirm_password"
                       name="confirm_password"
                       minlength="8"
                       required>
            </div>

            <button type="submit" class="button">
                Create Course Admin
            </button>

        </form>

    </section>


    <!-- REGISTERED STUDENTS -->
    <section class="card">

        <h2>Registered Students</h2>

        <?php if ($students && $students->num_rows > 0): ?>

            <div style="overflow-x:auto;">

                <table>

                    <thead>
                        <tr>
                            <th>Student ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($student = $students->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php echo (int)$student["student_id"]; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student["full_name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($student["email"]); ?>
                                </td>

                                <td>

                                    <form method="POST"
                                          action="manage-users.php"
                                          onsubmit="return confirm('Are you sure you want to delete this student account and its related records?');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="delete_user">

                                        <input type="hidden"
                                               name="user_type"
                                               value="student">

                                        <input type="hidden"
                                               name="user_id"
                                               value="<?php echo (int)$student["student_id"]; ?>">

                                        <button type="submit" class="button">
                                            Delete Student
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p>No students have registered yet.</p>

        <?php endif; ?>

    </section>


    <!-- COURSE ADMINISTRATORS -->
    <section class="card">

        <h2>Course Administrators</h2>

        <?php if ($course_admins && $course_admins->num_rows > 0): ?>

            <div style="overflow-x:auto;">

                <table>

                    <thead>
                        <tr>
                            <th>Admin ID</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php while ($admin = $course_admins->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php echo (int)$admin["course_admin_id"]; ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($admin["full_name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($admin["email"]); ?>
                                </td>

                                <td>

                                    <form method="POST"
                                          action="manage-users.php"
                                          onsubmit="return confirm('Deleting this Course Admin will also remove their timetable entries and associated class-item and checklist records. Continue?');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="delete_user">

                                        <input type="hidden"
                                               name="user_type"
                                               value="course_admin">

                                        <input type="hidden"
                                               name="user_id"
                                               value="<?php echo (int)$admin["course_admin_id"]; ?>">

                                        <button type="submit" class="button">
                                            Delete Course Admin
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p>No Course Administrators have been registered yet.</p>

        <?php endif; ?>

    </section>

</main>

<footer class="site-footer">
    <p>Forgotten School Item Alert System · Student Project</p>
</footer>

</body>
</html>

<?php
$conn->close();
?>

