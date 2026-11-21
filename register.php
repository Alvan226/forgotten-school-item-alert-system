<?php
session_start();
require_once 'includes/db.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $full_name = trim($_POST["fullname"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    if ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {

        $check = $conn->prepare("SELECT student_id FROM STUDENT WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $error = "This email is already registered.";
        } else {

            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare(
                "INSERT INTO STUDENT (full_name, email, password_hash)
                 VALUES (?, ?, ?)"
            );

            $stmt->bind_param("sss", $full_name, $email, $password_hash);

            if ($stmt->execute()) {
                $success = "Account created successfully! You can now log in.";
            } else {
                $error = "Registration failed. Please try again.";
            }

            $stmt->close();
        }

        $check->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Registration | Forgotten School Item Alert System</title>

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
        <a href="course-admin-dashboard.php">Course Admin</a>
        <a href="admin-dashboard.php">Administrator</a>
    </nav>
</header>

<main class="container">

    <section class="page-heading">
        <p class="eyebrow">Registration</p>
        <h1>Create a student account</h1>
        <p>Register to start managing your school items.</p>
    </section>

    <form class="card form-card" method="POST" action="register.php">

        <?php if ($error != ""): ?>
            <p style="color:red;">
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <?php if ($success != ""): ?>
            <p style="color:green;">
                <?php echo htmlspecialchars($success); ?>
            </p>
        <?php endif; ?>

        <label for="fullname">Full name</label>
        <input
            id="fullname"
            name="fullname"
            type="text"
            placeholder="Enter your full name"
            required
        >

        <label for="reg-email">Email address</label>
        <input
            id="reg-email"
            name="email"
            type="email"
            placeholder="name@example.com"
            required
        >

        <label for="reg-password">Password</label>
        <input
            id="reg-password"
            name="password"
            type="password"
            placeholder="Create a password"
            required
        >

        <label for="confirm-password">Confirm password</label>
        <input
            id="confirm-password"
            name="confirm_password"
            type="password"
            placeholder="Repeat your password"
            required
        >

        <button class="button" type="submit">Create account</button>

        <p class="muted">
            Already registered? <a href="login.php">Login</a>.
        </p>

    </form>

</main>

<footer class="site-footer">
    <p>Forgotten School Item Alert System · Student project</p>
</footer>

</body>
</html>
