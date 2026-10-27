# Guate-Subasta: diseño y entrega de artículos

1. Haz copia de seguridad de la base de datos y del proyecto.
2. Si partes de cero, importa `database/01_INSTALAR_LAB_NUEVO.sql`. Si actualizas un proyecto existente, aplica primero `02_MIGRACION_PAGOS_PUJAS.sql` solo si no se aplicó anteriormente.
3. Ejecuta UNA SOLA VEZ `database/03_DISENO_UBICACION_ARCHIVO.sql` en phpMyAdmin sobre `guate_subasta_lab`.
4. Comprueba `config/database.php` y abre `index.php` en Apache/XAMPP.
5. Prueba administrador > Artículos > Archivar seleccionados; auditoría > Imprimir; cliente > catálogo > detalle > ampliar imagen y mapa.

La ubicación es un **punto de entrega declarado manualmente** por el creador del artículo, NO se captura automáticamente la geolocalización del visitante. Se aconseja un lugar público, no la casa del vendedor. Google Maps requiere conexión a Internet.

Archivar es reversible en base de datos (`UPDATE articulos SET archivado=0 WHERE id=...`), conserva pagos y pujas y oculta los artículos del listado. No es borrado físico.

Pagos ficticios: no ingresar datos reales de tarjeta. La impresión de bitácoras usa la opción del navegador «Guardar como PDF».

**Limitaciones**: La nueva migración es obligatoria; el comportamiento integral debe probarse en XAMPP con MySQL. No ejecutar archivos de migración dos veces. No reemplazar el proyecto original sin copia.
