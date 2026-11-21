
<?php
session_start();
require_once 'includes/db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $role = $_POST["role"] ?? "";

    // STUDENT AND ADMINISTRATOR LOGIN
    if ($role === "student" || $role === "admin") {

        $stmt = $conn->prepare(
            "SELECT student_id, full_name, email, password_hash, role
             FROM STUDENT
             WHERE email = ? AND role = ?"
        );

        $stmt->bind_param("ss", $email, $role);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password_hash"])) {

                session_regenerate_id(true);

                $_SESSION["student_id"] = $user["student_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = $user["role"];

                if ($role === "admin") {
                    header("Location: admin-dashboard.php");
                } else {
                    header("Location: student-dashboard.php");
                }

                exit();
            }
        }

        $stmt->close();

    } elseif ($role === "course_admin") {

        $stmt = $conn->prepare(
            "SELECT course_admin_id, full_name, email, password_hash
             FROM COURSE_ADMIN
             WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password_hash"])) {

                session_regenerate_id(true);

                $_SESSION["course_admin_id"] = $user["course_admin_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["email"] = $user["email"];
                $_SESSION["role"] = "course_admin";

                header("Location: course-admin-dashboard.php");
                exit();
            }
        }

        $stmt->close();
    }

    $error = "Incorrect email, password or account type.";
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
                <?php echo htmlspecialchars($error); ?>
            </p>
        <?php endif; ?>

        <form method="POST" action="login.php">

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
