<?php
// Wrapper SaaS : attend $content, $title, $page, $base_url
// Fournir un fallback pour $base_url si non injecté
$base_url = $base_url ?? rtrim(APP_URL, '/');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= isset($title) ? htmlspecialchars($title) : 'Ivoire Stay SaaS' ?></title>

  <!-- Tailwind CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Alpine.js CDN -->
  <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
  <!-- Google Fonts : Inter uniquement -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root {
      --saas-sidebar: #0F2B20;
      --saas-sidebar-hover: #1B4332;
      --saas-content: #F8F6F2;
      --saas-card: #FFFFFF;
      --saas-gold: #C9A84C;
      --saas-gold-light: rgba(201,168,76,0.12);
      --saas-text: #1a1a2e;
      --saas-text-muted: #6B7280;
      --saas-border: rgba(0,0,0,0.06);
      --saas-success: #16a34a;
      --saas-danger: #DC2626;
      --saas-warning: #D97706;
    }

    * { font-family: 'Inter', sans-serif; box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      min-height: 100vh;
      background: var(--saas-content);
      color: var(--saas-text);
    }
    button, input, select, textarea { font: inherit; }
    a { color: inherit; }

    .saas-layout {
      display: flex;
      min-height: 100vh;
      background: var(--saas-content);
    }

    .saas-sidebar {
      width: 260px;
      min-height: 100vh;
      background: var(--saas-sidebar);
      display: flex;
      flex-direction: column;
      position: fixed;
      top: 0;
      left: 0;
      z-index: 40;
      transition: transform 0.3s ease;
    }
    .saas-sidebar-logo {
      padding: 24px 20px 20px 20px;
      border-bottom: 1px solid rgba(255,255,255,0.06);
    }
    .saas-sidebar-logo img { height: 34px; width: auto; filter: brightness(0) invert(1); opacity: 0.92; }
    .saas-sidebar-logo .brand-title {
      color: white;
      font-size: 14px;
      font-weight: 700;
      line-height: 1;
    }
    .saas-sidebar-logo .brand-subtitle {
      color: rgba(255,255,255,0.45);
      font-size: 10px;
      margin-top: 2px;
    }

    .saas-nav-section { padding: 8px 12px; margin-top: 6px; }
    .saas-nav-label {
      font-size: 10px;
      font-weight: 600;
      color: rgba(255,255,255,0.32);
      text-transform: uppercase;
      letter-spacing: 0.12em;
      padding: 12px 8px 6px 8px;
    }
    .saas-nav-item {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 10px 12px;
      border-radius: 12px;
      color: rgba(255,255,255,0.7);
      text-decoration: none;
      font-size: 14px;
      font-weight: 500;
      transition: all 0.2s ease;
      margin-bottom: 2px;
      cursor: pointer;
    }
    .saas-nav-item:hover {
      background: rgba(255,255,255,0.08);
      color: white;
    }
    .saas-nav-item.active {
      background: rgba(201,168,76,0.16);
      color: var(--saas-gold);
      border: 1px solid rgba(201,168,76,0.22);
    }
    .saas-nav-item svg {
      width: 18px;
      height: 18px;
      flex-shrink: 0;
      color: rgba(255,255,255,0.45);
      transition: color 0.2s;
    }
    .saas-nav-item:hover svg { color: white; }
    .saas-nav-item.active svg { color: var(--saas-gold); }

    .nav-badge {
      margin-left: auto;
      background: var(--saas-gold);
      color: white;
      font-size: 10px;
      font-weight: 700;
      padding: 2px 7px;
      border-radius: 50px;
      min-width: 20px;
      text-align: center;
    }

    .saas-plan-card {
      margin: 12px;
      padding: 16px;
      background: var(--saas-gold-light);
      border: 1px solid rgba(201,168,76,0.2);
      border-radius: 16px;
    }
    .saas-plan-card .plan-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 8px;
    }
    .saas-plan-card .plan-label {
      font-size: 11px;
      color: rgba(255,255,255,0.7);
    }
    .saas-plan-card .plan-pill {
      background: rgba(201,168,76,0.2);
      border: 1px solid rgba(201,168,76,0.3);
      color: var(--saas-gold);
      font-size: 11px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 50px;
    }
    .saas-plan-card .plan-copy {
      font-size: 12px;
      color: rgba(255,255,255,0.7);
      margin-bottom: 10px;
      line-height: 1.5;
    }
    .saas-plan-card a {
      display: block;
      text-align: center;
      background: linear-gradient(135deg, var(--saas-gold), #A67C2E);
      color: white;
      font-size: 12px;
      font-weight: 600;
      padding: 8px;
      border-radius: 10px;
      text-decoration: none;
      transition: all 0.2s ease;
    }
    .saas-plan-card a:hover {
      transform: translateY(-1px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.08);
    }

    .saas-topbar {
      position: fixed;
      top: 0;
      left: 260px;
      right: 0;
      height: 64px;
      background: rgba(248,246,242,0.92);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--saas-border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0 28px;
      z-index: 30;
    }

    .saas-content {
      margin-left: 260px;
      margin-top: 64px;
      flex: 1;
      padding: 28px;
      min-height: calc(100vh - 64px);
      background: var(--saas-content);
    }

    .saas-card,
    .kpi-card {
      background: var(--saas-card);
      border-radius: 16px;
      border: 1px solid rgba(0,0,0,0.05);
      box-shadow: 0 1px 8px rgba(0,0,0,0.04);
      padding: 24px;
    }
    .saas-card-hover {
      transition: box-shadow 0.2s, transform 0.2s;
    }
    .saas-card-hover:hover {
      box-shadow: 0 8px 24px rgba(0,0,0,0.08);
      transform: translateY(-2px);
    }

    .kpi-card {
      position: relative;
      overflow: hidden;
      padding: 20px 24px;
    }
    .kpi-card::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, var(--saas-gold), #E8D5A3);
    }

    .btn-saas-primary,
    .btn-saas-secondary,
    .btn-saas-danger {
      border-radius: 10px;
      padding: 9px 20px;
      font-size: 14px;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: all 0.2s ease;
      text-decoration: none;
    }
    .btn-saas-primary {
      background: linear-gradient(135deg, var(--saas-gold), #A67C2E);
      color: white;
      border: none;
      font-weight: 600;
    }
    .btn-saas-primary:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(201,168,76,0.35);
    }
    .btn-saas-secondary {
      background: white;
      color: #374151;
      border: 1px solid rgba(0,0,0,0.1);
      font-weight: 500;
    }
    .btn-saas-secondary:hover {
      background: #F9FAFB;
      border-color: rgba(0,0,0,0.15);
    }
    .btn-saas-danger {
      background: #FEF2F2;
      color: var(--saas-danger);
      border: 1px solid rgba(220,38,38,0.15);
      font-weight: 500;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 4px;
      padding: 3px 10px;
      border-radius: 50px;
      font-size: 12px;
      font-weight: 500;
    }
    .badge-success { background:#DCFCE7; color:var(--saas-success); }
    .badge-warning { background:#FEF3C7; color:var(--saas-warning); }
    .badge-danger  { background:#FEF2F2; color:var(--saas-danger); }
    .badge-info    { background:#EFF6FF; color:#2563EB; }
    .badge-gold {
      background: var(--saas-gold-light);
      color: var(--saas-gold);
      border: 1px solid rgba(201,168,76,0.25);
    }

    .saas-table {
      width: 100%;
      border-collapse: collapse;
    }
    .saas-table th {
      text-align: left;
      font-size: 11px;
      font-weight: 600;
      color: #9CA3AF;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      padding: 12px 16px;
      border-bottom: 1px solid rgba(0,0,0,0.06);
      background: #FAFAFA;
    }
    .saas-table td {
      padding: 14px 16px;
      font-size: 14px;
      color: #374151;
      border-bottom: 1px solid rgba(0,0,0,0.04);
      vertical-align: middle;
    }
    .saas-table tr:hover td { background: rgba(201,168,76,0.03); }
    .saas-table tr:last-child td { border-bottom: none; }

    .saas-input {
      width: 100%;
      padding: 10px 14px;
      border: 1px solid rgba(0,0,0,0.1);
      border-radius: 10px;
      font-size: 14px;
      color: #374151;
      background: white;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .saas-input:focus {
      outline: none;
      border-color: var(--saas-gold);
      box-shadow: 0 0 0 3px rgba(201,168,76,0.1);
    }
    .saas-label {
      display: block;
      font-size: 12px;
      font-weight: 600;
      color: #374151;
      margin-bottom: 6px;
    }

    .saas-modal-bg {
      position: fixed;
      inset: 0;
      background: rgba(0,0,0,0.45);
      backdrop-filter: blur(4px);
      z-index: 100;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .saas-modal {
      background: white;
      border-radius: 20px;
      box-shadow: 0 24px 64px rgba(0,0,0,0.2);
      max-width: 560px;
      width: 100%;
      max-height: 90vh;
      overflow-y: auto;
    }
    .saas-modal-header {
      padding: 20px 24px;
      border-bottom: 1px solid rgba(0,0,0,0.06);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .saas-modal-body { padding: 24px; }
    .saas-modal-footer {
      padding: 16px 24px;
      border-top: 1px solid rgba(0,0,0,0.06);
      display: flex;
      gap: 10px;
      justify-content: flex-end;
    }

    ::-webkit-scrollbar { width: 5px; height: 5px; }
    ::-webkit-scrollbar-track { background: transparent; }
    ::-webkit-scrollbar-thumb {
      background: rgba(201,168,76,0.3);
      border-radius: 3px;
    }
    ::-webkit-scrollbar-thumb:hover { background: var(--saas-gold); }

    .saas-toast {
      position: fixed;
      bottom: 24px;
      right: 24px;
      z-index: 200;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .toast-item {
      background: white;
      border-radius: 12px;
      padding: 14px 18px;
      box-shadow: 0 8px 24px rgba(0,0,0,0.12);
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 14px;
      min-width: 300px;
      border-left: 4px solid var(--saas-gold);
      animation: slideInRight 0.3s ease;
    }
    @keyframes slideInRight {
      from { transform: translateX(100%); opacity: 0; }
      to { transform: translateX(0); opacity: 1; }
    }

    @media (max-width: 768px) {
      .saas-sidebar { transform: translateX(-100%); }
      .saas-sidebar.mobile-open { transform: translateX(0); }
      .saas-topbar { left: 0; }
      .saas-content { margin-left: 0; }
    }
  </style>
</head>

<body>
<div class="saas-layout"
  x-data="{
    sidebarOpen: false,
    user: JSON.parse(localStorage.getItem('user') ?? 'null'),
    establishment: null,
    establishments: [],
    apiBase: '<?= rtrim($base_url, '/') ?>',
    apiUrl(path) { return this.apiBase + path; },

    init() {
      const token = localStorage.getItem('token');
      if (!token) {
        window.location.href = '<?= $base_url ?? '' ?>/login';
        return;
      }

      const stored = localStorage.getItem('establishments');
      if (stored) {
        try { this.establishments = JSON.parse(stored); } catch (e) { this.establishments = []; }
      }

      const estId = localStorage.getItem('establishment_id');
      if (estId && this.establishments.length) {
        this.establishment = this.establishments.find(e => e.id == estId) ?? this.establishments[0];
      }
    },

    logout() {
      localStorage.clear();
      window.location.href = '<?= $base_url ?? '' ?>/login';
    },

    switchEstablishment(id) {
      localStorage.setItem('establishment_id', id);
      this.establishment = this.establishments.find(e => e.id == id);
      window.location.reload();
    },

    get userName() {
      return this.user?.name ?? 'Utilisateur';
    },
    get userRole() {
      return this.user?.role ?? 'owner';
    },
    get userInitials() {
      const parts = this.userName.split(' ').filter(Boolean);
      return (parts[0]?.[0] ?? '') + (parts[1]?.[0] ?? '');
    },
    get canSeeFinance() {
      return ['owner','superadmin'].includes(this.userRole);
    },
    get canSeeSettings() {
      return ['owner','superadmin'].includes(this.userRole);
    }
  }"
  x-init="init()"
>
  <aside class="saas-sidebar" :class="sidebarOpen ? 'mobile-open' : ''">
    <div class="saas-sidebar-logo">
      <div style="display:flex;align-items:center;gap:10px;">
        <img src="<?= $base_url ?? '' ?>/assets/logo.png" alt="Ivoire Stay">
        <div>
          <div class="brand-title">Ivoire Stay</div>
          <div class="brand-subtitle"
            x-text="establishment?.name ?? 'SaaS Hôtelier'"
            style="color:rgba(255,255,255,0.45);font-size:10px;margin-top:2px;max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
          </div>
        </div>
      </div>
    </div>

    <div style="padding:12px 12px 8px 12px;" x-show="establishments.length > 0">
      <div style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.08);border-radius:12px;padding:10px 12px;cursor:pointer;position:relative;"
        x-data="{ open: false }"
        @click="open = !open"
      >
        <div style="display:flex;align-items:center;justify-content:space-between;">
          <div>
            <div style="font-size:12px;color:rgba(255,255,255,0.45);margin-bottom:2px;">Établissement actif</div>
            <div style="font-size:13px;color:white;font-weight:600;" x-text="establishment?.name ?? 'Sélectionner...'"></div>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
            :class="open ? 'rotate-180' : ''"
            style="width:14px;height:14px;color:rgba(255,255,255,0.4);transition:transform 0.2s;"
          >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </div>

        <div x-show="open" x-transition
          @click.outside="open = false"
          style="position:absolute;top:100%;left:0;right:0;margin-top:4px;background:#1B4332;border:1px solid rgba(255,255,255,0.1);border-radius:10px;overflow:hidden;z-index:50;"
        >
          <template x-for="est in establishments" :key="est.id">
            <button type="button"
              @click.stop="switchEstablishment(est.id); open = false"
              style="width:100%;text-align:left;padding:10px 12px;font-size:13px;color:white;background:transparent;border:none;cursor:pointer;transition:background 0.2s;"
              :class="est.id == establishment?.id ? 'bg-[rgba(201,168,76,0.2)] text-[#C9A84C]' : ''"
              @mouseover="($el.style.background = 'rgba(255,255,255,0.06)')"
              @mouseout="($el.style.background = est.id == establishment?.id ? 'rgba(201,168,76,0.2)' : 'transparent')"
              x-text="est.name"
            ></button>
          </template>
        </div>
      </div>
    </div>

    <nav style="flex:1;overflow-y:auto;padding-bottom:16px;">
      <div class="saas-nav-section">
        <div class="saas-nav-label">Principal</div>

        <a href="<?= $base_url ?? '' ?>/saas" class="saas-nav-item <?= ($page ?? '') === 'dashboard' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
          </svg>
          Tableau de bord
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/planning" class="saas-nav-item <?= ($page ?? '') === 'planning' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
          </svg>
          Planning
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/bookings" class="saas-nav-item <?= ($page ?? '') === 'bookings' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
          </svg>
          Réservations
          <span class="nav-badge">3</span>
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/rooms" class="saas-nav-item <?= ($page ?? '') === 'rooms' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
          </svg>
          Chambres & Tarifs
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/clients" class="saas-nav-item <?= ($page ?? '') === 'clients' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Clients
        </a>
      </div>

      <div class="saas-nav-section" x-show="canSeeFinance">
        <div class="saas-nav-label">Finances</div>

        <a href="<?= $base_url ?? '' ?>/saas/invoices" class="saas-nav-item <?= ($page ?? '') === 'invoices' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
          Facturation
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/payments" class="saas-nav-item <?= ($page ?? '') === 'payments' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
          </svg>
          Paiements
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/expenses" class="saas-nav-item <?= ($page ?? '') === 'expenses' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
          </svg>
          Dépenses
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/reports" class="saas-nav-item <?= ($page ?? '') === 'reports' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
          </svg>
          Rapports
        </a>
      </div>

      <div class="saas-nav-section" x-show="canSeeSettings">
        <div class="saas-nav-label">Configuration</div>

        <a href="<?= $base_url ?? '' ?>/saas/settings" class="saas-nav-item <?= ($page ?? '') === 'settings' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Paramètres
        </a>

        <a href="<?= $base_url ?? '' ?>/saas/help" class="saas-nav-item <?= ($page ?? '') === 'help' ? 'active' : '' ?>">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Centre d'aide
        </a>
      </div>
    </nav>

    <div class="saas-plan-card">
      <div class="plan-header">
        <div class="plan-label">Plan actuel</div>
        <div class="plan-pill">STARTER</div>
      </div>
      <div class="plan-copy">Passez en Pro pour débloquer toutes les fonctionnalités</div>
      <a href="<?= $base_url ?? '' ?>/tarifs">Upgrader →</a>
    </div>

    <div style="padding:12px;">
      <button @click="logout()" style="width:100%;display:flex;align-items:center;gap:10px;background:rgba(220,38,38,0.08);border:1px solid rgba(220,38,38,0.15);color:rgba(220,38,38,0.9);border-radius:10px;padding:10px 12px;font-size:13px;font-weight:500;cursor:pointer;transition:all 0.2s;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:16px;height:16px;">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        Déconnexion
      </button>
    </div>
  </aside>

  <header class="saas-topbar">
    <div style="display:flex;align-items:center;gap:16px;">
      <button @click="sidebarOpen = !sidebarOpen" class="md:hidden" style="background:none;border:none;cursor:pointer;padding:4px;color:#374151;">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:22px;height:22px;">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
        </svg>
      </button>
      <div>
        <div style="font-size:16px;font-weight:700;color:#111827;">
          <?php
            $titles = [
              'dashboard' => 'Tableau de bord',
              'planning'  => 'Planning',
              'bookings'  => 'Réservations',
              'rooms'     => 'Chambres & Tarifs',
              'clients'   => 'Clients',
              'invoices'  => 'Facturation',
              'payments'  => 'Paiements',
              'expenses'  => 'Dépenses',
              'reports'   => 'Rapports',
              'settings'  => 'Paramètres',
              'help'      => 'Centre d\'aide',
            ];
            echo $titles[$page ?? 'dashboard'] ?? 'Dashboard';
          ?>
        </div>
        <div style="font-size:12px;color:#9CA3AF;">
          <?php
            $jours = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
            $mois = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
            $date = new DateTime();
            echo $jours[(int)$date->format('w')] . ' ' . $date->format('d') . ' ' . $mois[(int)$date->format('n') - 1] . ' ' . $date->format('Y');
          ?>
        </div>
        <div style="font-size:11px;color:#C9A84C;font-weight:500;margin-top:1px;"
          x-text="establishment?.name ?? ''"
          x-show="establishment?.name">
        </div>
      </div>
    </div>

    <div style="display:flex;align-items:center;gap:12px;">
      <div style="position:relative;" class="hidden md:block">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:15px;height:15px;position:absolute;left:10px;top:50%;transform:translateY(-50%);color:#9CA3AF;">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
        <input type="text" placeholder="Rechercher..." style="padding:8px 14px 8px 32px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);border-radius:10px;font-size:13px;color:#374151;outline:none;width:200px;transition:all 0.2s;"
          onfocus="this.style.borderColor='#C9A84C'; this.style.boxShadow='0 0 0 3px rgba(201,168,76,0.1)'"
          onblur="this.style.borderColor='rgba(0,0,0,0.08)'; this.style.boxShadow='none'"
        >
      </div>

      <div style="position:relative;" x-data="{ open: false }">
        <button @click="open = !open" style="position:relative;width:38px;height:38px;border-radius:10px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:18px;height:18px;color:#6B7280;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
          </svg>
          <div style="position:absolute;top:6px;right:6px;width:8px;height:8px;background:#DC2626;border-radius:50%;border:2px solid white;"></div>
        </button>
        <div x-show="open" x-transition @click.away="open=false" style="position:absolute;top:100%;right:0;margin-top:8px;width:300px;background:white;border-radius:16px;box-shadow:0 16px 48px rgba(0,0,0,0.12);border:1px solid rgba(0,0,0,0.06);overflow:hidden;z-index:50;">
          <div style="padding:16px 16px 12px;border-bottom:1px solid rgba(0,0,0,0.06);font-size:13px;font-weight:700;color:#111827;">Notifications</div>
          <div style="padding:8px;">
            <button type="button" style="width:100%;padding:10px;border-radius:10px;text-align:left;cursor:pointer;transition:background 0.2s;border:none;background:transparent;" @mouseover="($event.currentTarget.style.background='#F9FAFB')" @mouseout="($event.currentTarget.style.background='transparent')">
              <div style="font-size:13px;color:#111827;font-weight:500;">Nouvelle réservation</div>
              <div style="font-size:12px;color:#9CA3AF;margin-top:2px;">Chambre Deluxe · il y a 5 min</div>
            </button>
            <button type="button" style="width:100%;padding:10px;border-radius:10px;text-align:left;cursor:pointer;transition:background 0.2s;border:none;background:transparent;" @mouseover="($event.currentTarget.style.background='#F9FAFB')" @mouseout="($event.currentTarget.style.background='transparent')">
              <div style="font-size:13px;color:#111827;font-weight:500;">Check-in aujourd'hui</div>
              <div style="font-size:12px;color:#9CA3AF;margin-top:2px;">3 clients · à 14h00</div>
            </button>
          </div>
          <div style="padding:12px;text-align:center;border-top:1px solid rgba(0,0,0,0.06);">
            <a href="#" style="font-size:13px;color:var(--saas-gold);font-weight:500;text-decoration:none;">Voir tout →</a>
          </div>
        </div>
      </div>

      <div x-data="{ open: false }" style="position:relative;">
        <button @click="open = !open" style="display:flex;align-items:center;gap:8px;background:rgba(0,0,0,0.04);border:1px solid rgba(0,0,0,0.08);border-radius:10px;padding:6px 10px;cursor:pointer;transition:all 0.2s;">
          <div style="width:28px;height:28px;border-radius:8px;background:linear-gradient(135deg,var(--saas-gold),#A67C2E);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;color:white;" x-text="userInitials || 'IS'"></div>
          <div style="text-align:left;" class="hidden md:block">
            <div style="font-size:13px;font-weight:600;color:#111827;line-height:1.2;" x-text="userName"></div>
            <div style="font-size:11px;color:#9CA3AF;text-transform:capitalize;" x-text="userRole"></div>
          </div>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:14px;height:14px;color:#9CA3AF;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
          </svg>
        </button>
        <div x-show="open" x-transition @click.away="open=false" style="position:absolute;top:100%;right:0;margin-top:8px;width:200px;background:white;border-radius:14px;box-shadow:0 16px 48px rgba(0,0,0,0.12);border:1px solid rgba(0,0,0,0.06);overflow:hidden;z-index:50;">
          <div style="padding:8px;">
            <a href="<?= $base_url ?? '' ?>/saas/settings" style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;color:#374151;text-decoration:none;font-size:13px;transition:background 0.2s;" @mouseover="($event.currentTarget.style.background='#F9FAFB')" @mouseout="($event.currentTarget.style.background='transparent')">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:15px;height:15px;color:#9CA3AF;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
              </svg>
              Mon profil
            </a>
            <a href="<?= $base_url ?? '' ?>/saas/settings" style="display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;color:#374151;text-decoration:none;font-size:13px;transition:background 0.2s;" @mouseover="($event.currentTarget.style.background='#F9FAFB')" @mouseout="($event.currentTarget.style.background='transparent')">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:15px;height:15px;color:#9CA3AF;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              Paramètres
            </a>
          </div>
          <div style="border-top:1px solid rgba(0,0,0,0.06);padding:8px;">
            <button @click="logout()" style="width:100%;display:flex;align-items:center;gap:10px;padding:10px;border-radius:8px;color:#DC2626;font-size:13px;background:none;border:none;cursor:pointer;transition:background 0.2s;" @mouseover="($event.currentTarget.style.background='#FEF2F2')" @mouseout="($event.currentTarget.style.background='transparent')">
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" style="width:15px;height:15px;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
              </svg>
              Déconnexion
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main class="saas-content">
    <?= $content ?? '' ?>
  </main>

  <div x-show="sidebarOpen" @click="sidebarOpen = false" class="md:hidden" style="position:fixed;inset:0;z-index:39;background:rgba(0,0,0,0.5);backdrop-filter:blur(2px);"></div>
</div>
</body>
</html>
