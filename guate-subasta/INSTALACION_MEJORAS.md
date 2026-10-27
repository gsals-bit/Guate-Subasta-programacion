# Guate-Subasta · Módulos de demostración

## Instalación nueva
Importar `database/01_INSTALAR_LAB_NUEVO.sql` en phpMyAdmin sobre un servidor MySQL sin la base `guate_subasta_lab` existente. El script crea la base y tablas. **No ejecutar además la migración 02**.

## Base de datos existente
Hacer copia de seguridad y ejecutar **una sola vez** `database/02_MIGRACION_PAGOS_PUJAS.sql` desde phpMyAdmin, base `guate_subasta_lab`. No importar el instalador completo encima de una base existente.

## Acceso
Configurar credenciales locales en `config/database.php`. Iniciar Apache y MySQL en XAMPP, abrir `http://localhost/Guate-Subasta-programacion/guate-subasta/index.php` si la carpeta coincide. Los usuarios demo de la instalación original se conservan.

## Flujo de prueba
1. Iniciar sesión como Cliente, entrar a una subasta activa y realizar una puja.
2. En Mis pujas, cancelar la puja; verificar el estado cancelada y el nuevo monto actual.
3. Realizar una nueva puja y dejar vencer la subasta. Solo el ganador verá la opción de pago ficticio en Detalle.
4. Pulsar Llenado automático y Confirmar pago simulado. Se crea una orden y pago con referencia `DEMO-...`, sin pasarela ni datos de tarjeta reales.
5. Iniciar sesión como Administrador: Estadísticas y Auditoría.

**Importante:** Este sistema es un prototipo académico. No recibe pagos reales ni verifica tarjetas. No se almacenan números de tarjetas. El flujo de compra con tokens se mantiene como alternativa, pero no permite doble compra una vez registrado un pago demo. No subir credenciales reales ni archivos de configuración con secretos a repositorios públicos.
