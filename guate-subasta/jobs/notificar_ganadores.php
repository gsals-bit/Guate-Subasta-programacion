<?php
/**
 * Ejecutar con Programador de tareas de Windows, por ejemplo cada 5 minutos.
 * Requiere composer require phpmailer/phpmailer y config/correo.php.
 * Correo al ganador DESPUES del cierre, no se envia al resto de pujadores.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo CLI.'); }
date_default_timezone_set('America/Guatemala');
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../models/Subasta.php';

try {
    $db=Database::connect();
    (new Subasta($db))->actualizarEstados();
    // Unico por subasta: nunca se encola dos veces el mismo ganador.
    $db->exec("INSERT IGNORE INTO notificaciones_ganador(subasta_id, usuario_id, correo_destino)
        SELECT s.id, p.usuario_id, u.email
        FROM subastas s
        JOIN pujas p ON p.id=(
            SELECT p2.id FROM pujas p2
            WHERE p2.subasta_id=s.id AND p2.cancelada=0 AND p2.estado<>'cancelada'
            ORDER BY p2.monto DESC, p2.fecha ASC, p2.id ASC LIMIT 1
        )
        JOIN usuarios u ON u.id=p.usuario_id AND u.activo=1
        WHERE s.fecha_fin <= NOW() AND s.estado IN ('cerrada','finalizada')");

    $configRuta=__DIR__.'/../config/correo.php';
    $autoRuta=__DIR__.'/../vendor/autoload.php';
    if (!is_file($configRuta)) throw new RuntimeException('Falta config/correo.php. Copia correo.ejemplo.php y coloca las credenciales SMTP.');
    if (!is_file($autoRuta)) throw new RuntimeException('Falta vendor/autoload.php. Ejecuta composer require phpmailer/phpmailer desde la carpeta guate-subasta.');
    $cfg=require $configRuta;
    foreach (['host','usuario','clave','remitente'] as $campo) {
        if (empty($cfg[$campo]) || str_contains((string)$cfg[$campo],'TU_CORREO') || str_contains((string)$cfg[$campo],'CONTRASENA_DE_APLICACION')) {
            throw new RuntimeException('Completa el campo '.$campo.' del archivo config/correo.php.');
        }
    }
    require_once $autoRuta;
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) throw new RuntimeException('PHPMailer no instalado.');

    $db->exec("UPDATE notificaciones_ganador SET estado='error', ultimo_error='Intento previo interrumpido'
        WHERE estado='procesando' AND actualizado_en < DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $rows=$db->query("SELECT ng.id, ng.subasta_id, ng.usuario_id, ng.correo_destino,
            u.nombre, a.nombre AS articulo, p.monto
        FROM notificaciones_ganador ng
        JOIN usuarios u ON u.id=ng.usuario_id
        JOIN subastas s ON s.id=ng.subasta_id
        JOIN articulos a ON a.id=s.articulo_id
        JOIN pujas p ON p.id=(
            SELECT p2.id FROM pujas p2 WHERE p2.subasta_id=s.id
            AND p2.cancelada=0 AND p2.estado<>'cancelada'
            ORDER BY p2.monto DESC,p2.fecha ASC,p2.id ASC LIMIT 1
        )
        WHERE ng.estado IN ('pendiente','error') AND ng.intentos < 5
        ORDER BY ng.id LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);

    $enviados=0;$fallidos=0;
    foreach($rows as $fila){
        $lock=$db->prepare("UPDATE notificaciones_ganador SET estado='procesando',intentos=intentos+1,ultimo_error=NULL
             WHERE id=? AND estado IN('pendiente','error') AND intentos<5");
        $lock->execute([(int)$fila['id']]);
        if($lock->rowCount()!==1)continue;
        try {
            if (!filter_var($fila['correo_destino'], FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Correo del ganador no valido');
            $m=new \PHPMailer\PHPMailer\PHPMailer(true);
            $m->isSMTP();
            $m->Host=(string)$cfg['host'];
            $m->SMTPAuth=true;
            $m->Username=(string)$cfg['usuario'];
            $m->Password=(string)$cfg['clave'];
            $m->Port=(int)($cfg['port']??587);
            $m->SMTPSecure=($m->Port===465) ? \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS : \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $m->CharSet='UTF-8';
            $m->Timeout=15;
            $m->setFrom((string)$cfg['remitente'],(string)($cfg['nombre_remitente']??'Guate-Subasta'));
            $m->addAddress((string)$fila['correo_destino'],(string)$fila['nombre']);
            $m->Subject='Guate-Subasta: ganaste la subasta #'.$fila['subasta_id'];
            $nombre=htmlspecialchars((string)$fila['nombre'],ENT_QUOTES,'UTF-8');
            $articulo=htmlspecialchars((string)$fila['articulo'],ENT_QUOTES,'UTF-8');
            $cantidad='Q '.number_format((float)$fila['monto'],2);
            $html='<h2>¡Felicitaciones, '.$nombre.'!</h2><p>Tu oferta ganó la subasta <strong>#'.(int)$fila['subasta_id'].'</strong>.</p><p><strong>Artículo:</strong> '.$articulo.'</p><p><strong>Oferta ganadora:</strong> '.$cantidad.'</p><p>Para completar la compra, ingresa a Guate-Subasta e inicia sesión. El pago disponible es únicamente con tarjeta ficticia de demostración.</p>';
            $url=rtrim((string)($cfg['url_publica']??''),'/');
            if ($url!=='' && str_starts_with($url,'https://')) {
                $link=$url.'/index.php?page=pago&id='.(int)$fila['subasta_id'];
                $html.='<p><a href="'.htmlspecialchars($link,ENT_QUOTES,'UTF-8').'">Ver compra y pago</a></p>';
            }
            $html.='<p><small>Mensaje automático de un sistema de demostración. No se ha cobrado ningún importe real.</small></p>';
            $m->isHTML(true);$m->Body=$html;
            $m->AltBody='Ganaste la subasta #'.$fila['subasta_id'].' | Articulo: '.$fila['articulo'].' | Oferta: '.$cantidad.'. Ingresa a Guate-Subasta para completar una compra de demostracion.';
            $m->send();
            $db->prepare("UPDATE notificaciones_ganador SET estado='enviada', enviado_en=NOW(), ultimo_error=NULL WHERE id=?")
               ->execute([(int)$fila['id']]);
            $db->prepare('INSERT INTO auditoria(usuario_id,evento,detalle) VALUES(?,?,?)')
               ->execute([(int)$fila['usuario_id'],'EMAIL_GANADOR','Subasta #'.$fila['subasta_id'].' | correo de aviso de ganador enviado']);
            $enviados++;
        }catch(Throwable $e){
            $db->prepare("UPDATE notificaciones_ganador SET estado='error',ultimo_error=? WHERE id=?")
               ->execute([substr($e->getMessage(),0,255),(int)$fila['id']]);
            $fallidos++;
        }
    }
    echo "Finalizado. Correos enviados: $enviados, fallidos: $fallidos.\n";
} catch(Throwable $e) {
    fwrite(STDERR,'No se pudo procesar la notificacion: '.$e->getMessage()."\n");
    exit(1);
}
