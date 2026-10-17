<?php
session_start();
require __DIR__.'/config/database.php';
require __DIR__.'/config/helpers.php';
foreach (glob(__DIR__.'/models/*.php') as $f) require$f;
foreach (glob(__DIR__.'/controllers/*.php') as $f) require$f;
$db = Database::connect();

// Función auxiliar para registrar eventos en la bitácora de auditoría
$registrarAuditoria = function(string$evento, string $detalle) use ($db): void {
    if (!empty($_SESSION['user']['id'])) {
        $q =$db->prepare("INSERT INTO auditoria(usuario_id, evento, detalle) VALUES(?, ?, ?)");
        $q->execute([(int)$_SESSION['user']['id'], $evento,$detalle]);
    }
};

$action =$_GET['action'] ?? '';
if ($action === 'login') (new AuthController($db))->login();
if ($action === 'registro') (new AuthController($db))->registro();
if ($action === 'pujar') (new PujaController($db))->crear();

// Permitimos que Cliente (Pujador), Subastador y Administrador ingresen productos
if ($action === 'articulo') {
    require_role(['Cliente','Subastador','Administrador']);
    verify_csrf();
    $nombre = trim($_POST['nombre']);
    $categoria = trim($_POST['categoria']);
    $precioBase = (float)$_POST['precio_base'];

    $ok = (new Articulo($db))->create([
        'nombre' => $nombre,
        'categoria' => $categoria,
        'descripcion' => trim($_POST['descripcion']),
        'estado_fisico' => $_POST['estado_fisico'],
        'precio_base' => $precioBase,
        'certificado' => trim($_POST['certificado']),
        'creado_por' => $_SESSION['user']['id']
    ]);

    if ($ok) {$registrarAuditoria('CREAR_ARTICULO', "Artículo: {$nombre} \vert{} Categoría: {$categoria} | Base: Q " . number_format($precioBase, 2));$_SESSION['flash'] = 'Artículo registrado.';
    } else {
        $_SESSION['flash'] = 'No se pudo registrar el artículo.';
    }
    redirect('index.php?page=articulos');
}

// Permitimos programar la subasta validando fechas y disponibilidad
if ($action === 'programar') {
    require_role(['Cliente','Subastador','Administrador']);
    verify_csrf();
    $articuloId = (int)$_POST['articulo_id'];
    $inicio = str_replace('T', ' ', trim($_POST['inicio']));
    $fin = str_replace('T', ' ', trim($_POST['fin']));
    $incremento = (float)$_POST['incremento'];

    $creada = (new Subasta($db))->crear($articuloId,$inicio, $fin,$incremento);
    if ($creada) {$registrarAuditoria('PROGRAMAR_SUBASTA', "Artículo ID #{$articuloId} | Inicio: {$inicio} \vert{} Fin: {$fin} | Incremento: Q " . number_format($incremento, 2));$_SESSION['flash'] = 'Subasta programada correctamente.';
        redirect('index.php?page=catalogo');
    } else {
        $_SESSION['flash'] = 'Error al programar: verifica que la fecha de fin sea posterior a la de inicio y que el artículo esté disponible.';
        redirect('index.php?page=programar');
    }
}

// Acción para eliminar subasta cuando ya está cerrada
if ($action === 'eliminar_subasta') {
    require_role(['Cliente','Subastador','Administrador']);
    verify_csrf();
    $subastaId = (int)($_POST['subasta_id'] ?? $_GET['id'] ?? 0);
    [$ok,$msg] = (new Subasta($db))->eliminarCerrada($subastaId);
    if ($ok) {$registrarAuditoria('ELIMINAR_SUBASTA', "Subasta cerrada eliminada ID #{$subastaId}");
    }
    $_SESSION['flash'] =$msg;
    redirect('index.php?page=catalogo');
}

