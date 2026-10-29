<?php
date_default_timezone_set('America/Guatemala');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Forzar al navegador a solicitar siempre la información más reciente (prevenir caché)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once __DIR__.'/config/database.php';
require_once __DIR__.'/config/helpers.php';

foreach (glob(__DIR__.'/models/*.php') as $f) require_once $f;
foreach (glob(__DIR__.'/controllers/*.php') as $f) require_once $f;

$db = Database::connect();
// Revalidar la cuenta en cada solicitud: bloquea usuarios desactivados y roles antiguos.
if (!empty($_SESSION['user']['id'])) {
    $q = $db->prepare("SELECT u.id,u.nombre,u.email,r.nombre AS rol FROM usuarios u JOIN roles r ON r.id=u.rol_id WHERE u.id=? AND u.activo=1");
    $q->execute([(int)$_SESSION['user']['id']]);
    $actual = $q->fetch();
    if (!$actual) {
        unset($_SESSION['user']);
    } else {
        $_SESSION['user'] = $actual;
    }
}


// Función auxiliar para registrar eventos en la bitácora de auditoría
$registrarAuditoria = function(string$evento, string $detalle) use ($db): void {
    if (!empty($_SESSION['user']['id'])) {
        $q =$db->prepare("INSERT INTO auditoria(usuario_id, evento, detalle) VALUES(?, ?, ?)");
        $q->execute([(int)$_SESSION['user']['id'], $evento,$detalle]);
    }
};

// Modulos adicionales: reportes, recibos QR, avisos y agenda de 30 minutos.
// Se cargan ANTES de las rutas originales para procesar programacion sin conflicto.
require_once __DIR__ . '/extras/rutas.php';

$action =$_GET['action'] ?? '';
if ($action === 'login') (new AuthController($db))->login();
if ($action === 'registro') (new AuthController($db))->registro();
if ($action === 'pujar') (new PujaController($db))->crear();
if ($action === 'cancelar_puja') {
    require_role(['Cliente']); verify_csrf();
    [$ok,$msg]=(new Puja($db))->cancelar((int)($_POST['puja_id']??0),(int)$_SESSION['user']['id']);
    $_SESSION['flash']=$msg; redirect('index.php?page=mis-pujas');
}
if ($action === 'pagar_demo') {
    require_role(['Cliente']); verify_csrf();
    $sid=(int)($_POST['subasta_id']??0);
    [$ok,$msg]=(new PagoDemo($db))->pagar($sid,(int)$_SESSION['user']['id'], [
        'numero' => trim((string)($_POST['demo_numero'] ?? '')),
        'vencimiento' => trim((string)($_POST['demo_vencimiento'] ?? '')),
        'cvv' => trim((string)($_POST['demo_cvv'] ?? '')),
        'acepta' => ($_POST['demo_confirmacion'] ?? '') === '1',
    ]);
    $_SESSION['flash']=$msg; redirect('index.php?page=pago&id='.$sid);
}
// Antigua compra con tokens deshabilitada: ahora solo tarjeta ficticia.
if ($action === 'comprar_tokens') {
    require_role(['Cliente']); verify_csrf();
    $sid = (int)($_POST['subasta_id'] ?? 0);
    $_SESSION['flash'] = 'Las compras ahora se registran exclusivamente con tarjeta ficticia.';
    redirect('index.php?page=detalle&id='.$sid);
}


