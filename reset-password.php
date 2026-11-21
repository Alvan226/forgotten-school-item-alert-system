
<?php
session_start();
require_once 'includes/db.php';

$message = "";
$valid_token = false;
$reset = null;

$token = $_GET["token"] ?? $_POST["token"] ?? "";

if (!empty($token)) {

    $token_hash = hash("sha256", $token);

    $stmt = $conn->prepare(
        "SELECT reset_id, email
         FROM PASSWORD_RESET
         WHERE reset_token = ?
         AND expires_at > NOW()
         AND used = FALSE
         LIMIT 1"
    );

    $stmt->bind_param("s", $token_hash);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $reset = $result->fetch_assoc();
        $valid_token = true;
    }

    $stmt->close();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!$valid_token) {

        $message = "This reset link is invalid or expired.";

    } else {

        $password = $_POST["password"] ?? "";
        $confirm_password = $_POST["confirm_password"] ?? "";

        if (strlen($password) < 8) {

            $message = "Password must contain at least 8 characters.";

        } elseif ($password !== $confirm_password) {

            $message = "Passwords do not match.";

        } else {

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $email = $reset["email"];

            $stmt = $conn->prepare(
                "UPDATE STUDENT SET password_hash = ? WHERE email = ?"
            );
            $stmt->bind_param("ss", $password_hash, $email);
            $stmt->execute();
            $student_updated = $stmt->affected_rows;
            $stmt->close();

            $stmt = $conn->prepare(
                "UPDATE COURSE_ADMIN SET password_hash = ? WHERE email = ?"
            );
            $stmt->bind_param("ss", $password_hash, $email);
            $stmt->execute();
            $admin_updated = $stmt->affected_rows;
            $stmt->close();

            if ($student_updated > 0 || $admin_updated > 0) {

                $stmt = $conn->prepare(
                    "UPDATE PASSWORD_RESET
                     SET used = TRUE
                     WHERE reset_id = ?"
                );

                $stmt->bind_param("i", $reset["reset_id"]);
                $stmt->execute();
                $stmt->close();

                $message = "Password changed successfully! You can now log in.";
                $valid_token = false;

            } else {

                $message = "Unable to update password. Please request another reset link.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="container">

    <h1>Reset Password</h1>

    <?php if ($message !== "") { ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php } ?>

    <?php if ($valid_token) { ?>

        <p>Enter your new password below.</p>

        <form method="POST" action="reset-password.php">

            <input
                type="hidden"
                name="token"
                value="<?php echo htmlspecialchars($token); ?>"
            >

            <label for="password">New Password:</label>

            <input
                type="password"
                id="password"
                name="password"
                minlength="8"
                required
            >

            <br><br>

            <label for="confirm_password">Confirm New Password:</label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                minlength="8"
                required
            >

            <br><br>

            <label>
                <input type="checkbox" onclick="togglePasswords()">
                Show Passwords
            </label>

            <br><br>

            <button type="submit">Change Password</button>

        </form>

    <?php } elseif (!$valid_token && empty($message)) { ?>

        <p>This reset link is invalid, expired or already used.</p>

        <p>
            <a href="forgot-password.php">Request another reset link</a>
        </p>

    <?php } ?>

    <p><a href="login.php">Back to Login</a></p>

</div>

<script>
function togglePasswords() {

    const password = document.getElementById("password");
    const confirmPassword = document.getElementById("confirm_password");

    const newType = password.type === "password" ? "text" : "password";

    password.type = newType;
    confirmPassword.type = newType;
}
</script>

</body>
</html>
