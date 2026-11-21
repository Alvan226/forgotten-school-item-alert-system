<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <!-- Sets the character encoding and makes the page responsive on phones and computers. -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home | Forgotten School Item Alert System</title>
  <!-- All pages share one stylesheet so the design stays consistent. -->
  <link rel="stylesheet" href="css/style.css">
  <script src="js/script.js" defer></script>
</head>
<body>
  <!-- The header contains the system name and the main navigation links. -->
  <header class="site-header">
    <div class="brand"><span class="brand-icon">FS</span><div><strong>Forgotten School Item Alert System</strong><small>Plan your day. Pack with confidence.</small></div></div>
    <nav aria-label="Main navigation">
      <a href="index.php">Home</a><a href="student-dashboard.php">Student</a><a href="course-admin-dashboard.php">Course Admin</a><a href="admin-dashboard.php">Administrator</a>
    </nav>
  </header>
  <main class="container">
    <section class="page-heading">
      <p class="eyebrow">Home</p>
      <h1>Welcome to the Forgotten School Item Alert System</h1>
      <p>A simple place for students to manage school items, check their timetable and prepare for each school day.</p>
    </section>
<div class="grid"><a class="card link-card" href="login.php"><span>Open section</span><h2>Login →</h2><p>Open this section of the system.</p></a><a class="card link-card" href="register.php"><span>Open section</span><h2>Create an account →</h2><p>Open this section of the system.</p></a></div>
  </main>
  <!-- The footer appears at the bottom of every page. -->
  <footer class="site-footer"><p>Forgotten School Item Alert System · Student project</p></footer>
</body>
</html>
