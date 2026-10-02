<?php
session_start();
require __DIR__.'/config/database.php'; require __DIR__.'/config/helpers.php';
foreach (glob(__DIR__.'/models/*.php') as $f) require $f;
foreach (glob(__DIR__.'/controllers/*.php') as $f) require $f;
$db=Database::connect();

$action=$_GET['action']??'';
if($action==='login') (new AuthController($db))->login();
if($action==='registro') (new AuthController($db))->registro();
if($action==='pujar') (new PujaController($db))->crear();
if($action==='articulo'){
    require_role(['Subastador','Administrador']); verify_csrf();
    (new Articulo($db))->create([
      'nombre'=>trim($_POST['nombre']),'categoria'=>trim($_POST['categoria']),'descripcion'=>trim($_POST['descripcion']),
      'estado_fisico'=>$_POST['estado_fisico'],'precio_base'=>(float)$_POST['precio_base'],
      'certificado'=>trim($_POST['certificado']),'creado_por'=>$_SESSION['user']['id']
    ]); $_SESSION['flash']='Artículo registrado.'; redirect('index.php?page=articulos');
}
if($action==='programar'){
    require_role(['Subastador','Administrador']); verify_csrf();
    (new Subasta($db))->crear((int)$_POST['articulo_id'],str_replace('T',' ',$_POST['inicio']),str_replace('T',' ',$_POST['fin']),(float)$_POST['incremento']);
    $_SESSION['flash']='Subasta programada.'; redirect('index.php?page=catalogo');
}
$page=$_GET['page']??(empty($_SESSION['user'])?'login':'dashboard');
if($page==='logout') (new AuthController($db))->logout();

require __DIR__.'/views/layouts/header.php';
switch($page){
 case 'login': require __DIR__.'/views/auth/login.php'; break;
 case 'registro': require __DIR__.'/views/auth/registro.php'; break;
 case 'catalogo':
   require_login(); $subastas=(new Subasta($db))->activas(); require __DIR__.'/views/cliente/catalogo.php'; break;
 case 'detalle':
   require_login(); $subasta=(new Subasta($db))->find((int)($_GET['id']??0)); if(!$subasta){echo 'Subasta no encontrada.';break;}
   $historial=(new Puja($db))->historialSubasta($subasta['id']); require __DIR__.'/views/cliente/detalle.php'; break;
 case 'mis-pujas':
   require_role(['Cliente']); $pujas=(new Puja($db))->historialUsuario($_SESSION['user']['id']); require __DIR__.'/views/cliente/mis_pujas.php'; break;
 case 'articulos':
   require_role(['Subastador','Administrador']); $articulos=(new Articulo($db))->all(); require __DIR__.'/views/subastador/articulos.php'; break;
 case 'programar':
   require_role(['Subastador','Administrador']); $articulos=(new Articulo($db))->all(); require __DIR__.'/views/subastador/programar.php'; break;
 case 'usuarios':
   require_role(['Administrador']); $usuarios=(new Usuario($db))->all(); require __DIR__.'/views/admin/usuarios.php'; break;
 case 'auditoria':
   require_role(['Auditor','Administrador']); $eventos=$db->query("SELECT a.*,u.nombre usuario FROM auditoria a LEFT JOIN usuarios u ON u.id=a.usuario_id ORDER BY a.id DESC LIMIT 300")->fetchAll();
   require __DIR__.'/views/auditor/auditoria.php'; break;
 default:
   require_login(); $stats=[
    'usuarios'=>$db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
    'articulos'=>$db->query("SELECT COUNT(*) FROM articulos")->fetchColumn(),
    'subastas'=>$db->query("SELECT COUNT(*) FROM subastas")->fetchColumn(),
    'pujas'=>$db->query("SELECT COUNT(*) FROM pujas")->fetchColumn()
   ]; require __DIR__.'/views/admin/dashboard.php';
}
require __DIR__.'/views/layouts/footer.php';
