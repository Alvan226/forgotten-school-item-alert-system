
<?php

// Enable stronger session security before starting the session.
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

session_start();

require_once 'includes/db.php';

$error = "";

// Generate a CSRF token for the login form.
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Verify the CSRF token.
    $submitted_token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($submitted_token) ||
        !hash_equals($_SESSION['csrf_token'], $submitted_token)
    ) {
        $error = "Invalid form submission. Please try again.";

    } else {

        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";
        $role = $_POST["role"] ?? "";

        // Validate submitted information.
        if (
            !is_string($email) ||
            !filter_var($email, FILTER_VALIDATE_EMAIL) ||
            !is_string($password) ||
            !in_array($role, ['student', 'admin', 'course_admin'], true)
        ) {
            $error = "Incorrect email, password or account type.";

        } else {

            $user = null;

            // STUDENT AND ADMINISTRATOR LOGIN
            if ($role === "student" || $role === "admin") {

                $stmt = $conn->prepare(
                    "SELECT student_id, full_name, email, password_hash, role
                     FROM STUDENT
                     WHERE email = ? AND role = ?"
                );

                if ($stmt) {

                    $stmt->bind_param("ss", $email, $role);
                    $stmt->execute();

                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                    }

                    $stmt->close();
                }

                // COURSE ADMIN LOGIN
            } elseif ($role === "course_admin") {

                $stmt = $conn->prepare(
                    "SELECT course_admin_id, full_name, email, password_hash
                     FROM COURSE_ADMIN
                     WHERE email = ?"
                );

                if ($stmt) {

                    $stmt->bind_param("s", $email);
                    $stmt->execute();

                    $result = $stmt->get_result();

                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                    }

                    $stmt->close();
                }
            }

            // Verify the password.
            if (
                $user &&
                password_verify($password, $user["password_hash"])
            ) {

                // Regenerate session ID after successful login.
                session_regenerate_id(true);

                // Remove any previous login information.
                unset(
                    $_SESSION['student_id'],
                    $_SESSION['course_admin_id'],
                    $_SESSION['full_name'],
                    $_SESSION['email'],
                    $_SESSION['role']
                );

                // Store the new login information.
                if ($role === "student" || $role === "admin") {

                    $_SESSION["student_id"] = $user["student_id"];
                    $_SESSION["full_name"] = $user["full_name"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = $user["role"];

                    // Redirect according to account type.
                    if ($role === "admin") {
                        header("Location: admin-dashboard.php");
                    } else {
                        header("Location: student-dashboard.php");
                    }

                    exit();

                } elseif ($role === "course_admin") {

                    $_SESSION["course_admin_id"] = $user["course_admin_id"];
                    $_SESSION["full_name"] = $user["full_name"];
                    $_SESSION["email"] = $user["email"];
                    $_SESSION["role"] = "course_admin";

                    header("Location: course-admin-dashboard.php");
                    exit();
                }
            }

            $error = "Incorrect email, password or account type.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Forgotten School Item Alert System</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

    <div class="container">

        <h1>Login</h1>

        <p>Welcome to the Forgotten School Item Alert System.</p>

        <?php if (!empty($error)): ?>
            <p style="color: red;">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <!-- CSRF protection token -->
            <input
                type="hidden"
                name="csrf_token"
                value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>"
            >

            <label for="role">Account Type:</label>

            <select name="role" id="role" required>
                <option value="student">Student</option>
                <option value="course_admin">Course Admin</option>
                <option value="admin">Administrator</option>
            </select>

            <br><br>

            <label for="email">Email Address:</label>

            <input
                type="email"
                id="email"
                name="email"
                required
            >

            <br><br>

            <label for="password">Password:</label>

            <input
                type="password"
                id="password"
                name="password"
                required
            >

            <br>

            <label>
                <input
                    type="checkbox"
                    id="showPassword"
                    onclick="togglePassword()"
                >
                Show Password
            </label>

            <br><br>

            <button type="submit">Login</button>

        </form>

        <p>
            <a href="forgot-password.php">Forgot Password?</a>
        </p>

        <p>
            Don't have an account?
            <a href="register.php">Register here</a>
        </p>

        <p>
            <a href="index.php">Back to Home</a>
        </p>

    </div>

    <script>
        function togglePassword() {

            const passwordField = document.getElementById("password");

            if (passwordField.type === "password") {
                passwordField.type = "text";
            } else {
                passwordField.type = "password";
            }
        }
    </script>

</body>
</html>