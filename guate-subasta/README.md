# Guate-Subasta

Prototipo funcional para Análisis de Sistemas 2, basado en los requisitos documentados del proyecto.

## Requisitos
- XAMPP con Apache, PHP 8.x y MySQL/MariaDB
- Navegador moderno

## Instalación
1. Copiar la carpeta `guate-subasta` dentro de `C:\xampp\htdocs\`.
2. Iniciar Apache y MySQL desde XAMPP.
3. Abrir phpMyAdmin.
4. Importar `database/guate_subasta.sql`.
5. Abrir `http://localhost/guate-subasta/`.

## Base de datos
La conexión predeterminada usa:
- host: localhost
- base: guate_subasta
- usuario: root
- contraseña: vacía

Si tu MySQL usa otra contraseña, cambia `config/database.php`.

## Funcionalidad incluida
- Registro e inicio de sesión.
- Roles: Administrador, Subastador, Cliente y Auditor.
- Catálogo de subastas.
- Detalle e historial.
- Motor de pujas con transacción y `SELECT ... FOR UPDATE`.
- Regla de monto actual + incremento mínimo.
- Registro de artículos.
- Programación de subastas.
- Bitácora de auditoría.
- Estructura de órdenes/pagos.
- Procedimiento `cerrar_subasta`.
- Protección básica CSRF y consultas PDO preparadas.

## Nota sobre usuarios demo
El SQL contiene usuarios demostrativos. Si una contraseña demo no valida en tu versión de PHP, crea un Cliente desde Registro y luego cambia su `rol_id` en phpMyAdmin para probar otros roles. Esto evita depender de hashes generados en otra instalación.

## Pendiente para una versión de producción
Pasarela de pago real, carga segura de archivos/certificados, recuperación de contraseña por correo, WebSockets, job automático para cierres, edición completa de roles, pruebas automatizadas y endurecimiento de seguridad.
