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

## Avisos internos y app instalable

- Al asignar un trabajo, el carpintero recibe un aviso (campanita, push y email) con el número y el nombre. El dueño recibe un aviso por cada avance que carga un carpintero y cuando un cliente califica un trabajo.
- El sistema es una app web instalable (PWA): `public/manifest.webmanifest`, `public/sw.js` e íconos en `public/icons/` (provisorios hasta tener el logo). Desde el celular: menú del navegador → "Agregar a pantalla de inicio" (en iPhone hace falta iOS 16.4 o superior para recibir avisos).
- Para las notificaciones push generá las claves una vez con `php artisan webpush:vapid` y completá `VAPID_*` en `.env`. Necesita HTTPS en producción.
- A futuro, para publicar en Google Play y App Store, la misma app se envuelve con Capacitor y se suma un canal de push nativo: los avisos se arman en un único lugar (`App\Notifications\Concerns\SendsAppNotification`).

## Documentación del personal para los barrios

En **Barrios** cada barrio tiene tildados los documentos que pide para entrar (ART, seguro de accidentes personales, DNI...) y, si tiene, su permiso o credencial propia. En **Barrios → Personal y documentación** se cargan los empleados (con o sin acceso al sistema) y sus archivos con fecha de vencimiento, guardados en el disco privado. Cada barrio muestra quién está habilitado y una nómina lista para mandar a la administración; si asignás a un trabajo a un carpintero que no puede entrar al barrio del cliente, el trabajo lo avisa.

Todos los días a las 8 el sistema avisa a los administradores (campanita, push y email) los documentos que vencen en los próximos `DOCUMENTOS_AVISO_DIAS` días (15 por defecto) y los que vencieron, una vez por cada uno. Para eso en producción tiene que correr el programador de tareas: `* * * * * php artisan schedule:run` en el cron del servidor.

## Plazos

Cada trabajo tiene un plazo en días (`PLAZO_DIAS`, 30 por defecto) y una fecha de inicio. La entrega estimada se calcula sola y se recalcula si cambia el plazo. El inicio se pone al crear el trabajo y se mueve al día en que el cliente acepta su primer presupuesto.

## Presupuestos

Los datos de la carpintería que salen en el PDF y el margen e IVA por defecto se configuran en `.env` (`EMPRESA_*`, `PRESUPUESTO_MARGEN`, `PRESUPUESTO_IVA`). El cliente ve el presupuesto sin precios unitarios ni margen; el PDF interno del admin muestra el detalle de costos.
