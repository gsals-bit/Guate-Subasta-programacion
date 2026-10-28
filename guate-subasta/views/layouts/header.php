/* ==========================================================
   Guate-Subasta · estilos principales
   Las fuentes se cargan desde header.php (Bricolage Grotesque + DM Sans)
   ========================================================== */
:root {
  --ink: #0a1226;
  --navy: #101a34;
  --navy-2: #1a2748;
  --blue: #2f5bea;
  --blue-dark: #2548c4;
  --gold: #e8a317;
  --gold-soft: #fdf3d8;
  --green: #0f9d74;
  --green-soft: #def5ec;
  --red: #d6403f;
  --red-soft: #fde8e8;
  --violet: #7c5cf0;
  --paper: #f3f4f8;
  --card: #ffffff;
  --text: #121a2e;
  --muted: #667089;
  --line: #e3e6ee;
  --shadow: 0 1px 2px rgba(10,18,38,.05), 0 8px 24px -12px rgba(10,18,38,.14);
  --shadow-lift: 0 2px 4px rgba(10,18,38,.06), 0 18px 34px -14px rgba(10,18,38,.22);
  --r-sm: 8px;
  --r-md: 12px;
  --r-lg: 16px;
  --font-display: 'Bricolage Grotesque', 'DM Sans', system-ui, sans-serif;
  --font-body: 'DM Sans', system-ui, -apple-system, 'Segoe UI', Arial, sans-serif;
}

* { box-sizing: border-box; }

body {
  margin: 0;
  font-family: var(--font-body);
  background: var(--paper);
  color: var(--text);
  line-height: 1.55;
  -webkit-font-smoothing: antialiased;
}

a { color: var(--blue); text-decoration: none; transition: color .15s ease, background-color .15s ease; }

:focus-visible { outline: 3px solid rgba(47,91,234,.45); outline-offset: 2px; }

.ico { width: 20px; height: 20px; flex-shrink: 0; }

/* ---------- BARRA SUPERIOR ---------- */
header {
  height: 64px;
  background: var(--ink);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  position: sticky;
  top: 0;
  z-index: 100;
  border-bottom: 2px solid var(--gold);
}
header a { color: #fff; }

.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  font-family: var(--font-display);
  font-size: 1.4rem;
  font-weight: 800;
  letter-spacing: -.02em;
}
.brand em { font-style: normal; color: var(--gold); }
.brand-dash { color: var(--gold); }
.brand-mark {
  width: 36px; height: 36px;
  display: grid; place-items: center;
  border-radius: 10px;
  background: var(--gold);
  color: var(--ink);
}
.brand-mark .ico { width: 20px; height: 20px; }

nav { display: flex; gap: 12px; align-items: center; }

.bell-btn {
  position: relative;
  width: 40px; height: 40px;
  display: inline-grid; place-items: center;
  border-radius: 50%;
  background: rgba(255,255,255,.07);
  color: #e6eaf5;
}
.bell-btn:hover { background: rgba(232,163,23,.22); color: var(--gold); }
.bell-badge {
  position: absolute; top: -3px; right: -3px;
  min-width: 19px; height: 19px; padding: 0 5px;
  display: grid; place-items: center;
  background: var(--red); color: #fff;
  font-size: .7rem; font-weight: 700;
  border-radius: 999px;
  border: 2px solid var(--ink);
}

