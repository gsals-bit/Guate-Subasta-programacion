<?php $user=$_SESSION['user']??null; ?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Guate-Subasta</title><link rel="stylesheet" href="assets/css/styles.css"></head><body>
<header><a class="brand" href="index.php">⚖ Guate-Subasta</a>
<nav><?php if($user): ?><span><?=e($user['nombre'])?> · <?=e($user['rol'])?></span><a href="index.php?page=logout">Cerrar sesión</a><?php endif;?></nav></header>
<div class="shell">
<?php if($user): ?><aside>
<a href="index.php?page=dashboard">Inicio</a>
<a href="index.php?page=catalogo">Subastas</a>
<?php if($user['rol']==='Cliente'): ?><a href="index.php?page=mis-pujas">Mis pujas</a><?php endif;?>
<?php if(in_array($user['rol'],['Subastador','Administrador'])): ?><a href="index.php?page=articulos">Artículos</a><a href="index.php?page=programar">Programar subasta</a><?php endif;?>
<?php if($user['rol']==='Administrador'): ?><a href="index.php?page=usuarios">Usuarios</a><?php endif;?>
<?php if(in_array($user['rol'],['Auditor','Administrador'])): ?><a href="index.php?page=auditoria">Auditoría</a><?php endif;?>
</aside><?php endif;?><main>
<?php if(!empty($_SESSION['flash'])): ?><div class="flash"><?=e($_SESSION['flash']); unset($_SESSION['flash']);?></div><?php endif;?>