// Permitimos que Cliente (Pujador), Subastador y Administrador ingresen productos
if ($action === 'articulo') {
    require_role(['Cliente','Subastador','Administrador']);
    verify_csrf();
    $nombre = trim($_POST['nombre']);
    $categoria = trim($_POST['categoria']);
    $precioBase = (float)$_POST['precio_base'];
    $descripcion = mb_substr(trim($_POST['descripcion']), 0, 500); // Doble validación del límite

    // Procesamiento optimizado de imágenes
    $imagenesSubidas = [];
    $directorioSubidas = __DIR__ . '/uploads/';
    
    // Crea el directorio automáticamente si no existe
    if (!is_dir($directorioSubidas)) {
        mkdir($directorioSubidas, 0777, true);
    }

    if (!empty($_FILES['imagenes']['name'][0])) {
        $totalArchivos = count($_FILES['imagenes']['name']);
        $totalPermitido = min($totalArchivos, 10); // Máximo de 10 imágenes por artículo

        for ($i = 0; $i < $totalPermitido; $i++) {
            $tmpName = $_FILES['imagenes']['tmp_name'][$i];
            $error = $_FILES['imagenes']['error'][$i];
            $size = $_FILES['imagenes']['size'][$i];
            
            // Validación de carga sin errores y límite de 2MB
            if ($error === UPLOAD_ERR_OK && $size <= 2097152) {
                $extension = strtolower(pathinfo($_FILES['imagenes']['name'][$i], PATHINFO_EXTENSION));
                // Validación de formato seguro
                if (in_array($extension, ['jpg', 'jpeg', 'png', 'webp'])) {
                    // Genera un nombre único para evitar sobreescribir imágenes con el mismo nombre
                    $nuevoNombre = uniqid('art_', true) . '.' . $extension;
                    if (move_uploaded_file($tmpName, $directorioSubidas . $nuevoNombre)) {
                        $imagenesSubidas[] = $nuevoNombre;
                    }
                }
            }
        }
    }

    // Convertimos el arreglo a JSON para guardarlo estructurado en la base de datos
    $jsonImagenes = json_encode($imagenesSubidas);

    $ok = (new Articulo($db))->create([
        'nombre' => $nombre,
        'categoria' => $categoria,
        'descripcion' => $descripcion,
        'estado_fisico' => $_POST['estado_fisico'],
        'precio_base' => $precioBase,
        'modelo' => trim($_POST['modelo']),
        'imagen' => $jsonImagenes,
        'creado_por' => $_SESSION['user']['id'],
        'ubicacion_nombre' => mb_substr(trim($_POST['ubicacion_nombre'] ?? ''),0,200),
        'ubicacion_lat' => filter_var($_POST['ubicacion_lat'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE),
        'ubicacion_lng' => filter_var($_POST['ubicacion_lng'] ?? null, FILTER_VALIDATE_FLOAT, FILTER_NULL_ON_FAILURE)
    ]);

    if ($ok) {
        $registrarAuditoria('CREAR_ARTICULO', "Artículo: {$nombre} | Categoría: {$categoria} | Base: Q " . number_format($precioBase, 2));
        $_SESSION['flash'] = 'Artículo registrado exitosamente con sus imágenes.';
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
    [$ok,$msg] = (new Subasta($db))->eliminarCerrada($subastaId, (int)$_SESSION['user']['id'], $_SESSION['user']['rol'] === 'Administrador');
    if ($ok) {$registrarAuditoria('ELIMINAR_SUBASTA', "Subasta cerrada eliminada ID #{$subastaId}");
    }
    $_SESSION['flash'] =$msg;
    redirect('index.php?page=catalogo');
}

// Acción para eliminar artículo
if ($action === 'eliminar_articulo') {
    require_role(['Cliente', 'Subastador', 'Administrador']);
    verify_csrf();
    $id = (int)$_POST['id'];

    $artModel = new Articulo($db);
    $articulo = $artModel->find($id);

    // 1. Validar que el artículo exista
    if (!$articulo) {
        $_SESSION['flash'] = 'El artículo no existe o ya fue eliminado.';
    } 
    // 2. Validar que el usuario sea el dueño (o sea un Administrador)
    elseif ($_SESSION['user']['rol'] !== 'Administrador' && (int)$articulo['creado_por'] !== (int)$_SESSION['user']['id']) {
        $_SESSION['flash'] = 'Acceso denegado: Solo el creador del artículo puede eliminarlo.';
    } 
    // 3. Proceder con la eliminación segura
    else {
        $resultado = $artModel->eliminar($id);
        $_SESSION['flash'] = $resultado[1]; // Mensaje real devuelto por el modelo
        
        if ($resultado[0] === true) {
            $registrarAuditoria('ELIMINAR_ARTICULO', "Artículo eliminado: " . $articulo['nombre']);
        }
    }
    redirect('index.php?page=articulos');
}
// Administrador: archivar articulos, sin borrar pujas ni pagos.
if ($action === 'archivar_articulos') {
    require_role(['Administrador']); verify_csrf();
    $ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['ids'] ?? [])), fn($x) => $x > 0)));
    if (!$ids || count($ids)>500) { $_SESSION['flash']='Selecciona entre 1 y 500 artículos.'; }
    else {
        $marks=implode(',', array_fill(0,count($ids),'?'));
        $q=$db->prepare("UPDATE articulos SET archivado=1 WHERE id IN ($marks) AND archivado=0");
        $q->execute($ids);
        $registrarAuditoria('ARCHIVAR_ARTICULOS', 'IDs: '.implode(',',$ids).' | Archivados: '.$q->rowCount());
        $_SESSION['flash']='Artículos archivados: '.$q->rowCount().'. Historial conservado.';
    }
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
        $q = $db->prepare("UPDATE usuarios SET rol_id = (SELECT id FROM roles WHERE nombre = ?), activo = ? WHERE id = ?");
        $q->execute([$nuevoRol, $nuevoActivo, $uid]);
        $estadoTxt = $nuevoActivo ? 'Activo' : 'Inactivo';
        $registrarAuditoria('ACTUALIZAR_USUARIO', "Usuario ID #{$uid} actualizado a Rol: {$nuevoRol} | Estado: {$estadoTxt}");
        $_SESSION['flash'] = 'Permisos del usuario actualizados correctamente.';
    } else {
        $_SESSION['flash'] = 'No se pudo modificar el usuario seleccionado.';
    }
    redirect('index.php?page=usuarios');
}

