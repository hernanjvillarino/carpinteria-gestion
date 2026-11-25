# Carpintería: sitio web y gestión

Sistema para una carpintería de muebles a medida: sitio público, gestión de trabajos con carpinteros, portal de seguimiento para clientes, stock e insumos, presupuestos y despiece desde SketchUp con exportación para CorteOpt.

Stack: Laravel 13, PHP 8.3+ (recomendado 8.5 en producción), MySQL 8.

## Instalación local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

## Emails al cliente

El sistema avisa por email cuando un pedido pasa a una etapa visible y cuando hay un diseño para aprobar, y avisa a los administradores cuando el cliente responde. Los emails salen por la cola: en producción configurá `MAIL_*` en `.env` y dejá corriendo `php artisan queue:work` (con Supervisor o el programador de tareas del hosting).

## Presupuestos

Los datos de la carpintería que salen en el PDF y el margen e IVA por defecto se configuran en `.env` (`EMPRESA_*`, `PRESUPUESTO_MARGEN`, `PRESUPUESTO_IVA`). El cliente ve el presupuesto sin precios unitarios ni margen; el PDF interno del admin muestra el detalle de costos.
