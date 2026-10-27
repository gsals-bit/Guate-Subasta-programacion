# Guate-Subasta — Cambios universitarios

- `Mis artículos` muestra solamente artículos creados por la sesión.
- `Programar subasta` lista solo artículos propios y el modelo comprueba propietario.
- Eliminar una subasta cerrada exige propietario o administrador.
- Administración: botón **Eliminar acceso** desactiva la cuenta y conserva historial por integridad referencial. El administrador no puede desactivarse a sí mismo. La sesión se revalida en cada solicitud.
- Tokens virtuales: cada cuenta nueva recibe 100,000 tokens de prueba; cuentas existentes reciben saldo inicial con la migración. El ganador de una subasta finalizada puede comprar con tokens (1 token = Q1 simulado). Se descuentan en transacción y se impide comprar dos veces.
- No procesa dinero real, ni transfiere propiedad, ni integra pasarela de pago.

## Instalación
1. Respaldar la base de datos `guate_subasta` desde phpMyAdmin.
2. Reemplazar los archivos de la carpeta `guate-subasta` por los de este ZIP, conservando tu `config/database.php` si contiene credenciales propias y tu carpeta `uploads` si ya tiene más fotos.
3. En phpMyAdmin, base `guate_subasta`, pestaña SQL: ejecutar `database/actualizacion_usuarios_tokens.sql` **una vez**. Si `modelo` ya existe, omitir ese ALTER.
4. **No importar** `database/guate_subasta.sql` sobre tu base actual: contiene `DROP TABLE` y borraría datos.
5. Cerrar sesión, iniciar sesión nuevamente y probar con dos cuentas Cliente y una Administrador.

## Notas
- El archivo SQL de instalación original está desfasado respecto a parte del PHP. La migración cubre `modelo`, `cerrada` y tablas de tokens; otros esquemas personalizados pueden necesitar ajustes.
- Las contraseñas de demostración del SQL original no deben publicarse en producción.
- Para uso público se requieren pruebas de seguridad, carga y concurrencia adicionales.
