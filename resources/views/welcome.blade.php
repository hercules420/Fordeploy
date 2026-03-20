<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Poultry System</title>
    <style>
        :root {
            --bg: #0b1220;
            --surface: #111a2b;
            --surface-alt: #0f1727;
            --text: #e2e8f0;
            --muted: #94a3b8;
            --line: #24324a;
            --accent: #f97316;
            --accent-2: #14b8a6;
            --accent-dark: #c2410c;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background:
                radial-gradient(circle at 10% 12%, rgba(249, 115, 22, 0.22), transparent 34%),
                radial-gradient(circle at 90% 18%, rgba(20, 184, 166, 0.18), transparent 32%),
                linear-gradient(180deg, #0a1220 0%, #0b1220 55%, #0e1524 100%);
            color: var(--text);
            font-family: "Segoe UI", "Verdana", sans-serif;
            line-height: 1.45;
        }

        .container {
            max-width: 1140px;
            margin: 0 auto;
            padding: 20px 20px 42px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            margin-bottom: 16px;
        }

        .consumer-promo {
            margin-bottom: 14px;
            padding: 12px 14px;
            border-radius: 14px;
            border: 1px solid #2d5b7a;
            background: linear-gradient(135deg, rgba(12, 74, 110, 0.45), rgba(21, 94, 117, 0.35));
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .consumer-promo strong {
            color: #ecfeff;
            letter-spacing: 0.02em;
        }

        .consumer-promo .sub {
            color: #bae6fd;
            font-size: 0.85rem;
            margin-top: 2px;
        }

        .consumer-promo .promo-btn {
            text-decoration: none;
            background: #14b8a6;
            color: #042f2e;
            font-weight: 800;
            border-radius: 10px;
            padding: 10px 14px;
            white-space: nowrap;
        }

        .consumer-promo .promo-btn:hover {
            filter: brightness(1.06);
        }

        .brand {
            font-weight: 800;
            letter-spacing: 0.12em;
            font-size: 0.95rem;
            color: #f8fafc;
        }

        .chip {
            font-size: 0.75rem;
            color: #cbd5e1;
            border: 1px solid #3b4b65;
            border-radius: 999px;
            padding: 6px 10px;
            background: rgba(17, 26, 43, 0.8);
        }

        .hero {
            display: grid;
            grid-template-columns: 1.25fr 0.95fr;
            gap: 16px;
            margin-bottom: 16px;
        }

        .hero-main {
            background: linear-gradient(165deg, rgba(18, 30, 51, 0.9), rgba(11, 20, 35, 0.95));
            border: 1px solid var(--line);
            border-radius: 20px;
            padding: 34px 30px;
            box-shadow: 0 24px 60px rgba(0, 0, 0, 0.35);
            animation: rise 600ms ease-out;
        }

        .eyebrow {
            color: #fb923c;
            font-size: 0.8rem;
            letter-spacing: 0.16em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        .hero-main h1 {
            margin: 0;
            font-size: 2.7rem;
            line-height: 1.06;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .hero-main p {
            margin: 14px 0 22px;
            color: var(--muted);
            max-width: 52ch;
        }

        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn {
            display: inline-block;
            text-decoration: none;
            font-weight: 700;
            border-radius: 12px;
            padding: 12px 16px;
            transition: transform 0.15s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--accent);
            color: #ffffff;
            box-shadow: 0 10px 25px rgba(249, 115, 22, 0.25);
        }

        .btn-primary:hover {
            background: #fb7f2a;
        }

        .btn-secondary {
            background: #1e293b;
            border: 1px solid #334155;
            color: #dbeafe;
        }

        .hero-side {
            display: grid;
            gap: 12px;
            animation: rise 760ms ease-out;
        }

        .stat {
            background: linear-gradient(160deg, rgba(17, 26, 43, 0.95), rgba(11, 18, 32, 0.95));
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 16px;
        }

        .stat .label {
            font-size: 0.76rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: #93c5fd;
        }

        .stat .value {
            margin-top: 8px;
            font-size: 1.9rem;
            font-weight: 800;
            color: #f8fafc;
        }

        .panel-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 12px;
        }

        .panel {
            background: var(--surface);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 16px;
        }

        .panel h3 {
            margin: 0 0 6px;
            font-size: 1rem;
            color: #f8fafc;
        }

        .panel p {
            margin: 0;
            color: var(--muted);
            font-size: 0.92rem;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-top: 12px;
        }

        .card {
            background: var(--surface-alt);
            border: 1px solid var(--line);
            border-radius: 16px;
            padding: 16px;
        }

        .card h2 {
            margin: 0;
            font-size: 1.06rem;
            color: #f8fafc;
        }

        .card p {
            margin: 10px 0 14px;
            color: var(--muted);
            font-size: 0.9rem;
        }

        .register-btn {
            display: inline-block;
            width: 100%;
            text-align: center;
            text-decoration: none;
            font-weight: 800;
            border-radius: 10px;
            padding: 11px 12px;
            transition: filter 0.15s ease;
        }

        .register-btn:hover {
            filter: brightness(1.08);
        }

        .register-owner {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: #fff;
        }

        .register-consumer {
            background: linear-gradient(135deg, var(--accent-2), #0d9488);
            color: #06201c;
        }

        .roles {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
        }

        .role {
            border: 1px dashed #41526e;
            border-radius: 999px;
            padding: 6px 10px;
            color: #cbd5e1;
            font-size: 0.78rem;
        }

        .note {
            margin: 0;
            color: #9fb0c9;
            font-size: 0.85rem;
            text-align: center;
            padding: 16px 0 0;
        }

        @keyframes rise {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 1000px) {
            .hero {
                grid-template-columns: 1fr;
            }

            .panel-grid {
                grid-template-columns: 1fr;
            }

            .grid {
                grid-template-columns: 1fr;
            }

            .hero-main h1 {
                font-size: 2.2rem;
            }
        }

        @media (max-width: 640px) {
            .container {
                padding: 14px 14px 30px;
            }

            .hero-main {
                padding: 24px 18px;
            }

            .hero-main h1 {
                font-size: 1.8rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <section class="consumer-promo">
            <div>
                <strong>Want to Easily Shop? Download the App!</strong>
                <div class="sub">You can still continue using web login if you prefer.</div>
            </div>
            <a href="{{ route('consumer.app.launch') }}" class="promo-btn">Open App Options</a>
        </section>

        @if (session('success'))
            <section class="consumer-promo" style="border-color:#166534;background:linear-gradient(135deg, rgba(21, 128, 61, 0.28), rgba(22, 163, 74, 0.20));">
                <div>
                    <strong>{{ session('success') }}</strong>
                    <div class="sub" style="color:#bbf7d0;">You are now on the landing page. Log in on web or launch the app above.</div>
                </div>
            </section>
        @endif

        <header class="topbar">
            <div class="brand">POULTRY SYSTEM</div>
            <div class="chip">Integrated Management and Decision Support</div>
        </header>

        <section class="hero">
            <div class="hero-main">
                <div class="eyebrow">Smart Poultry Operations</div>
                <h1>Manage Farms, Orders, and Monitoring in One Platform</h1>
                <p>
                    Built for production teams and community buyers. Track farm performance,
                    streamline approvals, and centralize records from one trusted system.
                </p>
                <div class="actions">
                    <a href="{{ route('login') }}" class="btn btn-primary">Log In</a>
                    <a href="#register" class="btn btn-secondary">Create an Account</a>
                </div>
                <div class="roles">
                    <span class="role">Super Admin</span>
                    <span class="role">Farm Owner</span>
                    <span class="role">Consumer</span>
                </div>
            </div>

            <aside class="hero-side">
                <div class="stat">
                    <div class="label">Daily Monitoring</div>
                    <div class="value">Eggs + Flocks</div>
                </div>
                <div class="stat">
                    <div class="label">Business Flow</div>
                    <div class="value">Orders + Subscriptions</div>
                </div>
                <div class="stat">
                    <div class="label">Support Workflow</div>
                    <div class="value">Tickets + Approvals</div>
                </div>
            </aside>
        </section>

        <section class="panel-grid">
            <article class="panel">
                <h3>Farm Operations Visibility</h3>
                <p>Consolidate flock records, vaccinations, inventory, and production reports.</p>
            </article>
            <article class="panel">
                <h3>Role-Based Access Control</h3>
                <p>Each user role gets targeted tools while protecting sensitive actions.</p>
            </article>
            <article class="panel">
                <h3>Marketplace Integration</h3>
                <p>Connect farm owners and consumers with structured ordering workflows.</p>
            </article>
        </section>

        <section id="register" class="grid">
            <article class="card">
                <h2>Farm Owner Registration</h2>
                <p>
                    Apply as a farm owner using the dedicated registration form.
                    Your existing farm-owner form fields remain unchanged.
                </p>
                <a href="{{ route('client.register') }}" class="register-btn register-owner">Register as Farm Owner</a>
            </article>

            <article class="card">
                <h2>Consumer Registration</h2>
                <p>
                    Create a buyer account to browse and order products from registered farms.
                </p>
                <a href="{{ route('consumer.register') }}" class="register-btn register-consumer">Register as Consumer</a>
            </article>
        </section>

        <p class="note">Single entry URL: <strong>http://127.0.0.1:8000</strong> | One shared login button for all roles.</p>
    </div>
</body>
</html>