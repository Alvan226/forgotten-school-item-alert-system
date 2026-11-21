
<?php
session_start();
require_once 'includes/db.php';

$message = "";
$reset_link = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } else {

        // Check Student account.
        $stmt = $conn->prepare(
            "SELECT email FROM STUDENT WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $student = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        // Check Course Admin account.
        $stmt = $conn->prepare(
            "SELECT email FROM COURSE_ADMIN WHERE email = ?"
        );

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $course_admin = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        if ($student || $course_admin) {

            // Generate a secure random token.
            $token = bin2hex(random_bytes(32));

            // Store only the hashed token.
            $token_hash = hash("sha256", $token);

            // Let MySQL calculate the expiry time.
            $stmt = $conn->prepare(
                "INSERT INTO PASSWORD_RESET
                (email, reset_token, expires_at)
                VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))"
            );

            $stmt->bind_param(
                "ss",
                $email,
                $token_hash
            );

            if ($stmt->execute()) {

                $reset_link = "http://localhost/forgotten-school-item-alert/reset-password.php?token=" . urlencode($token);

                $message = "Your password reset request was created successfully.";

            } else {

                $message = "Unable to create the reset request. Please try again.";
            }

            $stmt->close();

        } else {

            $message = "If an account exists with that email, a reset request can be made.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Forgot Password | Forgotten School Item Alert System</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="container">

    <h1>Forgot Password?</h1>

    <p>Enter your registered email address to request a password reset.</p>

    <?php if (!empty($message)): ?>

        <p>
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>

    <?php if (!empty($reset_link)): ?>

        <p>
            <a href="<?php echo htmlspecialchars($reset_link); ?>">
                Continue to Reset Password
            </a>
        </p>

        <p>Your reset link expires after 30 minutes.</p>

    <?php endif; ?>

    <form method="POST" action="forgot-password.php">

        <label for="email">Email Address:</label>

        <input
            type="email"
            id="email"
            name="email"
            required
        >

        <br><br>

        <button type="submit">
            Request Password Reset
        </button>

    </form>

    <p>
        <a href="login.php">Back to Login</a>
    </p>

</div>

</body>
</html>
