<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">

  <meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
  >

  <meta
    name="description"
    content="Portfolio of a System Developer & Entrepreneur specializing in full-stack systems, automation, database architecture, backend logic, and role-based security."
  >

  <meta
    name="theme-color"
    content="#0f172a"
  >

  <title>System Developer & Entrepreneur | Portfolio</title>

  <style>
    /* =========================================================
       DESIGN SYSTEM
    ========================================================= */

    :root {
      --bg-primary: #0f172a;
      --bg-secondary: #111c32;
      --bg-card: #1e293b;
      --bg-card-hover: #243449;

      --accent: #38bdf8;
      --accent-strong: #0ea5e9;
      --accent-soft: rgba(56, 189, 248, 0.12);

      --text-primary: #f8fafc;
      --text-secondary: #cbd5e1;
      --text-muted: #94a3b8;

      --border: rgba(148, 163, 184, 0.14);
      --border-accent: rgba(56, 189, 248, 0.35);

      --shadow-sm: 0 8px 25px rgba(0, 0, 0, 0.16);
      --shadow-lg: 0 20px 60px rgba(0, 0, 0, 0.28);

      --radius-sm: 10px;
      --radius-md: 16px;
      --radius-lg: 24px;

      --container: 1180px;

      --transition-fast: 180ms ease;
      --transition-normal: 280ms ease;
    }

    /* =========================================================
       RESET
    ========================================================= */

    *,
    *::before,
    *::after {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html {
      scroll-behavior: smooth;
      scroll-padding-top: 90px;
    }

    body {
      min-height: 100vh;
      background:
        radial-gradient(
          circle at 85% 10%,
          rgba(56, 189, 248, 0.08),
          transparent 30%
        ),
        radial-gradient(
          circle at 10% 55%,
          rgba(14, 165, 233, 0.05),
          transparent 28%
        ),
        var(--bg-primary);

      color: var(--text-primary);

      font-family:
        Inter,
        ui-sans-serif,
        system-ui,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

      line-height: 1.7;
      overflow-x: hidden;
    }

    img {
      max-width: 100%;
      display: block;
    }

    a {
      color: inherit;
      text-decoration: none;
    }

    button,
    a {
      -webkit-tap-highlight-color: transparent;
    }

    button {
      font: inherit;
    }

    ::selection {
      background: var(--accent);
      color: var(--bg-primary);
    }

    /* =========================================================
       ACCESSIBILITY
    ========================================================= */

    .skip-link {
      position: fixed;
      top: -100px;
      left: 20px;

      z-index: 9999;

      padding: 10px 16px;

      background: var(--accent);
      color: #082f49;

      border-radius: 8px;
      font-weight: 700;

      transition: top var(--transition-fast);
    }

    .skip-link:focus {
      top: 20px;
    }

    :focus-visible {
      outline: 3px solid var(--accent);
      outline-offset: 4px;
    }

    /* =========================================================
       LAYOUT
    ========================================================= */

    .container {
      width: min(
        calc(100% - 40px),
        var(--container)
      );

      margin-inline: auto;
    }

    .section {
      padding: 110px 0;
    }

    .section-header {
      max-width: 760px;
      margin-bottom: 50px;
    }

    .section-label {
      display: inline-flex;
      align-items: center;
      gap: 9px;

      margin-bottom: 14px;

      color: var(--accent);

      font-size: 0.8rem;
      font-weight: 800;

      letter-spacing: 0.16em;
      text-transform: uppercase;
    }

    .section-label::before {
      content: "";

      width: 28px;
      height: 2px;

      background: var(--accent);
      border-radius: 100px;
    }

    .section-title {
      color: var(--text-primary);

      font-size: clamp(
        2rem,
        4vw,
        3.1rem
      );

      line-height: 1.1;
      letter-spacing: -0.035em;

      margin-bottom: 18px;
    }

    .section-description {
      color: var(--text-muted);

      max-width: 700px;

      font-size: 1.02rem;
    }

    /* =========================================================
       HEADER / NAVIGATION
    ========================================================= */

    .site-header {
      position: fixed;
      top: 0;
      left: 0;

      width: 100%;
      z-index: 1000;

      border-bottom: 1px solid transparent;

      transition:
        background var(--transition-normal),
        border-color var(--transition-normal),
        box-shadow var(--transition-normal);
    }

    .site-header.scrolled {
      background: rgba(15, 23, 42, 0.85);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);

      border-bottom-color: var(--border);
      box-shadow: var(--shadow-sm);
    }

    .nav {
      min-height: 78px;

      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 30px;
    }

    .brand {
      display: inline-flex;
      align-items: center;
      gap: 11px;

      font-weight: 800;
      letter-spacing: -0.02em;
    }

    .brand-mark {
      display: grid;
      place-items: center;

      width: 38px;
      height: 38px;

      border: 1px solid var(--border-accent);
      border-radius: 11px;

      background: var(--accent-soft);
      color: var(--accent);

      font-size: 0.85rem;
      font-weight: 900;
    }

    .brand-text {
      color: var(--text-primary);
    }

    .brand-text span {
      color: var(--accent);
    }

    .nav-menu {
      display: flex;
      align-items: center;
      gap: 30px;

      list-style: none;
    }

    .nav-link {
      position: relative;

      color: var(--text-secondary);

      font-size: 0.92rem;
      font-weight: 650;

      transition: color var(--transition-fast);
    }

    .nav-link::after {
      content: "";

      position: absolute;
      left: 0;
      bottom: -7px;

      width: 0;
      height: 2px;

      background: var(--accent);

      transition: width var(--transition-fast);
    }

    .nav-link:hover {
      color: var(--text-primary);
    }

    .nav-link:hover::after {
      width: 100%;
    }

    .menu-toggle {
      display: none;

      border: 1px solid var(--border);
      background: var(--bg-card);

      width: 44px;
      height: 44px;

      border-radius: 10px;

      cursor: pointer;
    }

    .menu-toggle span {
      display: block;

      width: 20px;
      height: 2px;

      margin: 4px auto;

      background: var(--text-primary);

      transition: transform var(--transition-fast);
    }

    /* =========================================================
       HERO
    ========================================================= */

    .hero {
      position: relative;

      min-height: 100vh;

      display: grid;
      align-items: center;

      padding: 150px 0 100px;

      isolation: isolate;
    }

    .hero::before {
      content: "";

      position: absolute;
      z-index: -1;

      top: 18%;
      right: -10%;

      width: 420px;
      height: 420px;

      border-radius: 50%;

      background: rgba(56, 189, 248, 0.07);

      filter: blur(50px);
    }

    .hero-grid {
      display: grid;

      grid-template-columns:
        minmax(0, 1.4fr)
        minmax(300px, 0.7fr);

      align-items: center;
      gap: 80px;
    }

    .hero-content {
      max-width: 820px;
    }

    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;

      padding: 7px 12px;

      border: 1px solid var(--border-accent);
      border-radius: 999px;

      background: var(--accent-soft);
      color: var(--accent);

      font-size: 0.76rem;
      font-weight: 800;

      letter-spacing: 0.12em;
      text-transform: uppercase;

      margin-bottom: 25px;
    }

    .eyebrow-dot {
      width: 7px;
      height: 7px;

      border-radius: 50%;

      background: var(--accent);

      box-shadow:
        0 0 0 5px rgba(56, 189, 248, 0.09),
        0 0 18px rgba(56, 189, 248, 0.6);
    }

    .hero-title {
      max-width: 880px;

      color: var(--text-primary);

      font-size: clamp(
        3rem,
        7vw,
        5.9rem
      );

      line-height: 0.98;

      letter-spacing: -0.065em;

      margin-bottom: 28px;
    }

    .hero-title .accent {
      color: var(--accent);
    }

    .hero-description {
      max-width: 720px;

      color: var(--text-muted);

      font-size: clamp(
        1.05rem,
        2vw,
        1.22rem
      );

      line-height: 1.8;

      margin-bottom: 34px;
    }

    .hero-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;

      margin-bottom: 42px;
    }

    .button {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 9px;

      min-height: 48px;

      padding: 0 19px;

      border-radius: 10px;

      font-size: 0.91rem;
      font-weight: 750;

      cursor: pointer;

      transition:
        transform var(--transition-fast),
        background var(--transition-fast),
        border-color var(--transition-fast),
        box-shadow var(--transition-fast);
    }

    .button:hover {
      transform: translateY(-2px);
    }

    .button-primary {
      border: 1px solid var(--accent);
      background: var(--accent);
      color: #082f49;

      box-shadow:
        0 10px 25px rgba(56, 189, 248, 0.16);
    }

    .button-primary:hover {
      background: #67d1ff;
      border-color: #67d1ff;

      box-shadow:
        0 15px 35px rgba(56, 189, 248, 0.24);
    }

    .button-secondary {
      border: 1px solid var(--border);
      background: rgba(30, 41, 59, 0.55);
      color: var(--text-primary);
    }

    .button-secondary:hover {
      background: var(--bg-card);
      border-color: var(--border-accent);
    }

    /* =========================================================
       TECH TAGS
    ========================================================= */

    .tech-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 9px;

      max-width: 850px;

      list-style: none;
    }

    .tech-tag {
      padding: 7px 11px;

      border: 1px solid var(--border);

      border-radius: 7px;

      background: rgba(30, 41, 59, 0.55);

      color: var(--text-secondary);

      font-family:
        "SFMono-Regular",
        Consolas,
        "Liberation Mono",
        monospace;

      font-size: 0.76rem;

      transition:
        border-color var(--transition-fast),
        color var(--transition-fast),
        background var(--transition-fast);
    }

    .tech-tag:hover {
      color: var(--accent);
      border-color: var(--border-accent);
      background: var(--accent-soft);
    }

    /* =========================================================
       HERO SIDE PANEL
    ========================================================= */

    .hero-panel {
      position: relative;

      padding: 26px;

      border:
        1px solid var(--border);

      border-radius: var(--radius-lg);

      background:
        linear-gradient(
          150deg,
          rgba(30, 41, 59, 0.98),
          rgba(15, 23, 42, 0.92)
        );

      box-shadow: var(--shadow-lg);
    }

    .hero-panel::before {
      content: "";

      position: absolute;

      inset: 1px;

      border-radius: calc(
        var(--radius-lg) - 1px
      );

      background:
        linear-gradient(
          140deg,
          rgba(56, 189, 248, 0.07),
          transparent 35%
        );

      pointer-events: none;
    }

    .panel-header {
      position: relative;

      display: flex;
      align-items: center;
      gap: 8px;

      padding-bottom: 20px;

      border-bottom: 1px solid var(--border);

      margin-bottom: 22px;
    }

    .panel-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background: var(--accent);
    }

    .panel-header span {
      color: var(--text-muted);
      font-size: 0.78rem;
      font-weight: 700;

      text-transform: uppercase;
      letter-spacing: 0.11em;
    }

    .panel-code {
      position: relative;

      display: grid;
      gap: 13px;

      font-family:
        "SFMono-Regular",
        Consolas,
        "Liberation Mono",
        monospace;

      font-size: 0.78rem;
    }

    .code-row {
      display: flex;
      gap: 12px;
    }

    .code-key {
      color: var(--accent);
    }

    .code-value {
      color: var(--text-secondary);
    }

    .code-value.highlight {
      color: #7dd3fc;
    }

    /* =========================================================
       CASE STUDY
    ========================================================= */

    .portfolio-section {
      position: relative;
    }

    .case-study {
      overflow: hidden;

      border: 1px solid var(--border);
      border-radius: var(--radius-lg);

      background:
        linear-gradient(
          150deg,
          rgba(30, 41, 59, 0.96),
          rgba(15, 23, 42, 0.85)
        );

      box-shadow: var(--shadow-lg);
    }

    .case-study-top {
      display: grid;

      grid-template-columns:
        minmax(0, 1.2fr)
        minmax(280px, 0.8fr);

      gap: 50px;

      padding: 46px;

      border-bottom: 1px solid var(--border);
    }

    .case-study-number {
      color: var(--accent);

      font-family:
        "SFMono-Regular",
        Consolas,
        monospace;

      font-size: 0.8rem;
      font-weight: 800;

      letter-spacing: 0.15em;
      text-transform: uppercase;

      margin-bottom: 17px;
    }

    .case-study-title {
      color: var(--text-primary);

      font-size: clamp(
        1.8rem,
        3.5vw,
        3rem
      );

      line-height: 1.08;

      letter-spacing: -0.04em;

      margin-bottom: 20px;
    }

    .case-study-lead {
      color: var(--text-muted);

      font-size: 1rem;
      line-height: 1.8;

      max-width: 720px;
    }

    .case-study-meta {
      display: grid;
      gap: 12px;

      align-content: start;
    }

    .meta-card {
      padding: 15px 16px;

      border: 1px solid var(--border);
      border-radius: 12px;

      background: rgba(15, 23, 42, 0.5);
    }

    .meta-label {
      display: block;

      color: var(--text-muted);

      font-size: 0.7rem;
      font-weight: 800;

      text-transform: uppercase;
      letter-spacing: 0.1em;

      margin-bottom: 4px;
    }

    .meta-value {
      color: var(--text-primary);

      font-size: 0.91rem;
      font-weight: 650;
    }

    .case-study-body {
      display: grid;

      grid-template-columns:
        repeat(2, minmax(0, 1fr));

      gap: 0;
    }

    .case-study-block {
      padding: 40px;

      border-right: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
    }

    .case-study-block:nth-child(2n) {
      border-right: none;
    }

    .case-study-block:nth-last-child(-n + 2) {
      border-bottom: none;
    }

    .block-icon {
      display: grid;
      place-items: center;

      width: 42px;
      height: 42px;

      border: 1px solid var(--border-accent);
      border-radius: 11px;

      background: var(--accent-soft);
      color: var(--accent);

      font-size: 0.82rem;
      font-weight: 800;

      margin-bottom: 18px;
    }

    .block-title {
      color: var(--text-primary);

      font-size: 1.3rem;
      line-height: 1.25;

      margin-bottom: 13px;
    }

    .block-text {
      color: var(--text-muted);

      font-size: 0.93rem;
      line-height: 1.8;
    }

    .feature-list {
      display: grid;
      gap: 11px;

      list-style: none;

      margin-top: 20px;
    }

    .feature-list li {
      position: relative;

      padding-left: 25px;

      color: var(--text-secondary);

      font-size: 0.9rem;
    }

    .feature-list li::before {
      content: "";

      position: absolute;
      left: 0;
      top: 0.72em;

      width: 7px;
      height: 7px;

      border-radius: 50%;

      background: var(--accent);

      transform: translateY(-50%);
    }

    /* =========================================================
       SECURITY HIGHLIGHT
    ========================================================= */

    .security-highlight {
      grid-column: 1 / -1;

      padding: 42px;

      background:
        linear-gradient(
          135deg,
          rgba(56, 189, 248, 0.08),
          rgba(15, 23, 42, 0)
        );

      border-bottom: none;
    }

    .security-grid {
      display: grid;

      grid-template-columns:
        minmax(0, 1fr)
        minmax(280px, 0.8fr);

      align-items: center;
      gap: 45px;
    }

    .security-points {
      display: grid;

      grid-template-columns:
        repeat(2, minmax(0, 1fr));

      gap: 12px;

      list-style: none;
    }

    .security-point {
      padding: 15px;

      border: 1px solid var(--border);

      border-radius: 11px;

      background: rgba(15, 23, 42, 0.46);

      color: var(--text-secondary);

      font-size: 0.84rem;
    }

    .security-point strong {
      display: block;

      color: var(--text-primary);

      margin-bottom: 4px;
    }

    /* =========================================================
       IN DEVELOPMENT
    ========================================================= */

    .development-section {
      padding-top: 70px;
    }

    .upcoming-card {
      position: relative;

      overflow: hidden;

      padding: 38px;

      border:
        1px solid var(--border-accent);

      border-radius: var(--radius-lg);

      background:
        linear-gradient(
          135deg,
          rgba(56, 189, 248, 0.09),
          rgba(30, 41, 59, 0.75)
        );

      box-shadow:
        0 20px 70px rgba(0, 0, 0, 0.18);
    }

    .upcoming-card::after {
      content: "";

      position: absolute;

      right: -70px;
      bottom: -90px;

      width: 250px;
      height: 250px;

      border-radius: 50%;

      background:
        rgba(56, 189, 248, 0.08);

      filter: blur(12px);
    }

    .upcoming-layout {
      position: relative;
      z-index: 1;

      display: flex;
      align-items: center;
      justify-content: space-between;

      gap: 40px;
    }

    .status {
      display: inline-flex;
      align-items: center;
      gap: 8px;

      padding: 6px 10px;

      border-radius: 999px;

      background: rgba(56, 189, 248, 0.1);

      color: var(--accent);

      font-size: 0.7rem;
      font-weight: 800;

      letter-spacing: 0.11em;
      text-transform: uppercase;

      margin-bottom: 16px;
    }

    .status::before {
      content: "";

      width: 7px;
      height: 7px;

      border-radius: 50%;

      background: var(--accent);

      animation: pulse 1.8s infinite;
    }

    .upcoming-title {
      color: var(--text-primary);

      font-size: clamp(
        1.6rem,
        3vw,
        2.3rem
      );

      letter-spacing: -0.035em;

      line-height: 1.15;

      margin-bottom: 10px;
    }

    .upcoming-description {
      max-width: 700px;

      color: var(--text-muted);

      font-size: 0.94rem;
    }

    .upcoming-mark {
      flex: 0 0 auto;

      display: grid;
      place-items: center;

      width: 88px;
      height: 88px;

      border: 1px solid var(--border-accent);
      border-radius: 22px;

      background: rgba(15, 23, 42, 0.5);

      color: var(--accent);

      font-family:
        "SFMono-Regular",
        Consolas,
        monospace;

      font-size: 1.15rem;
      font-weight: 800;
    }

    @keyframes pulse {
      0%,
      100% {
        box-shadow:
          0 0 0 0
          rgba(56, 189, 248, 0.45);
      }

      50% {
        box-shadow:
          0 0 0 6px
          rgba(56, 189, 248, 0);
      }
    }

    /* =========================================================
       FOOTER
    ========================================================= */

    footer {
      padding: 55px 0 35px;

      border-top: 1px solid var(--border);

      margin-top: 50px;
    }

    .footer-inner {
      display: flex;
      align-items: center;
      justify-content: space-between;

      gap: 25px;
    }

    .copyright {
      color: var(--text-muted);

      font-size: 0.82rem;
    }

    .signoff {
      color: var(--text-secondary);

      font-size: 0.82rem;
    }

    .signoff strong {
      color: var(--accent);
    }

    /* =========================================================
       SCROLL REVEAL
    ========================================================= */

    .reveal {
      opacity: 0;
      transform: translateY(24px);

      transition:
        opacity 700ms ease,
        transform 700ms ease;
    }

    .reveal.visible {
      opacity: 1;
      transform: translateY(0);
    }

    /* =========================================================
       RESPONSIVE
    ========================================================= */

    @media (max-width: 1000px) {
      .hero-grid {
        grid-template-columns: 1fr;
        gap: 50px;
      }

      .hero-panel {
        max-width: 650px;
      }

      .case-study-top {
        grid-template-columns: 1fr;
        gap: 35px;
      }
    }

    @media (max-width: 800px) {
      .section {
        padding: 85px 0;
      }

      .nav {
        min-height: 70px;
      }

      .menu-toggle {
        display: block;
      }

      .nav-menu {
        position: absolute;

        top: calc(100% + 1px);
        left: 20px;
        right: 20px;

        display: none;
        flex-direction: column;
        align-items: stretch;
        gap: 0;

        padding: 10px;

        border:
          1px solid var(--border);

        border-radius: 15px;

        background:
          rgba(15, 23, 42, 0.96);

        box-shadow: var(--shadow-lg);

        backdrop-filter: blur(16px);
      }

      .nav-menu.open {
        display: flex;
      }

      .nav-link {
        display: block;

        padding: 12px 13px;

        border-radius: 8px;
      }

      .nav-link:hover {
        background: var(--accent-soft);
      }

      .nav-link::after {
        display: none;
      }

      .case-study-body {
        grid-template-columns: 1fr;
      }

      .case-study-block {
        border-right: none;
      }

      .case-study-block:nth-last-child(-n + 2) {
        border-bottom: 1px solid var(--border);
      }

      .case-study-block:last-child {
        border-bottom: none;
      }

      .security-grid {
        grid-template-columns: 1fr;
      }

      .upcoming-layout {
        align-items: flex-start;
        flex-direction: column;
      }

      .footer-inner {
        flex-direction: column;
        align-items: flex-start;
      }
    }

    @media (max-width: 600px) {
      .container {
        width: min(
          calc(100% - 28px),
          var(--container)
        );
      }

      .hero {
        padding-top: 125px;
      }

      .hero-title {
        font-size: clamp(
          2.7rem,
          14vw,
          4rem
        );
      }

      .hero-actions {
        flex-direction: column;
        align-items: stretch;
      }

      .button {
        width: 100%;
      }

      .case-study-top,
      .case-study-block,
      .security-highlight {
        padding: 28px 23px;
      }

      .security-points {
        grid-template-columns: 1fr;
      }

      .upcoming-card {
        padding: 27px 23px;
      }

      .upcoming-mark {
        width: 70px;
        height: 70px;
      }
    }

    /* =========================================================
       REDUCED MOTION
    ========================================================= */

    @media (prefers-reduced-motion: reduce) {
      html {
        scroll-behavior: auto;
      }

      *,
      *::before,
      *::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
      }

      .reveal {
        opacity: 1;
        transform: none;
      }
    }
  </style>
