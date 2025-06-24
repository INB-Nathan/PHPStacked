<?php
include 'includes/functions.php';
/**
 * This is the main entry point of PHPStacked - Election System.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>PHPStacked - Election System</title>
    <meta name="description" content="Online voting system for elections."/>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/main.css"/>
</head>
<body>
    <div class="page-wrapper">
        <header class="header">
            <div class="container">
                <div class="header-content">
                    <h1 class="site-title">PHPStacked</h1>
                    <p class="header-subtitle">Your trusted platform for conducting secure and transparent elections.</p>
                </div>
            </div>
        </header>

        <main class="main-content">
            <section class="hero">
                <div class="hero-background"></div>
                <div class="container">
                    <div class="hero-content">
                        <h2 class="hero-title">Empower Your Community</h2>
                        <p class="hero-description">Manage, vote, and track real-time results for ongoing elections, all in one secure platform.</p>
                        <div class="hero-actions">
                            <a class="cta-button primary" href="login.php">Login</a>
                        </div>
                    </div>
                </div>
            </section>

            <section class="features" id="features">
                <div class="container">
                    <div class="section-header">
                        <h3 class="section-title">Why Choose PHPStacked?</h3>
                        <p class="section-subtitle">Built for modern elections with security and transparency at its core</p>
                    </div>
                    
                    <div class="features-grid">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <span>👥</span>
                            </div>
                            <h4 class="feature-title">Easy to Use</h4>
                            <p class="feature-description">Intuitive interface designed for voters, ensuring smooth election processes.</p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">
                                <span>🔒</span>
                            </div>
                            <h4 class="feature-title">Bank-Level Security</h4>
                            <p class="feature-description">Advanced encryption, secure authentication, and robust data protection keep your elections safe.</p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-icon">
                                <span>📊</span>
                            </div>
                            <h4 class="feature-title">Real-Time Transparency</h4>
                            <p class="feature-description">Live updates on election progress with instant result tracking and comprehensive analytics.</p>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="footer">
            <div class="container">
                <div class="footer-content">
                    <div class="footer-brand">
                        <span class="footer-title">PHPStacked</span>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</body>
</html>