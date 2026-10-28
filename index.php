<?php /* HornetCloud placeholder page - safe to remove */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Welcome to stag.pios.com.my</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">

  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
      --bg:        #0b0f1a;
      --surface:   #111827;
      --border:    rgba(255,255,255,0.07);
      --text:      #f1f5f9;
      --muted:     #64748b;
      --accent:    #f59e0b;
      --green:     #22c55e;
    }

    body {
      background: var(--bg);
      color: var(--text);
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      position: relative;
      overflow: hidden;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      background-image:
        linear-gradient(rgba(245,158,11,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(245,158,11,0.03) 1px, transparent 1px);
      background-size: 40px 40px;
      pointer-events: none;
    }

    body::after {
      content: '';
      position: fixed;
      top: -20%;
      left: 50%;
      transform: translateX(-50%);
      width: 600px;
      height: 400px;
      background: radial-gradient(ellipse, rgba(245,158,11,0.08) 0%, transparent 70%);
      pointer-events: none;
    }

    .card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 16px;
      padding: 3rem 3.5rem;
      max-width: 560px;
      width: 100%;
      text-align: center;
      position: relative;
      z-index: 1;
      box-shadow: 0 0 0 1px rgba(245,158,11,0.05), 0 32px 80px rgba(0,0,0,0.5);
      animation: rise 0.6s cubic-bezier(0.22,1,0.36,1) both;
    }

    @keyframes rise {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    .card::before {
      content: '';
      position: absolute;
      top: 0; left: 10%; right: 10%;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--accent), transparent);
      border-radius: 100%;
    }

    .status {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      background: rgba(34,197,94,0.1);
      border: 1px solid rgba(34,197,94,0.2);
      color: var(--green);
      font-size: 0.72rem;
      font-weight: 500;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 0.35rem 0.9rem;
      border-radius: 100px;
      margin-bottom: 2rem;
    }

    .status::before {
      content: '';
      width: 6px; height: 6px;
      background: var(--green);
      border-radius: 50%;
      box-shadow: 0 0 8px var(--green);
      animation: pulse 2s ease infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50%       { opacity: 0.4; }
    }

    .icon-wrap {
      width: 72px; height: 72px;
      background: rgba(245,158,11,0.1);
      border: 1px solid rgba(245,158,11,0.2);
      border-radius: 18px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.8rem;
      font-size: 2rem;
    }

    h1 {
      font-family: 'Space Grotesk', sans-serif;
      font-size: 1.75rem;
      font-weight: 700;
      color: var(--text);
      line-height: 1.2;
      margin-bottom: 0.75rem;
    }

    h1 span { color: var(--accent); }

    .sub {
      font-size: 0.92rem;
      color: var(--muted);
      line-height: 1.65;
      max-width: 36ch;
      margin: 0 auto 2.2rem;
    }

    .divider {
      border: none;
      border-top: 1px solid var(--border);
      margin: 2rem 0;
    }

    .info-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      text-align: left;
      margin-bottom: 2rem;
    }

    .info-list li {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      font-size: 0.85rem;
      color: var(--muted);
    }

    .info-list li .dot {
      width: 6px; height: 6px;
      border-radius: 50%;
      background: var(--accent);
      flex-shrink: 0;
    }

    .powered {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      font-size: 0.78rem;
      color: var(--muted);
      text-decoration: none;
      padding: 0.6rem 1.2rem;
      border: 1px solid var(--border);
      border-radius: 100px;
      transition: border-color 0.2s, color 0.2s;
    }

    .powered:hover {
      border-color: rgba(245,158,11,0.3);
      color: var(--accent);
    }

    .powered .logo-mark {
      font-family: 'Space Grotesk', sans-serif;
      font-weight: 700;
      font-size: 0.82rem;
      color: var(--accent);
    }

    @media (max-width: 480px) {
      .card { padding: 2rem 1.5rem; }
      h1 { font-size: 1.4rem; }
    }
  </style>
</head>
<body>

  <div class="card">

    <div class="status">Website Active</div>

    <div class="icon-wrap">🌐</div>

    <h1>Welcome to <span>stag.pios.com.my</span></h1>
    <p class="sub">
      Your website has been successfully created and is now live.
      Replace this page with your own content to get started.
    </p>

    <hr class="divider">

    <ul class="info-list">
      <li><span class="dot"></span> Domain is pointing correctly</li>
      <li><span class="dot"></span> PHP <?php echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION; ?> is ready</li>
      <li><span class="dot"></span> Server is running and healthy</li>
      <li><span class="dot"></span> Upload your files via File Manager or FTP to begin</li>
    </ul>

    <hr class="divider">

    <a href="https://hornetcloud.com" class="powered">
      <span>Powered by</span>
      <span class="logo-mark">🐝 HornetCloud</span>
    </a>

  </div>

</body>
</html>