</head>

<body>

  <a
    class="skip-link"
    href="#main-content"
  >
    Skip to content
  </a>

  <!-- =======================================================
       HEADER
  ======================================================== -->

  <header class="site-header" id="site-header">

    <div class="container">

      <nav
        class="nav"
        aria-label="Primary navigation"
      >

        <a
          href="#home"
          class="brand"
          aria-label="Homepage"
        >
          <span class="brand-mark">&lt;/&gt;</span>

          <span class="brand-text">
            System<span>Dev</span>
          </span>
        </a>

        <button
          class="menu-toggle"
          type="button"
          aria-label="Toggle navigation"
          aria-expanded="false"
          aria-controls="primary-menu"
        >
          <span></span>
          <span></span>
          <span></span>
        </button>

        <ul
          class="nav-menu"
          id="primary-menu"
        >
          <li>
            <a
              class="nav-link"
              href="#home"
            >
              Home
            </a>
          </li>

          <li>
            <a
              class="nav-link"
              href="#case-studies"
            >
              Case Study
            </a>
          </li>

          <li>
            <a
              class="nav-link"
              href="#development"
            >
              In Development
            </a>
          </li>
        </ul>

      </nav>

    </div>

  </header>

  <!-- =======================================================
       MAIN
  ======================================================== -->

  <main id="main-content">

    <!-- HERO -->

    <section
      class="hero"
      id="home"
      aria-labelledby="hero-title"
    >

      <div class="container">

        <div class="hero-grid">

          <div class="hero-content reveal">

            <span class="eyebrow">
              <span class="eyebrow-dot"></span>
              Full-Stack Systems &amp; Digital Automation
            </span>

            <h1
              class="hero-title"
              id="hero-title"
            >
              System Developer
              <span class="accent">&amp;</span>
              Entrepreneur
            </h1>

            <p class="hero-description">
              I transform slow, fragmented, and manual workflows
              into structured digital systems that automate
              operations, centralize information, improve
              decision-making, and create scalable business value.
            </p>

            <div class="hero-actions">

              <a
                class="button button-primary"
                href="#case-studies"
              >
                Explore My Systems
                <span aria-hidden="true">→</span>
              </a>

              <a
                class="button button-secondary"
                href="#development"
              >
                See What I'm Building
              </a>

            </div>

            <ul
              class="tech-tags"
              aria-label="Technical skills"
            >

              <li class="tech-tag">PHP</li>
              <li class="tech-tag">MySQL</li>
              <li class="tech-tag">JavaScript</li>
              <li class="tech-tag">HTML5</li>
              <li class="tech-tag">CSS3</li>
              <li class="tech-tag">Backend Logic</li>
              <li class="tech-tag">Database Architecture</li>
              <li class="tech-tag">Role-Based Security</li>

            </ul>

          </div>

          <aside
            class="hero-panel reveal"
            aria-label="Developer profile summary"
          >

            <div class="panel-header">
              <span class="panel-dot"></span>

              <span>
                System Architecture
              </span>
            </div>

            <div class="panel-code">

              <div class="code-row">
                <span class="code-key">
                  focus:
                </span>

                <span class="code-value">
                  Full-Stack Development
                </span>
              </div>

              <div class="code-row">
                <span class="code-key">
                  approach:
                </span>

                <span class="code-value">
                  Workflow Automation
                </span>
              </div>

              <div class="code-row">
                <span class="code-key">
                  backend:
                </span>

                <span class="code-value highlight">
                  Business Logic
                </span>
              </div>

              <div class="code-row">
                <span class="code-key">
                  data:
                </span>

                <span class="code-value">
                  Structured Databases
                </span>
              </div>

              <div class="code-row">
                <span class="code-key">
                  security:
                </span>

                <span class="code-value">
                  Role-Based Access
                </span>
              </div>

              <div class="code-row">
                <span class="code-key">
                  objective:
                </span>

                <span class="code-value highlight">
                  Scalable Digital Systems
                </span>
              </div>

            </div>

          </aside>

        </div>

      </div>

    </section>

    <!-- CASE STUDIES -->

    <section
      class="section portfolio-section"
      id="case-studies"
      aria-labelledby="case-studies-title"
    >

      <div class="container">

        <div class="section-header reveal">

          <span class="section-label">
            Portfolio
          </span>

          <h2
            class="section-title"
            id="case-studies-title"
          >
            Case Studies
          </h2>

          <p class="section-description">
            Real-world system development focused on replacing
            inefficient manual processes with structured,
            secure, and measurable digital workflows.
          </p>

        </div>

        <article class="case-study reveal">

          <!-- CASE STUDY HEADER -->

          <div class="case-study-top">

            <div>

              <div class="case-study-number">
                Case Study / 001
              </div>

              <h3 class="case-study-title">
                Institutional Evaluation &amp;
                Performance Analytics System
              </h3>

              <p class="case-study-lead">
                A full-stack institutional evaluation platform
                designed to centralize assessment workflows,
                manage role-based responsibilities, protect
                sensitive evaluation data, and transform
                collected responses into usable performance
                analytics.
              </p>

            </div>

            <div class="case-study-meta">

              <div class="meta-card">
                <span class="meta-label">
                  System Type
                </span>

                <span class="meta-value">
                  Institutional Web Application
                </span>
              </div>

              <div class="meta-card">
                <span class="meta-label">
                  Architecture
                </span>

                <span class="meta-value">
                  Role-Based Full Stack
                </span>
              </div>

              <div class="meta-card">
                <span class="meta-label">
                  Core Stack
                </span>

                <span class="meta-value">
                  PHP / MySQL / JavaScript
                </span>
              </div>

              <div class="meta-card">
                <span class="meta-label">
                  Primary Goal
                </span>

                <span class="meta-value">
                  Evaluation Automation
                </span>
              </div>

            </div>

          </div>

          <!-- CASE STUDY BODY -->

          <div class="case-study-body">

            <!-- OVERVIEW -->

            <section class="case-study-block">

              <div
                class="block-icon"
                aria-hidden="true"
              >
                01
              </div>

              <h4 class="block-title">
                Project Overview
              </h4>

              <p class="block-text">
                The platform digitalizes an institution's
                evaluation lifecycle by connecting users,
                evaluation targets, questionnaires,
                submissions, permissions, and analytics
                within a unified application.
              </p>

              <ul class="feature-list">

                <li>
                  Centralized evaluation workflow
                </li>

                <li>
                  Structured user and staff management
                </li>

                <li>
                  Dynamic evaluation assignments
                </li>

                <li>
                  Performance-oriented reporting
                </li>

              </ul>

            </section>

            <!-- PROBLEM -->

            <section class="case-study-block">

              <div
                class="block-icon"
                aria-hidden="true"
              >
                02
              </div>

              <h4 class="block-title">
                The Real-World Problem
              </h4>

              <p class="block-text">
                Traditional evaluation workflows can depend
                heavily on paper forms, disconnected files,
                manual encoding, repetitive administrative
                tasks, and scattered records. These processes
                create unnecessary workload and make it harder
                to maintain consistency and visibility.
              </p>

              <ul class="feature-list">

                <li>
                  Reduced repetitive manual processing
                </li>

                <li>
                  Better data consistency
                </li>

                <li>
                  Centralized evaluation records
                </li>

                <li>
                  Faster access to institutional insights
                </li>

              </ul>

            </section>

            <!-- FEATURES -->

            <section class="case-study-block">

              <div
                class="block-icon"
                aria-hidden="true"
              >
                03
              </div>

              <h4 class="block-title">
                Key Features &amp; Architectural Value
              </h4>

              <p class="block-text">
                The system is built around clearly separated
                responsibilities and controlled workflows,
                allowing different institutional roles to
                interact with the same underlying system
                without exposing functionality that they
                should not access.
              </p>

              <ul class="feature-list">

                <li>
                  Role-specific dashboards and permissions
                </li>

                <li>
                  Staff classification and assignment logic
                </li>

                <li>
                  Dynamic questionnaire management
                </li>

                <li>
                  Evaluation submission tracking
                </li>

                <li>
                  Database-driven reporting and analytics
                </li>

              </ul>

            </section>

            <!-- ARCHITECTURE -->

            <section class="case-study-block">

              <div
                class="block-icon"
                aria-hidden="true"
              >
                04
              </div>

              <h4 class="block-title">
                System Architecture
              </h4>

              <p class="block-text">
                The architecture separates presentation,
                application logic, data handling, and access
                control responsibilities. This provides a
                stronger foundation for maintaining complex
                institutional rules while keeping the
                application extensible as requirements evolve.
              </p>

              <ul class="feature-list">

                <li>
                  Server-side business logic
                </li>

                <li>
                  Relational database structure
                </li>

                <li>
                  Controlled AJAX-based interactions
                </li>

                <li>
                  Reusable administrative workflows
                </li>

              </ul>

            </section>

            <!-- SECURITY -->

            <section class="security-highlight">

              <div class="security-grid">

                <div>

                  <div
                    class="block-icon"
                    aria-hidden="true"
                  >
                    05
                  </div>

                  <h4 class="block-title">
                    Enterprise Security &amp;
                    Privacy Architecture
                  </h4>

                  <p class="block-text">
                    Because institutional evaluation data can
                    contain sensitive information, security is
                    treated as an architectural requirement
                    rather than a visual afterthought.
                    Access is designed around authenticated
                    sessions, role boundaries, controlled
                    actions, and data-level responsibility.
                  </p>

                </div>

                <ul class="security-points">

                  <li class="security-point">
                    <strong>
                      Role-Based Access
                    </strong>
                    Users receive only the functionality
                    appropriate to their role.
                  </li>

                  <li class="security-point">
                    <strong>
                      Authentication
                    </strong>
                    Protected system areas require
                    authenticated access.
                  </li>

                  <li class="security-point">
                    <strong>
                      Data Isolation
                    </strong>
                    Sensitive operations are separated by
                    user responsibility and system role.
                  </li>

                  <li class="security-point">
                    <strong>
                      Controlled Workflows
                    </strong>
                    Evaluation actions are constrained by
                    defined application rules.
                  </li>

                  <li class="security-point">
                    <strong>
                      Database Integrity
                    </strong>
                    Structured relational data helps
                    preserve consistency across workflows.
                  </li>

                  <li class="security-point">
                    <strong>
                      Privacy Mindset
                    </strong>
                    Sensitive evaluation information is
                    treated as protected institutional data.
                  </li>

                </ul>

              </div>

            </section>

          </div>

        </article>

      </div>

    </section>

    <!-- IN DEVELOPMENT -->

    <section
      class="section development-section"
      id="development"
      aria-labelledby="development-title"
    >

      <div class="container">

        <div class="section-header reveal">

          <span class="section-label">
            In Development
          </span>

          <h2
            class="section-title"
            id="development-title"
          >
            Systems I'm Building Next
          </h2>

          <p class="section-description">
            Continuing to develop practical systems that
            automate real-world operations and improve the way
            organizations manage their workflows.
          </p>

        </div>

        <article class="upcoming-card reveal">

          <div class="upcoming-layout">

            <div>

              <span class="status">
                Actively Building
              </span>

              <h3 class="upcoming-title">
                Automated Service Appointment
                &amp; Workflow Booker
              </h3>

              <p class="upcoming-description">
                An upcoming workflow automation system focused
                on simplifying service appointment scheduling,
                reducing manual coordination, organizing
                booking data, and creating a smoother experience
                for both service providers and customers.
              </p>

            </div>

            <div
              class="upcoming-mark"
              aria-hidden="true"
            >
              002
            </div>

          </div>

        </article>

      </div>

    </section>

  </main>

  <!-- =======================================================
       FOOTER
  ======================================================== -->

  <footer>

    <div class="container">

      <div class="footer-inner">

        <p class="copyright">
          &copy;
          <span id="current-year"></span>
          System Developer &amp; Entrepreneur.
          All rights reserved.
        </p>

        <p class="signoff">
          Built with purpose.
          <strong>Engineered for impact.</strong>
        </p>

      </div>

    </div>

  </footer>

  <!-- =======================================================
       JAVASCRIPT
  ======================================================== -->

  <script>
    "use strict";

    document.addEventListener("DOMContentLoaded", () => {

      /* -------------------------------------------------------
         HEADER SCROLL STATE
      ------------------------------------------------------- */

      const header =
        document.getElementById("site-header");

      const updateHeader = () => {
        if (window.scrollY > 20) {
          header.classList.add("scrolled");
        } else {
          header.classList.remove("scrolled");
        }
      };

      updateHeader();

      window.addEventListener(
        "scroll",
        updateHeader,
        { passive: true }
      );

      /* -------------------------------------------------------
         MOBILE NAVIGATION
      ------------------------------------------------------- */

      const menuToggle =
        document.querySelector(".menu-toggle");

      const navMenu =
        document.querySelector(".nav-menu");

      if (menuToggle && navMenu) {

        menuToggle.addEventListener("click", () => {

          const isOpen =
            navMenu.classList.toggle("open");

          menuToggle.setAttribute(
            "aria-expanded",
            String(isOpen)
          );

        });

        document
          .querySelectorAll(".nav-link")
          .forEach((link) => {

            link.addEventListener("click", () => {

              navMenu.classList.remove("open");

              menuToggle.setAttribute(
                "aria-expanded",
                "false"
              );

            });

          });

        document.addEventListener(
          "click",
          (event) => {

            const target =
              event.target;

            if (
              !navMenu.contains(target) &&
              !menuToggle.contains(target)
            ) {
              navMenu.classList.remove("open");

              menuToggle.setAttribute(
                "aria-expanded",
                "false"
              );
            }

          }
        );
      }

      /* -------------------------------------------------------
         SCROLL REVEAL
      ------------------------------------------------------- */

      const revealElements =
        document.querySelectorAll(".reveal");

      if (
        "IntersectionObserver" in window &&
        revealElements.length
      ) {

        const observer =
          new IntersectionObserver(
            (entries, observerInstance) => {

              entries.forEach((entry) => {

                if (entry.isIntersecting) {

                  entry.target.classList.add(
                    "visible"
                  );

                  observerInstance.unobserve(
                    entry.target
                  );

                }

              });

            },
            {
              threshold: 0.12,
              rootMargin: "0px 0px -40px 0px"
            }
          );

        revealElements.forEach((element) => {
          observer.observe(element);
        });

      } else {

        revealElements.forEach((element) => {
          element.classList.add("visible");
        });

      }

      /* -------------------------------------------------------
         CURRENT YEAR
      ------------------------------------------------------- */

      const currentYear =
        document.getElementById("current-year");

      if (currentYear) {
        currentYear.textContent =
          new Date().getFullYear();
      }

    });
  </script>

</body>
</html>