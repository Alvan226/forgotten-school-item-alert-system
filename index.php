<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Forgotten School Item Alert System</title>
    <link rel="stylesheet" href="css/style.css?v=3">
    <script src="js/script.js" defer></script>

    <style>
        /* Homepage background */
        body {
            background:
                radial-gradient(circle at 10% 15%, rgba(59, 130, 246, 0.18), transparent 30%),
                radial-gradient(circle at 90% 80%, rgba(139, 92, 246, 0.18), transparent 30%),
                linear-gradient(135deg, #eff6ff, #f5f3ff, #ecfeff);
            min-height: 100vh;
        }

        .home-hero {
            position: relative;
            overflow: hidden;
            margin: 45px auto 35px;
            padding: 65px 45px;
            border-radius: 25px;
            color: white;
            background: linear-gradient(120deg, #1e40af, #4f46e5, #7c3aed);
            box-shadow: 0 18px 40px rgba(49, 46, 129, 0.22);
        }

        .home-hero::before,
        .home-hero::after {
            content: "";
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.09);
        }

        .home-hero::before {
            width: 260px;
            height: 260px;
            top: -100px;
            right: -50px;
        }

        .home-hero::after {
            width: 180px;
            height: 180px;
            bottom: -100px;
            right: 180px;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 720px;
        }

        .hero-label {
            display: inline-block;
            padding: 8px 15px;
            margin-bottom: 18px;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.16);
            font-size: 13px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .home-hero h1 {
            color: white;
            font-size: clamp(32px, 5vw, 52px);
            line-height: 1.15;
            margin: 0 0 20px;
        }

        .home-hero p {
            max-width: 620px;
            color: #e0e7ff;
            font-size: 17px;
            line-height: 1.8;
            margin-bottom: 28px;
        }

        .hero-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
        }

        .hero-button {
            display: inline-block;
            padding: 14px 25px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .hero-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.18);
        }

        .button-white {
            background: white;
            color: #3730a3;
        }

        .button-outline {
            border: 2px solid rgba(255, 255, 255, 0.8);
            color: white;
        }

        .home-section {
            margin: 45px 0;
            text-align: center;
        }

        .home-section h2 {
            color: #1e3a8a;
            font-size: 30px;
            margin-bottom: 10px;
        }

        .home-section > p {
            color: #64748b;
            margin-bottom: 28px;
        }

        .home-features {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
            text-align: left;
        }

        .feature-card {
            padding: 27px;
            border-radius: 18px;
            background: rgba(255, 255, 255, 0.9);
            border: 1px solid #e0e7ff;
            box-shadow: 0 8px 22px rgba(30, 64, 175, 0.07);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 14px 28px rgba(30, 64, 175, 0.13);
        }

        .feature-icon {
            display: inline-flex;
            width: 52px;
            height: 52px;
            align-items: center;
            justify-content: center;
            border-radius: 14px;
            font-size: 25px;
            margin-bottom: 15px;
        }

        .feature-card:nth-child(1) .feature-icon {
            background: #dbeafe;
        }

        .feature-card:nth-child(2) .feature-icon {
            background: #ede9fe;
        }

        .feature-card:nth-child(3) .feature-icon {
            background: #ccfbf1;
        }

        .feature-card h3 {
            color: #1e3a8a;
            margin: 0 0 10px;
        }

        .feature-card p {
            color: #64748b;
            line-height: 1.7;
            margin: 0;
        }

        .home-callout {
            margin: 40px 0;
            padding: 30px;
            border-radius: 18px;
            background: linear-gradient(120deg, #dbeafe, #ede9fe);
            text-align: center;
        }

        .home-callout h2 {
            color: #312e81;
            margin-top: 0;
        }

        .home-callout p {
            color: #475569;
        }

        @media (max-width: 700px) {
            .home-hero {
                margin: 25px auto;
                padding: 40px 23px;
            }

            .home-features {
                grid-template-columns: 1fr;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .hero-button {
                text-align: center;
            }

            .home-section h2 {
                font-size: 25px;
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
            <a href="index.php">Home</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </nav>
    </header>

    <main class="container">

        <section class="home-hero">
            <div class="hero-content">
                <span class="hero-label">SMARTER SCHOOL PREPARATION</span>

                <h1>Never Forget Your School Items Again!</h1>

                <p>
                    Stay organised and prepared for every school day.
                    Manage your timetable, keep track of important school
                    items and receive reminders so you arrive ready for class.
                </p>

                <div class="hero-buttons">
                    <a href="login.php" class="hero-button button-white">
                        Login to Your Account →
                    </a>

                    <a href="register.php" class="hero-button button-outline">
                        Create an Account
                    </a>
                </div>
            </div>
        </section>

        <section class="home-section">
            <h2>Everything You Need to Stay Prepared</h2>
            <p>Simple tools that make organising your school day easier.</p>

            <div class="home-features">

                <article class="feature-card">
                    <span class="feature-icon">📚</span>
                    <h3>Manage School Items</h3>
                    <p>
                        View the books and other materials needed for
                        your classes so you know what to pack.
                    </p>
                </article>

                <article class="feature-card">
                    <span class="feature-icon">📅</span>
                    <h3>Check Your Timetable</h3>
                    <p>
                        Keep track of your lessons and know which
                        subjects you have each school day.
                    </p>
                </article>

                <article class="feature-card">
                    <span class="feature-icon">🔔</span>
                    <h3>Get Reminders</h3>
                    <p>
                        Receive in-system notifications about school
                        items you need to prepare before class.
                    </p>
                </article>

            </div>
        </section>

        <section class="home-callout">
            <h2>Ready for a More Organised School Day?</h2>
            <p>
                Log in to access your timetable, checklist and
                school item reminders.
            </p>
            <a href="login.php" class="hero-button button-white">
                Get Started →
            </a>
        </section>

    </main>

    <footer class="site-footer">
        <p>Forgotten School Item Alert System · Student Project</p>
    </footer>

</body>
</html>