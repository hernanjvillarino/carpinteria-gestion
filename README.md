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
