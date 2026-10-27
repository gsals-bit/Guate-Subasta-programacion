<?php
/** Ejecutar SOLO desde CLI con PHP de XAMPP, después de activar SMTP y consentimiento en la app. */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('Solo CLI'); }
date_default_timezone_set('America/Guatemala');
require_once __DIR__.'/../config/database.php';

try {
    $db=Database::connect();
    $configArchivo=__DIR__.'/../config/correo.php';
    $composer=__DIR__.'/../vendor/autoload.php';
    if (!is_file($configArchivo) || !is_file($composer)) {
        throw new RuntimeException('SMTP no configurado: instala PHPMailer y crea config/correo.php.');
    }
    $cfg=require $configArchivo;
    foreach (['host','usuario','clave','remitente'] as $campo) {
        if (empty($cfg[$campo]) || str_contains((string)$cfg[$campo],'TU_CORREO') || str_contains((string)$cfg[$campo],'CONTRASENA_DE_APLICACION')) {
            throw new RuntimeException('Campo incompleto en config/correo.php: '.$campo);
        }
    }
    require_once $composer;
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) throw new RuntimeException('Falta PHPMailer.');
    // Recuperar interrupciones accidentales.
    $db->exec("UPDATE avisos_subastas_correos SET estado='error',ultimo_error='Interrumpido'
       WHERE estado='procesando' AND actualizado_en < DATE_SUB(NOW(),INTERVAL 15 MINUTE)");
    $pendientes=$db->query("SELECT ac.id, ac.correo_destino, u.nombre, v.titulo, v.mensaje, v.subasta_id,
       pr.correo_habilitado
       FROM avisos_subastas_correos ac
       JOIN avisos_subastas v ON v.id=ac.aviso_id
       JOIN subastas s ON s.id=v.subasta_id AND s.fecha_fin>NOW()
       JOIN usuarios u ON u.id=ac.usuario_id AND u.activo=1
       JOIN preferencias_avisos_subastas pr ON pr.usuario_id=u.id AND pr.correo_habilitado=1
       WHERE ac.estado IN ('pendiente','error') AND ac.intentos<5
       ORDER BY ac.id LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    $enviados=0;$errores=0;
    foreach ($pendientes as $r) {
        $q=$db->prepare("UPDATE avisos_subastas_correos SET estado='procesando',intentos=intentos+1,ultimo_error=NULL
          WHERE id=? AND estado IN ('pendiente','error') AND intentos<5");
        $q->execute([(int)$r['id']]);
        if ($q->rowCount()!==1) continue;
        try {
            if (!filter_var($r['correo_destino'],FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Correo no válido');
            $m=new \PHPMailer\PHPMailer\PHPMailer(true);
            $m->isSMTP();
            $m->Host=(string)$cfg['host'];
            $m->SMTPAuth=true;
            $m->Username=(string)$cfg['usuario'];
            $m->Password=(string)$cfg['clave'];
            $m->Port=(int)($cfg['port']??587);
            if ($m->Port===465) $m->SMTPSecure=\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            else $m->SMTPSecure=\PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $m->CharSet='UTF-8';
            $m->setFrom((string)$cfg['remitente'], (string)($cfg['nombre_remitente']??'Guate-Subasta'));
            $m->addAddress((string)$r['correo_destino'],(string)$r['nombre']);
            $m->isHTML(false);
            $m->Subject=(string)$r['titulo'].' | Guate-Subasta';
            $m->Body="Hola ".$r['nombre'].".\n\n".$r['mensaje']."\n\nIngresa a Guate-Subasta para revisar la subasta #".$r['subasta_id'].".\n\nAviso automático de demostración.";
            $m->send();
            $q=$db->prepare("UPDATE avisos_subastas_correos SET estado='enviada',enviado_en=NOW(),ultimo_error=NULL WHERE id=?");
            $q->execute([(int)$r['id']]);
            $enviados++;
        } catch(Throwable $e) {
            $q=$db->prepare("UPDATE avisos_subastas_correos SET estado='error',ultimo_error=? WHERE id=?");
            $q->execute([mb_substr($e->getMessage(),0,255),(int)$r['id']]);
            $errores++;
        }
    }
    echo "Correos de subastas enviados: $enviados; errores: $errores.\n";
} catch(Throwable $e) {
    fwrite(STDERR,'Avisos de subastas: '.$e->getMessage()."\n");
    exit(1);
}
