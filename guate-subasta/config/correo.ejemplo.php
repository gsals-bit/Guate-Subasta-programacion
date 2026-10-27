<?php
// Copia a config/correo.php y agrega tus propios datos SMTP.
// Nunca publiques las credenciales en GitHub ni uses tu contrasena principal de Gmail.
return [
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'usuario' => 'TU_CORREO@gmail.com',
    'clave' => 'CONTRASENA_DE_APLICACION',
    'remitente' => 'TU_CORREO@gmail.com',
    'nombre_remitente' => 'Guate-Subasta - Demo',
    // Deja vacio si no has publicado el sitio. localhost no abre desde otro dispositivo.
    'url_publica' => '',
];
