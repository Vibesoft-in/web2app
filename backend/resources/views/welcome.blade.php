<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>WebIntoApp — Convert WooCommerce & Shopify into White-Label Native Apps</title>
    <meta name="description" content="Turn your e-commerce store into a high-converting iOS and Android app in under 10 minutes. Live catalog sync, push notifications, and automated app builds.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&display=swap" rel="stylesheet">

    <!-- Styles -->
    <style>
        *, ::after, ::before { box-sizing: border-box; margin: 0; padding: 0; }
        :root {
            --bg: #090d16;
            --card: rgba(17, 24, 39, 0.7);
            --border: rgba(255, 255, 255, 0.08);
            --primary: #6366f1;
            --primary-glow: rgba(99, 102, 241, 0.25);
            --accent: #a855f7;
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            line-height: 1.6;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, .brand-font {
            font-family: 'Outfit', sans-serif;
        }

        /* Ambient glow spots */
        .ambient-glow-1 {
            position: absolute;
            top: 5%;
            left: 50%;
            transform: translateX(-50%);
            width: 700px;
            height: 380px;
            background: radial-gradient(circle, rgba(99,102,241,0.18) 0%, rgba(168,85,247,0.08) 50%, transparent 80%);
            filter: blur(80px);
            pointer-events: none;
            z-index: 0;
        }

        .ambient-glow-2 {
            position: absolute;
            top: 25%;
            left: 10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(168,85,247,0.12) 0%, transparent 70%);
            filter: blur(90px);
            pointer-events: none;
            z-index: 0;
        }

        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            position: relative;
            z-index: 1;
        }

        /* Navigation */
        nav {
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            background: rgba(9, 13, 22, 0.75);
            border-bottom: 1px solid var(--border);
        }

        .nav-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            height: 72px;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text-main);
            font-weight: 800;
            font-size: 20px;
        }

        .logo-badge {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            background: linear-gradient(135deg, #6366f1, #a855f7);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
        }

        .nav-links {
            display: flex;
            gap: 28px;
            list-style: none;
        }

        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: color 0.2s;
        }

        .nav-links a:hover {
            color: var(--text-main);
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: 1px solid transparent;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #ffffff;
            box-shadow: 0 4px 20px rgba(99, 102, 241, 0.35);
        }

        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 25px rgba(99, 102, 241, 0.5);
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
        }

        .btn-outline {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: var(--text-main);
        }

        .btn-outline:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.25);
        }

        /* Hero */
        .hero {
            padding: 90px 0 110px;
            text-align: center;
        }

        .pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 16px;
            border-radius: 9999px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.3);
            color: #818cf8;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 28px;
        }

        .hero h1 {
            font-size: clamp(38px, 6vw, 68px);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: -0.02em;
            max-width: 900px;
            margin: 0 auto 24px;
        }

        .gradient-text {
            background: linear-gradient(135deg, #818cf8 0%, #c084fc 50%, #f472b6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .hero p {
            font-size: 18px;
            color: var(--text-muted);
            max-width: 660px;
            margin: 0 auto 36px;
            line-height: 1.6;
        }

        .hero-ctas {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            flex-wrap: wrap;
            margin-bottom: 40px;
        }

        .hero-features {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
            font-size: 13px;
            color: var(--text-muted);
        }

        .hero-feature-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .check-icon {
            color: #10b981;
            font-weight: bold;
        }

        /* Features Section */
        .section-header {
            text-align: center;
            max-width: 680px;
            margin: 0 auto 60px;
        }

        .section-header h2 {
            font-size: clamp(28px, 4vw, 42px);
            font-weight: 800;
            letter-spacing: -0.01em;
            margin-bottom: 14px;
        }

        .section-header p {
            color: var(--text-muted);
            font-size: 16px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 24px;
            margin-bottom: 100px;
        }

        .feature-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 32px;
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            border-color: rgba(99, 102, 241, 0.4);
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.3);
        }

        .feature-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .feature-card h3 {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .feature-card p {
            font-size: 14px;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* Theme Showcase */
        .themes-section {
            background: rgba(17, 24, 39, 0.4);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 90px 0;
            margin-bottom: 100px;
        }

        .themes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 28px;
        }

        .theme-card {
            background: #111827;
            border: 1px solid var(--border);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .theme-card:hover {
            border-color: #6366f1;
            transform: translateY(-4px);
        }

        .theme-preview-box {
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .theme-a-box { background: linear-gradient(135deg, #1e293b, #0f172a); }
        .theme-b-box { background: linear-gradient(135deg, #4c0519, #881337); }
        .theme-c-box { background: linear-gradient(135deg, #1e1b4b, #312e81); }

        .theme-info {
            padding: 24px;
        }

        .theme-info h4 {
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
        }

        .theme-info p {
            font-size: 13px;
            color: var(--text-muted);
            margin-bottom: 16px;
        }

        /* Quick Portal Access Banner */
        .portal-banner {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.15) 0%, rgba(168, 85, 247, 0.15) 100%);
            border: 1px solid rgba(99, 102, 241, 0.35);
            border-radius: 20px;
            padding: 40px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 24px;
            margin-bottom: 100px;
        }

        .portal-content h3 {
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 8px;
        }

        .portal-content p {
            color: var(--text-muted);
            font-size: 15px;
        }

        /* Footer */
        footer {
            border-top: 1px solid var(--border);
            padding: 40px 0;
            font-size: 13px;
            color: var(--text-muted);
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
        }

        .footer-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background-color: #10b981;
            box-shadow: 0 0 10px #10b981;
        }
    </style>
</head>
<body>
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <!-- Navigation -->
    <nav>
        <div class="container nav-inner">
            <a href="/" class="logo">
                <div class="logo-badge">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                    </svg>
                </div>
                <span>WebIntoApp</span>
            </a>

            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#themes">Theme Engine</a></li>
                <li><a href="#plans">Architecture</a></li>
                <li><a href="/healthz" target="_blank">System Health</a></li>
            </ul>

            <div class="nav-actions">
                <a href="/dashboard/login" class="btn btn-outline">User Login</a>
                <a href="/dashboard/register" class="btn btn-primary">Create App &rarr;</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="pill-badge">
                <span>✨ Production White-Label Native App Platform</span>
            </div>

            <h1>
                Turn Your E-Commerce Store Into a<br>
                <span class="gradient-text">Native Mobile App</span>
            </h1>

            <p>
                Connect WooCommerce or Shopify in 2 minutes. Live catalog synchronization, push notifications, instant checkout, and automated white-label Android APK & iOS build compilation.
            </p>

            <div class="hero-ctas">
                <a href="/dashboard/register" class="btn btn-primary" style="padding: 14px 28px; font-size: 16px;">
                    🚀 Build Your App (Free Trial) &rarr;
                </a>
                <a href="/dashboard/login" class="btn btn-outline" style="padding: 14px 24px; font-size: 16px;">
                    Customer Dashboard
                </a>
                <a href="/admin" class="btn btn-outline" style="padding: 14px 20px; font-size: 14px; opacity: 0.85;">
                    Admin Panel
                </a>
            </div>

            <div class="hero-features">
                <div class="hero-feature-item">
                    <span class="check-icon">✓</span> Zero Coding Required
                </div>
                <div class="hero-feature-item">
                    <span class="check-icon">✓</span> Real-Time Store Sync
                </div>
                <div class="hero-feature-item">
                    <span class="check-icon">✓</span> Multi-Tenant Isolation
                </div>
                <div class="hero-feature-item">
                    <span class="check-icon">✓</span> Google Play Ready (APK/AAB)
                </div>
            </div>
        </div>
    </section>

    <!-- Features Grid -->
    <section id="features" class="container">
        <div class="section-header">
            <h2>Engineered for High-Conversion Mobile Sales</h2>
            <p>Everything your enterprise needs for a lightning-fast, white-labeled mobile shopping experience.</p>
        </div>

        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon-wrapper" style="background: rgba(99, 102, 241, 0.12); color: #818cf8;">
                    🛍️
                </div>
                <h3>Instant WooCommerce Connect</h3>
                <p>Seamlessly integrate via verified REST API keys. Automated DNS validation, SSRF URL security guard, and real-time product verification.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrapper" style="background: rgba(168, 85, 247, 0.12); color: #c084fc;">
                    🎨
                </div>
                <h3>Dynamic Theme Engine</h3>
                <p>Select and customize layouts, fonts, brand colors, and bottom navigation styles with real-time phone simulator preview.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrapper" style="background: rgba(16, 185, 129, 0.12); color: #34d399;">
                    🔔
                </div>
                <h3>Automated Push Messaging</h3>
                <p>Integrated Firebase Cloud Messaging (FCM) pipeline. Send scheduled campaigns and automated abandoned cart recovery alerts with deep-links.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrapper" style="background: rgba(245, 158, 11, 0.12); color: #fbbf24;">
                    📱
                </div>
                <h3>Cloud Build Compilation</h3>
                <p>Automated cloud compilation producing signed production-ready Android APK and AAB binaries with keystore security.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrapper" style="background: rgba(56, 189, 248, 0.12); color: #38bdf8;">
                    🛡️
                </div>
                <h3>Bank-Grade Tenant Isolation</h3>
                <p>Strict multi-tenant DB isolation, encrypted credential vaults, and httpOnly cookie session protections ensuring zero cross-tenant leakage.</p>
            </div>

            <div class="feature-card">
                <div class="feature-icon-wrapper" style="background: rgba(244, 63, 94, 0.12); color: #fb7185;">
                    💬
                </div>
                <h3>WhatsApp Marketing Engine</h3>
                <p>Integrated WhatsApp Business Cloud API with automated order updates, transactional messaging, and tenant wallet ledger system.</p>
            </div>
        </div>
    </section>

    <!-- Themes Showcase -->
    <section id="themes" class="themes-section">
        <div class="container">
            <div class="section-header">
                <h2>Curated Mobile Layout Themes</h2>
                <p>Pre-configured, responsive, high-performance designs tailored for boutique, luxury, and general retail stores.</p>
            </div>

            <div class="themes-grid">
                <div class="theme-card">
                    <div class="theme-preview-box theme-a-box">
                        <span style="font-size: 40px;">📱</span>
                    </div>
                    <div class="theme-info">
                        <h4>Minimal Elegance</h4>
                        <p>Clean modern lines, high typography clarity with Inter, ideal for apparel, jewelry, and modern lifestyle brands.</p>
                        <span style="font-size: 11px; padding: 4px 10px; border-radius: 6px; background: rgba(99,102,241,0.2); color: #818cf8; font-weight: 600;">Theme A Active</span>
                    </div>
                </div>

                <div class="theme-card">
                    <div class="theme-preview-box theme-b-box">
                        <span style="font-size: 40px;">✨</span>
                    </div>
                    <div class="theme-info">
                        <h4>Boutique Glamour</h4>
                        <p>Vibrant colors, curated card radii, and Poppins font optimized for cosmetics, beauty, and trending fashion stores.</p>
                        <span style="font-size: 11px; padding: 4px 10px; border-radius: 6px; background: rgba(244,63,94,0.2); color: #fb7185; font-weight: 600;">Theme B Active</span>
                    </div>
                </div>

                <div class="theme-card">
                    <div class="theme-preview-box theme-c-box">
                        <span style="font-size: 40px;">⚡</span>
                    </div>
                    <div class="theme-info">
                        <h4>Apex Dark Luxury</h4>
                        <p>Sleek dark mode luxury palette with Outfit typography and glowing accents for tech, gaming, and premium gear.</p>
                        <span style="font-size: 11px; padding: 4px 10px; border-radius: 6px; background: rgba(168,85,247,0.2); color: #c084fc; font-weight: 600;">Theme C Active</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Portal Access Callout -->
    <div class="container" id="plans">
        <div class="portal-banner">
            <div class="portal-content">
                <h3>Customer App Studio & Control Center</h3>
                <p>Register your workspace, connect your WooCommerce store, configure your mobile theme, and trigger automated Android APK/AAB builds in real-time.</p>
            </div>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <a href="/dashboard/register" class="btn btn-primary" style="padding: 14px 24px; font-size: 15px;">
                    Start Free Now &rarr;
                </a>
                <a href="/dashboard/login" class="btn btn-outline" style="padding: 14px 20px; font-size: 15px;">
                    User Login
                </a>
                <a href="/admin" class="btn btn-outline" style="padding: 14px 18px; font-size: 14px; opacity: 0.85;">
                    Admin Console
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <div class="container footer-inner">
            <div class="footer-status">
                <span class="status-dot"></span>
                <span>WebIntoApp Platform &bull; All Systems Operational</span>
            </div>
            <div>
                <span>Powered by Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }})</span>
            </div>
            <div>
                <a href="/dashboard/login" style="color:var(--text-muted); margin-right: 16px; text-decoration: none;">User Login</a>
                <a href="/dashboard/register" style="color:var(--text-muted); margin-right: 16px; text-decoration: none;">Create App</a>
                <a href="/admin" style="color:var(--text-muted); margin-right: 16px; text-decoration: none;">Admin Portal</a>
                <a href="/healthz" style="color:var(--text-muted); text-decoration: none;">Health Check</a>
            </div>
        </div>
    </footer>
</body>
</html>