// Acción para eliminar artículo
if ($action === 'eliminar_articulo') {
    require_role(['Cliente','Subastador','Administrador']);
    verify_csrf();
    $articuloId = (int)($_POST['articulo_id'] ?? $_GET['id'] ?? 0);
    [$ok,$msg] = (new Articulo($db))->eliminar($articuloId);
    if ($ok) {$registrarAuditoria('ELIMINAR_ARTICULO', "Artículo eliminado ID #{$articuloId}");
    }
    $_SESSION['flash'] =$msg;
    redirect('index.php?page=articulos');
}
// Acción para que el Administrador actualice rol y estado de un usuario
if ($action === 'actualizar_usuario') {
    require_role(['Administrador']);
    verify_csrf();
    $uid = (int)($_POST['usuario_id'] ?? 0);
    $nuevoRol = trim($_POST['rol'] ?? 'Cliente');
    $nuevoActivo = (int)($_POST['activo'] ?? 1) === 1 ? 1 : 0;
    $rolesValidos = ['Cliente', 'Subastador', 'Auditor', 'Administrador'];

    if ($uid > 0 && $uid !== (int)$_SESSION['user']['id'] && in_array($nuevoRol, $rolesValidos, true)) {
        $q = $db->prepare("UPDATE usuarios SET rol = ?, activo = ? WHERE id = ?");
        $q->execute([$nuevoRol, $nuevoActivo, $uid]);
        $estadoTxt = $nuevoActivo ? 'Activo' : 'Inactivo';
        $registrarAuditoria('ACTUALIZAR_USUARIO', "Usuario ID #{$uid} actualizado a Rol: {$nuevoRol} | Estado: {$estadoTxt}");
        $_SESSION['flash'] = 'Permisos del usuario actualizados correctamente.';
    } else {
        $_SESSION['flash'] = 'No se pudo modificar el usuario seleccionado.';
    }
    redirect('index.php?page=usuarios');
}
$page = $_GET['page'] ?? (empty($_SESSION['user']) ? 'login' : 'dashboard');
if ($page === 'logout') (new AuthController($db))->logout();

require __DIR__.'/views/layouts/header.php';
switch ($page) {
    case 'login':
        require __DIR__.'/views/auth/login.php';
        break;
    case 'registro':
        require __DIR__.'/views/auth/registro.php';
        break;
    case 'catalogo':
        require_login();
        $subastas = (new Subasta($db))->activas();
        require __DIR__.'/views/cliente/catalogo.php';
        break;
    case 'detalle':
        require_login();
        $subasta = (new Subasta($db))->find((int)($_GET['id'] ?? 0));
        if (!$subasta) { echo 'Subasta no encontrada.'; break; }$historial = (new Puja($db))->historialSubasta($subasta['id']);
        require __DIR__.'/views/cliente/detalle.php';
        break;
    case 'mis-pujas':
        require_role(['Cliente']);
        $pujas = (new Puja($db))->historialUsuario($_SESSION['user']['id']);
        require __DIR__.'/views/cliente/mis_pujas.php';
        break;
    case 'articulos':
        require_role(['Cliente','Subastador','Administrador']);
        $articulos = (new Articulo($db))->all();
        require __DIR__.'/views/subastador/articulos.php';
        break;
    case 'programar':
        require_role(['Cliente','Subastador','Administrador']);
        $articulos = (new Articulo($db))->disponiblesParaSubasta();
        require __DIR__.'/views/subastador/programar.php';
        break;
    case 'usuarios':
        require_role(['Administrador']);
        $usuarios = (new Usuario($db))->all();
        require __DIR__.'/views/admin/usuarios.php';
        break;
    case 'auditoria':
        require_role(['Auditor','Administrador']);
        $eventos =$db->query("
            SELECT a.*, u.nombre AS usuario, u.rol AS rol_usuario
            FROM auditoria a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            ORDER BY a.id DESC
            LIMIT 300
        ")->fetchAll();
        require __DIR__.'/views/auditor/auditoria.php';
        break;
   default:
        require_login();
        (new Subasta($db))->actualizarEstados();
        $stats = [
            'usuarios' => $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
            'articulos' => $db->query("SELECT COUNT(*) FROM articulos")->fetchColumn(),
            'subastas' => $db->query("SELECT COUNT(*) FROM subastas")->fetchColumn(),
            'pujas' => $db->query("SELECT COUNT(*) FROM pujas")->fetchColumn()
        ];
        require __DIR__ . '/views/admin/dashboard.php';
        break;
}
require __DIR__ . '/views/layouts/footer.php';