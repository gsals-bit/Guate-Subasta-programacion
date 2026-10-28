<?php 
$user = $_SESSION['user'] ?? null; 
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Guate-Subasta</title><link rel="stylesheet" href="assets/css/styles.css?v=<?=time()?>"></head><body>
<header>
  <a class="brand" href="index.php">⚖ Guate-Subasta</a>
  <nav>
    <?php if($user): ?>
      <?php $avisosPendientes=0; try { if (class_exists('AgendaSubastas')) $avisosPendientes=(new AgendaSubastas($db))->noLeidos((int)$user['id']); } catch(Throwable $ignorado) {} ?>
      <a class="bell-btn" href="index.php?page=avisos-subastas" title="Notificaciones de subastas">
        🔔
        <?php if($avisosPendientes > 0): ?>
          <span class="bell-badge"><?=(int)$avisosPendientes?></span>
        <?php endif; ?>
      </a>
      <span class="user-pill">👤 <?=e($user['nombre'])?> · <?=e($user['rol'])?></span>
      <a class="logout-btn" href="index.php?page=logout">Cerrar sesión</a>
    <?php endif; ?>
  </nav>
</header>
<div class="shell">
<?php if($user): ?><aside>
  <a class="<?=$currentPage==='dashboard'?'active':''?>" href="index.php?page=dashboard">🏠 Inicio</a>
  <a class="<?=in_array($currentPage,['catalogo','detalle'])?'active':''?>" href="index.php?page=catalogo">🔨 Subastas</a>
  <?php if($user['rol']==='Cliente'): ?>
    <a class="<?=$currentPage==='mis-pujas'?'active':''?>" href="index.php?page=mis-pujas">💰 Mis pujas</a>
  <?php endif; ?>
  <?php if(in_array($user['rol'],['Cliente','Subastador','Administrador'])): ?>
    <a class="<?=$currentPage==='articulos'?'active':''?>" href="index.php?page=articulos">📦 Artículos</a>
    <a class="<?=$currentPage==='programar'?'active':''?>" href="index.php?page=programar">⏱️ Programar subasta</a>
  <?php endif; ?>
  <?php if($user['rol']==='Administrador'): ?>
    <a class="<?=$currentPage==='usuarios'?'active':''?>" href="index.php?page=usuarios">👥 Usuarios</a>
    <a class="<?=$currentPage==='estadisticas'?'active':''?>" href="index.php?page=estadisticas">📊 Estadísticas</a>
  <?php endif; ?>
  <?php if(in_array($user['rol'],['Auditor','Administrador'])): ?>
    <a class="<?=$currentPage==='auditoria'?'active':''?>" href="index.php?page=auditoria">🛡️ Auditoría</a>
  <?php endif; ?>
</aside><?php endif;?><main>
<?php if(!empty($_SESSION['flash'])): ?><div class="flash"><?=e($_SESSION['flash']); unset($_SESSION['flash']);?></div><?php endif;?>