<?php
$user = $_SESSION['user'] ?? null;
$currentPage = $_GET['page'] ?? 'dashboard';

/* Íconos de línea (SVG en línea, no dependen de ninguna librería externa) */
$ic = fn(string $d): string => '<svg class="ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
$icons = [
  'home'     => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z"/>',
  'gavel'    => '<path d="m14 13-8.5 8.5a2.12 2.12 0 0 1-3-3L11 10"/><path d="m16 16 6-6"/><path d="m8 8 6-6"/><path d="m9 7 8 8"/><path d="m21 11-8-8"/>',
  'pujas'    => '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
  'box'      => '<path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
  'calendar' => '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M10 16h4"/><path d="M12 14v4"/>',
  'users'    => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
  'chart'    => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
  'shield'   => '<path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/>',
  'bell'     => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
  'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/>',
];

/* Menú lateral: [páginas que lo marcan activo, página destino, texto, ícono, roles permitidos] */
$menu = [];
if ($user) {
  $rol = $user['rol'];
  $todos = ['Cliente','Subastador','Administrador','Auditor'];
  $menu[] = [['dashboard'], 'dashboard', 'Inicio', 'home', $todos];
  $menu[] = [['catalogo','detalle'], 'catalogo', 'Subastas', 'gavel', $todos];
  $menu[] = [['mis-pujas','pago'], 'mis-pujas', 'Mis pujas', 'pujas', ['Cliente']];
  $menu[] = [['articulos'], 'articulos', 'Artículos', 'box', ['Cliente','Subastador','Administrador']];
  $menu[] = [['programar'], 'programar', 'Programar subasta', 'calendar', ['Cliente','Subastador','Administrador']];
  $menuAdmin = [];
  $menuAdmin[] = [['usuarios'], 'usuarios', 'Usuarios', 'users', ['Administrador']];
  $menuAdmin[] = [['estadisticas'], 'estadisticas', 'Estadísticas', 'chart', ['Administrador']];
  $menuAdmin[] = [['auditoria'], 'auditoria', 'Auditoría', 'shield', ['Auditor','Administrador']];
}
$renderItem = function(array $it) use ($currentPage, $ic, $icons): string {
  $activo = in_array($currentPage, $it[0], true) ? ' active' : '';
  return '<a class="nav-link'.$activo.'" href="index.php?page='.$it[1].'">'.$ic($icons[$it[3]]).'<span>'.$it[2].'</span></a>';
};
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Guate-Subasta</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,600..800&family=DM+Sans:opsz,wght@9..40,400..700&display=swap">
<link rel="stylesheet" href="assets/css/styles.css?v=<?=time()?>">
</head>
<body>
<header>
  <a class="brand" href="index.php">
    <span class="brand-mark"><?=$ic($icons['gavel'])?></span>
    <span>Guate<span class="brand-dash">-</span><em>Subasta</em></span>
  </a>
  <nav>
  <?php if($user): ?>
    <?php $avisosPendientes=0; try { if (class_exists('AgendaSubastas')) $avisosPendientes=(new AgendaSubastas($db))->noLeidos((int)$user['id']); } catch(Throwable $ignorado) {} ?>
    <a class="bell-btn" href="index.php?page=avisos-subastas" title="Notificaciones de subastas" aria-label="Notificaciones de subastas">
      <?=$ic($icons['bell'])?>
      <?php if($avisosPendientes > 0): ?>
        <span class="bell-badge"><?=(int)$avisosPendientes?></span>
      <?php endif; ?>
    </a>
    <span class="user-pill">
      <span class="avatar"><?=e(mb_strtoupper(mb_substr($user['nombre'],0,1)))?></span>
      <span class="user-text"><b><?=e($user['nombre'])?></b><small><?=e($user['rol'])?></small></span>
    </span>
    <a class="logout-btn" href="index.php?page=logout"><?=$ic($icons['logout'])?><span>Cerrar sesión</span></a>
  <?php endif; ?>
  </nav>
</header>
<div class="shell">
<?php if($user): ?>
<aside>
  <?php foreach($menu as $it) { if (in_array($rol, $it[4], true)) echo $renderItem($it); } ?>
  <?php
    $visibles = array_filter($menuAdmin, fn($it) => in_array($rol, $it[4], true));
    if ($visibles): ?>
    <div class="nav-group">Administración</div>
    <?php foreach($visibles as $it) echo $renderItem($it); ?>
  <?php endif; ?>
</aside>
<?php endif; ?>
<main>
<?php if(!empty($_SESSION['flash'])): ?><div class="flash" role="status"><?=e($_SESSION['flash']); unset($_SESSION['flash']);?></div><?php endif;?>