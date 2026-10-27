<?php
/** Debe incluirse antes de $action=... en index.php, por medio de extras/rutas.php. */
$agenda = new AgendaSubastas($db);
$accionAgenda=(string)($_GET['action'] ?? '');
$paginaAgenda=(string)($_GET['page'] ?? '');

if ($accionAgenda === 'programar') {
    require_role(['Cliente','Subastador','Administrador']);
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Metodo no permitido'); }
    verify_csrf();
    [$bien,$mensaje]=$agenda->programar((int)($_POST['articulo_id']??0), (int)$_SESSION['user']['id'],
                                      (string)($_POST['inicio']??''), (float)($_POST['incremento']??0), $_POST['duracion_minutos'] ?? 30);
    $_SESSION['flash']=$mensaje;
    redirect('index.php?page='.($bien?'catalogo':'programar'));
}

if ($accionAgenda === 'editar_horario') {
    require_role(['Cliente','Subastador','Administrador']);
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Metodo no permitido'); }
    verify_csrf();
    $sid=(int)($_POST['subasta_id']??0);
    [$bien,$mensaje]=$agenda->cambiar($sid,(int)$_SESSION['user']['id'],(string)($_POST['fecha_hora']??''), $_POST['duracion_minutos'] ?? null);
    $_SESSION['flash']=$mensaje;
    redirect('index.php?page='.($bien?'catalogo':'editar-horario&id='.$sid));
}

if ($accionAgenda === 'marcar_avisos') {
    require_login();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Metodo no permitido'); }
    verify_csrf();
    $agenda->marcarLeidos((int)$_SESSION['user']['id']);
    $_SESSION['flash']='Avisos marcados como leídos.';
    redirect('index.php?page=avisos-subastas');
}

if ($accionAgenda === 'preferencias_avisos') {
    require_login();
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Metodo no permitido'); }
    verify_csrf();
    $habilitado = isset($_POST['correo_habilitado']) && $_POST['correo_habilitado']==='1';
    $agenda->configurarCorreo((int)$_SESSION['user']['id'],$habilitado);
    $_SESSION['flash']=$habilitado?'Activaste los correos para futuras subastas.':'Se desactivaron los correos de nuevas subastas.';
    redirect('index.php?page=avisos-subastas');
}

if ($paginaAgenda === 'editar-horario') {
    require_role(['Cliente','Subastador','Administrador']);
    $subastaAgenda=$agenda->detallePropietario((int)($_GET['id']??0),(int)$_SESSION['user']['id']);
    if (!$subastaAgenda) { http_response_code(404); $_SESSION['flash']='No se encontró una subasta que te pertenezca.'; redirect('index.php?page=catalogo'); }
    require __DIR__.'/../views/layouts/header.php';
    require __DIR__.'/../views/subastador/editar_horario.php';
    require __DIR__.'/../views/layouts/footer.php';
    exit;
}

if ($paginaAgenda === 'avisos-subastas') {
    require_login();
    $avisosAgenda=$agenda->listado((int)$_SESSION['user']['id']);
    $correoAgenda=$agenda->correoActivo((int)$_SESSION['user']['id']);
    require __DIR__.'/../views/layouts/header.php';
    require __DIR__.'/../views/cliente/avisos_subastas.php';
    require __DIR__.'/../views/layouts/footer.php';
    exit;
}