// Eliminación lógica: preserva historial de pujas, subastas y auditoría.
if ($action === 'eliminar_usuario') {
    require_role(['Administrador']); verify_csrf();
    $uid = (int)($_POST['usuario_id'] ?? 0);
    if ($uid <= 0 || $uid === (int)$_SESSION['user']['id']) {
        $_SESSION['flash'] = 'No puedes eliminar tu propia cuenta.';
    } else {
        $q = $db->prepare('UPDATE usuarios SET activo=0 WHERE id=?');
        $q->execute([$uid]);
        $registrarAuditoria('DESACTIVAR_USUARIO', "Cuenta ID #{$uid} desactivada por administrador");
        $_SESSION['flash'] = 'Cuenta desactivada. El usuario ya no puede iniciar sesión.';
    }
    redirect('index.php?page=usuarios');
}

$page = $_GET['page'] ?? (empty($_SESSION['user']) ? 'login' : 'dashboard');
if ($page === 'logout') (new AuthController($db))->logout();

// NUEVO: al abrir el detalle de una subasta, sus avisos pasan a leídos.
if ($page === 'detalle' && !empty($_SESSION['user']['id']) && class_exists('AgendaSubastas')) {
    try {
        (new AgendaSubastas($db))->marcarLeidosSubasta((int)$_SESSION['user']['id'], (int)($_GET['id'] ?? 0));
    } catch (Throwable $ignorado) {}
}

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
        $subastas = array_values(array_filter((new Subasta($db))->activas(), fn($x) => empty($x['archivado'])));
        require __DIR__.'/views/cliente/catalogo.php';
        break;
    case 'detalle':
        require_login();
        $subasta = (new Subasta($db))->find((int)($_GET['id'] ?? 0));
        if (!$subasta || ((int)($subasta['archivado'] ?? 0) === 1 && $_SESSION['user']['rol'] !== 'Administrador')) { echo 'Subasta no disponible.'; break; }$historial = (new Puja($db))->historialSubasta($subasta['id']);
        require __DIR__.'/views/cliente/detalle.php';
        break;
    case 'pago':
        require_role(['Cliente']);
        $sid=(int)($_GET['id']??0);
        $pago=(new PagoDemo($db))->detalle($sid,(int)$_SESSION['user']['id']);
        require __DIR__.'/views/cliente/pago.php';
        break;
    case 'estadisticas':
        require_role(['Administrador']);
        (new Subasta($db))->actualizarEstados();
        require __DIR__.'/views/admin/estadisticas.php';
        break;
    case 'mis-pujas':
        require_role(['Cliente']);
        $pujas = (new Puja($db))->historialUsuario($_SESSION['user']['id']);
        require __DIR__.'/views/cliente/mis_pujas.php';
        break;
    case 'articulos':
        require_role(['Cliente','Subastador','Administrador']);
        $articulos = (new Articulo($db))->all($_SESSION['user']['rol'] === 'Administrador' ? null : (int)$_SESSION['user']['id']);
        require __DIR__.'/views/subastador/articulos.php';
        break;
    case 'programar':
        require_role(['Cliente','Subastador','Administrador']);
        $articulos = (new Articulo($db))->disponiblesParaSubasta((int)$_SESSION['user']['id']);
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
            SELECT a.*, u.nombre AS usuario, r.nombre AS rol_usuario
            FROM auditoria a
            LEFT JOIN usuarios u ON u.id = a.usuario_id
            LEFT JOIN roles r ON r.id=u.rol_id
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