<?php
require_once 'session_handler.php';
$isLoggedIn = isLoggedIn();
$currentUser = $isLoggedIn ? getCurrentUser() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Budget & Finance Manager - Nepal</title>
    <link rel="stylesheet" href="landing.css">
</head>
<body>
    <header class="site-header">
        <div class="container nav-wrap">
            <a href="#home" class="brand">BudgetManager</a>
            <button class="menu-toggle" id="menu-toggle" aria-label="Open navigation menu" aria-expanded="false">☰</button>
            <nav class="main-nav" id="main-nav">
                <a href="#about">About</a>
                <a href="#features">Features</a>
                <a href="#contact">Contact</a>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                <?php else: ?>
                    <a href="auth.html" class="btn btn-nav-auth">Login / Sign Up</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main id="home">
        <section class="hero">
            <div class="container hero-grid">
                <div class="hero-content">
                    <p class="eyebrow">Personal Finance, Simplified</p>
                    <h1>Take Control of Your Monthly Budget with Clarity</h1>
                    <p class="hero-text">
                        Personal Budget & Finance Manager helps you plan spending, monitor savings goals, and understand tax impact with an easy workflow built for daily use.
                    </p>
                    <div class="hero-actions">
                        <?php if ($isLoggedIn): ?>
                            <a href="dashboard.php" class="btn btn-primary">Open Dashboard</a>
                        <?php else: ?>
                            <a href="auth.html" class="btn btn-primary">Get Started</a>
                        <?php endif; ?>
                        <a href="#features" class="btn btn-secondary">Explore Features</a>
                    </div>
                    <?php if ($isLoggedIn && $currentUser): ?>
                        <p class="welcome-note">Welcome back, <?php echo htmlspecialchars($currentUser['full_name']); ?>.</p>
                    <?php endif; ?>
                </div>
                <div class="hero-panel">
                    <h2>What this website is about</h2>
                    <ul>
                        <li>Track income, expenses, and budget categories in one place</li>
                        <li>Review reports for better financial decisions every month</li>
                        <li>Plan savings goals and stay accountable to targets</li>
                        <li>Estimate tax and social security impact with built-in calculations</li>
                    </ul>
                </div>
            </div>
        </section>

        <section id="about" class="section">
            <div class="container">
                <h2>Designed for practical budgeting</h2>
                <p>
                    This platform is focused on helping individuals and families build better money habits with clear data, fast inputs, and straightforward financial insights.
                </p>
            </div>
        </section>

        <section id="features" class="section section-muted">
            <div class="container">
                <h2>Core Features</h2>
                <div class="feature-grid">
                    <article class="feature-card">
                        <h3>Income & Expense Tracking</h3>
                        <p>Record transactions quickly and keep your monthly finances up to date.</p>
                    </article>
                    <article class="feature-card">
                        <h3>Budget Planning</h3>
                        <p>Set category limits and instantly compare planned vs actual spending.</p>
                    </article>
                    <article class="feature-card">
                        <h3>Savings Goals</h3>
                        <p>Create target-based goals and monitor your progress over time.</p>
                    </article>
                </div>
            </div>
        </section>

        <section id="contact" class="section">
            <div class="container contact-box">
                <h2>Ready to manage your finances better?</h2>
                <p>Start with your monthly plan today and build long-term financial confidence.</p>
                <?php if ($isLoggedIn): ?>
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                <?php else: ?>
                    <a href="auth.html" class="btn btn-primary">Create Account</a>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-content">
            <p>&copy; <span id="footer-year"></span> Personal Budget & Finance Manager</p>
            <p>Today: <span id="current-date"></span></p>
        </div>
    </footer>

    <script src="landing.js"></script>
</body>
</html>