.user-pill {
  display: inline-flex; align-items: center; gap: 10px;
  padding: 4px 16px 4px 4px;
  border-radius: 999px;
  background: rgba(255,255,255,.07);
}
.avatar {
  width: 32px; height: 32px;
  display: grid; place-items: center;
  border-radius: 50%;
  background: var(--blue); color: #fff;
  font-weight: 700; font-size: .9rem;
}
.user-text { display: flex; flex-direction: column; line-height: 1.15; }
.user-text b { font-size: .88rem; font-weight: 600; color: #fff; }
.user-text small { font-size: .74rem; color: #9fabc9; }

.logout-btn {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 8px 14px;
  border-radius: 999px;
  border: 1px solid rgba(255,255,255,.18);
  color: #dbe1f0 !important;
  font-size: .86rem; font-weight: 600;
}
.logout-btn .ico { width: 17px; height: 17px; }
.logout-btn:hover { background: var(--red); border-color: var(--red); color: #fff !important; }

/* ---------- ESTRUCTURA Y MENÚ LATERAL ---------- */
.shell { display: flex; min-height: calc(100vh - 66px); }

aside {
  width: 248px;
  flex-shrink: 0;
  background: var(--navy);
  padding: 22px 14px;
}
.nav-link {
  display: flex; align-items: center; gap: 12px;
  padding: 11px 14px;
  margin-bottom: 4px;
  border-radius: var(--r-md);
  color: #b4bfd8;
  font-weight: 500;
  font-size: .94rem;
}
.nav-link:hover { background: rgba(255,255,255,.06); color: #fff; }
.nav-link.active {
  background: var(--navy-2);
  color: #fff;
  font-weight: 600;
  box-shadow: inset 3px 0 0 var(--gold);
}
.nav-link.active .ico { color: var(--gold); }
.nav-group {
  margin: 22px 14px 8px;
  padding-top: 18px;
  border-top: 1px solid rgba(255,255,255,.09);
  font-size: .78rem;
  font-weight: 600;
  color: #7f8bab;
}

/* ---------- CONTENIDO ---------- */
main {
  flex: 1;
  min-width: 0;
  width: 100%;
  max-width: 1440px;
  margin: 0 auto;
  padding: 34px 40px 48px;
}
h1 {
  font-family: var(--font-display);
  font-size: 2.1rem;
  font-weight: 800;
  letter-spacing: -.03em;
  line-height: 1.15;
  color: var(--ink);
  margin: 0 0 6px;
}
h2 {
  font-family: var(--font-display);
  font-size: 1.25rem;
  font-weight: 700;
  letter-spacing: -.015em;
  color: var(--ink);
  margin: 0 0 14px;
}
h3 { font-family: var(--font-display); font-weight: 700; letter-spacing: -.01em; }
main > p { color: var(--muted); margin: 0 0 26px; max-width: 70ch; }

/* ---------- TARJETAS DE ESTADÍSTICAS ---------- */
.stats {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 18px;
  margin: 24px 0 28px;
}
.stats > div {
  position: relative;
  min-height: 134px;
  display: flex; flex-direction: column;
  padding: 20px 22px;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--r-lg);
  box-shadow: var(--shadow);
}
.stats small { color: var(--muted); font-size: .88rem; font-weight: 500; }
.stats b {
  display: block;
  margin-top: 6px;
  font-family: var(--font-display);
  font-size: 2.4rem;
  font-weight: 800;
  line-height: 1.05;
  letter-spacing: -.03em;
  color: var(--ink);
  font-variant-numeric: tabular-nums;
}
.stats small:not(:first-child) { margin-top: auto; padding-top: 10px; font-size: .8rem; }

/* Ícono de cada métrica (esquina superior derecha) */
.stats > div::before, .stats > div::after {
  content: ''; position: absolute; top: 18px; right: 20px;
  width: 40px; height: 40px; border-radius: 12px;
}
.stats > div::before { background: currentColor; opacity: .12; }
.stats > div::after {
  background: currentColor;
  width: 20px; height: 20px; top: 28px; right: 30px; border-radius: 0;
  -webkit-mask: var(--stat-ico) center / contain no-repeat;
          mask: var(--stat-ico) center / contain no-repeat;
}
.stats > div:nth-child(1) { color: var(--blue); --stat-ico: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'/><circle cx='9' cy='7' r='4'/><path d='M22 21v-2a4 4 0 0 0-3-3.87'/><path d='M16 3.13a4 4 0 0 1 0 7.75'/></svg>"); }
.stats > div:nth-child(2) { color: var(--gold); --stat-ico: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='m7.5 4.27 9 5.15'/><path d='M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z'/><path d='m3.3 7 8.7 5 8.7-5'/><path d='M12 22V12'/></svg>"); }
.stats > div:nth-child(3) { color: var(--green); --stat-ico: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='m14 13-8.5 8.5a2.12 2.12 0 0 1-3-3L11 10'/><path d='m16 16 6-6'/><path d='m8 8 6-6'/><path d='m9 7 8 8'/><path d='m21 11-8-8'/></svg>"); }
.stats > div:nth-child(4) { color: var(--violet); --stat-ico: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='m22 7-8.5 8.5-5-5L2 17'/><path d='M16 7h6v6'/></svg>"); }
.stats > div > * { position: relative; color: initial; }
.stats small { color: var(--muted); }
.stats b { color: var(--ink); }

/* ---------- PANELES Y TARJETAS DE SUBASTA ---------- */
.card, .panel {
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: var(--r-lg);
  padding: 26px;
  box-shadow: var(--shadow);
}
.panel { overflow-x: auto; }

.cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(min(100%, 280px), 1fr));
  gap: 22px;
}
.item-subasta {
  overflow: hidden;
  display: flex; flex-direction: column; justify-content: space-between;
  transition: transform .2s ease, box-shadow .2s ease;
}
.item-subasta:hover { transform: translateY(-4px); box-shadow: var(--shadow-lift); }
.item-subasta img {
  width: 100%;
  object-fit: contain !important;
  background: #f6f7fb !important;
  padding: 8px !important;
  border-radius: var(--r-md) !important;
  border: 1px solid var(--line);
}
.item-subasta h3 { font-size: 1.15rem; color: var(--ink); min-height: 2.6em; margin: 14px 0 6px; }

.placeholder, .hero {
  height: 150px;
  display: grid; place-items: center;
  background: linear-gradient(135deg, #eef0f7, #e2e6f1);
  border-radius: var(--r-md);
  font-size: 50px;
}
.hero { height: 270px; }
.detail, .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.narrow { max-width: 680px; }

/* Cuenta regresiva (uso: <span class="countdown" data-countdown="2026-11-01 12:00:00"></span>) */
.countdown {
  display: inline-flex; align-items: center; gap: 8px;
  padding: 4px 12px 4px 10px;
  border-radius: 999px;
  background: var(--gold-soft);
  color: #8a5b00;
  font-size: .84rem; font-weight: 700;
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}
.countdown::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--gold); }
.countdown.is-urgent { background: var(--red-soft); color: #a22826; }
.countdown.is-urgent::before { background: var(--red); animation: pulso 1.2s ease-in-out infinite; }
.countdown.is-done { background: #eceef4; color: var(--muted); }
.countdown.is-done::before { background: #a9b0c2; }
@keyframes pulso { 50% { opacity: .25; } }

/* ---------- BOTONES (principal · .gold · .ghost · .danger · .sm) ---------- */
button, .button {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  margin-top: 12px;
  padding: 11px 20px;
  background: var(--blue);
  color: #fff;
  border: 1px solid transparent;
  border-radius: var(--r-md);
  font-family: inherit; font-weight: 600; font-size: .93rem;
  cursor: pointer;
  transition: background-color .15s ease, transform .15s ease, box-shadow .15s ease;
}
button:hover, .button:hover { background: var(--blue-dark); color: #fff; box-shadow: 0 6px 16px -6px rgba(47,91,234,.55); }
button:active, .button:active { transform: translateY(1px); }
.button.gold, button.gold { background: var(--gold); color: var(--ink); }
.button.gold:hover, button.gold:hover { background: #d4920d; color: var(--ink); box-shadow: 0 6px 16px -6px rgba(232,163,23,.6); }
.button.ghost, button.ghost { background: transparent; color: var(--ink); border-color: #cfd4e2; }
.button.ghost:hover, button.ghost:hover { background: #eceef6; color: var(--ink); box-shadow: none; }
.button.danger, button.danger { background: var(--red-soft); color: #a22826; }
.button.danger:hover, button.danger:hover { background: var(--red); color: #fff; box-shadow: none; }
.button.sm, button.sm, td .button, td button { padding: 7px 14px; font-size: .85rem; border-radius: 9px; }
td .button, td button { margin-top: 0; }

/* ---------- ESTADOS (BADGES) ---------- */
.badge {
  display: inline-flex; align-items: center; gap: 6px;
  padding: 4px 11px;
  border-radius: 999px;
  background: var(--green-soft);
  color: #0b7a5a;
  font-size: .78rem; font-weight: 700;
  text-transform: capitalize;
}
.badge::before { content: ''; width: 6px; height: 6px; border-radius: 50%; background: currentColor; }
.badge.programada, .badge.warn, .badge.pendiente { background: var(--gold-soft); color: #8a5b00; }
.badge.cerrada, .badge.muted, .badge.finalizada { background: #eceef4; color: #5a647c; }
.badge.cancelada, .badge.danger, .badge.inactivo { background: var(--red-soft); color: #a22826; }

/* ---------- TABLAS ---------- */
table {
  width: 100%;
  border-collapse: separate;
  border-spacing: 0;
  display: block;
  overflow-x: auto;
}
@media (min-width: 900px) { table { display: table; } }
th, td {
  text-align: left;
  padding: 14px 14px;
  border-bottom: 1px solid #eef0f5;
  vertical-align: middle;
}
td:not(:first-child), th:not(:first-child) { white-space: nowrap; }  /* evita fechas partidas en 3 líneas */
td:first-child { min-width: 170px; }
th {
  background: #f7f8fb;
  color: var(--muted);
  font-size: .82rem;
  font-weight: 600;
  border-bottom: 1px solid var(--line);
}
th:first-child { border-top-left-radius: var(--r-sm); }
th:last-child { border-top-right-radius: var(--r-sm); }
tbody tr:hover td { background: #fafbfd; }
tbody tr:last-child td { border-bottom: 0; }

/* ---------- FORMULARIOS Y LOGIN ---------- */
.auth-card {
  max-width: 440px;
  margin: 55px auto;
  padding: 38px;
  background: var(--card);
  border: 1px solid var(--line);
  border-radius: 20px;
  box-shadow: var(--shadow-lift);
}
label { display: block; margin: 16px 0 6px; font-weight: 600; font-size: .9rem; color: #2c3550; }
input, select, textarea {
  width: 100%; max-width: 100%; min-width: 0;
  padding: 11px 14px;
  background: #fafbfd;
  border: 1.5px solid #d3d8e6;
  border-radius: var(--r-md);
  font-family: inherit; font-size: .95rem; color: var(--text);
  transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
}
input:focus, select:focus, textarea:focus {
  outline: none;
  background: #fff;
  border-color: var(--blue);
  box-shadow: 0 0 0 4px rgba(47,91,234,.14);
}
textarea { min-height: 95px; }

/* ---------- ALERTAS Y EXTRAS ---------- */
.flash {
  margin-bottom: 22px;
  padding: 14px 18px;
  background: var(--gold-soft);
  border-left: 4px solid var(--gold);
  border-radius: var(--r-md);
  color: #6f4a00;
  font-weight: 500;
}
.demo { margin-top: 20px; padding: 12px 14px; background: #eef0f6; border-radius: var(--r-md); font-size: .88rem; }
.location-fields { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.hint { font-size: .85rem; color: var(--muted); }
.pickup-location { margin: 18px 0; padding: 18px; background: #eef3ff; border: 1px solid #c9d7fb; border-radius: 14px; }
.zoomable { cursor: zoom-in; }
.zoom-overlay {
  position: fixed; inset: 0; z-index: 1000;
  display: flex; align-items: center; justify-content: center;
  padding: 25px;
  background: rgba(6,12,28,.92);
  backdrop-filter: blur(4px);
  cursor: zoom-out;
}
.zoom-overlay img { max-width: 95vw; max-height: 90vh; object-fit: contain; border-radius: var(--r-md); box-shadow: 0 25px 50px rgba(0,0,0,.5); }

/* ---------- RESPONSIVE ---------- */
@media (max-width: 1100px) {
  .stats { grid-template-columns: repeat(2, 1fr); }
}
@media (max-width: 950px) {
  header { height: auto; min-height: 64px; padding: 10px 18px; gap: 10px; flex-wrap: wrap; }
  nav { flex-wrap: wrap; gap: 10px; }
  aside { width: 200px; padding: 16px 10px; }
  main { padding: 22px; }
  .grid2, .detail { grid-template-columns: 1fr; }
}
@media (max-width: 700px) {
  .shell { display: block; }
  aside { display: flex; width: 100%; overflow-x: auto; gap: 6px; padding: 10px 12px; }
  .nav-link { flex: 0 0 auto; white-space: nowrap; margin: 0; padding: 9px 12px; font-size: .86rem; }
  .nav-link.active { box-shadow: inset 0 -3px 0 var(--gold); }
  .nav-group { display: none; }
  main { padding: 16px 14px 32px; }
  .user-text small, .logout-btn span { display: none; }
  .user-pill { padding-right: 12px; }
  .logout-btn { padding: 9px; }
  .location-fields { grid-template-columns: 1fr; }
  .auth-card { margin: 18px auto; padding: 24px; }
  h1 { font-size: 1.65rem; }
}
@media (max-width: 480px) {
  .stats, .cards { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
  * { animation: none !important; transition: none !important; }
}

/* ---------- IMPRESIÓN ---------- */
@media print {
  header, aside, .audit-actions, button, .flash, #filtro-auditoria-texto, #filtro-auditoria-evento { display: none !important; }
  body, main { background: #fff !important; color: #000 !important; padding: 0 !important; }
  .shell { display: block; }
  table { display: table; font-size: 10px; }
  th, td { padding: 5px; overflow-wrap: anywhere; white-space: normal; }
  section.panel { box-shadow: none; border: 0; }
}