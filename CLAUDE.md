# Carpintería: sitio web y gestión

- Laravel 13, PHP 8.3+ (producción 8.5), MySQL 8. Interfaz en español rioplatense (vos).
- Roles en `App\Enums\Role`: admin, carpintero, cliente. Middleware `role:` y `WorkOrderPolicy`.
- Los "trabajos" son `WorkOrder` / tabla `work_orders`: Laravel reserva `jobs` para las colas.
- Rutas en español (`/admin/trabajos`, `/taller`, `/mi-cuenta`).
- Fotos de obra en el disco privado `local`, servidas por `WorkOrderPhotoController` con permisos.
- Tests: `php artisan test` (SQLite en memoria). Formato: `vendor/bin/pint`.
- Emails (avances y diseños) van por cola: `php artisan queue:work` en producción.
- Plan por etapas: 1 trabajos y carpinteros, 2 portal del cliente, 3 stock y presupuestos, 4 despiece SketchUp (OpenCutList CSV) → XLSX CorteOpt, 5 sitio público.
