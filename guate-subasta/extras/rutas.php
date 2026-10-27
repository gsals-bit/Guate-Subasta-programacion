<?php
/** Incluir una sola vez despues de crear $db y antes de enviar HTML en index.php. */
if (($_GET['action'] ?? '') === 'exportar_estadisticas') {
    require_role(['Administrador']);
    $filas=ReporteEstadisticas::filas($db);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="guate_subasta_estadisticas_'.date('Ymd').'.csv"');
    header('Cache-Control: no-store');
    $out=fopen('php://output','wb');
    fwrite($out,"\xEF\xBB\xBF");
    fputcsv($out,['Subasta','Articulo','Estado','Monto actual (Q)','Pujas activas','Pujas canceladas','Pagos demo (Q)']);
    foreach($filas as $f) {
        // Proteccion contra formulas cuando CSV se abre en Excel.
        $nombre=(string)$f['articulo'];
        if (preg_match('/^[=+\-@\t\r]/u', $nombre)) $nombre="'".$nombre;
        fputcsv($out,[(int)$f['id'],$nombre,$f['estado'],(string)$f['monto_actual'],(int)$f['total_pujas'],(int)$f['canceladas'],(string)$f['cobrado_demo']]);
    }
    fclose($out);
    exit;
}

if (($_GET['page']??'') === 'factura') {
    require_role(['Cliente','Administrador']);
    $sid=(int)($_GET['id']??0);
    $factura=(new FacturaDemo($db))->obtener($sid,(int)$_SESSION['user']['id'],$_SESSION['user']['rol']==='Administrador');
    if (!$factura) http_response_code(404);
    require __DIR__.'/../views/layouts/header.php';
    require __DIR__.'/../views/cliente/factura.php';
    require __DIR__.'/../views/layouts/footer.php';
    exit;
}

if (($_GET['page']??'') === 'verificar_factura') {
    // Publico por referencia, se muestran solo datos de la compra ficticia.
    $referencia=(string)($_GET['ref']??'');
    $comprobante=(new FacturaDemo($db))->verificar($referencia);
    if (!$comprobante) http_response_code(404);
    require __DIR__.'/../views/layouts/header.php';
    require __DIR__.'/../views/cliente/verificar_factura.php';
    require __DIR__.'/../views/layouts/footer.php';
    exit;
}

// Horarios de 30 minutos + avisos de nuevas subastas (módulo adicional).
require_once __DIR__.'/agenda_rutas.php';
