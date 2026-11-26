# App de Android e iPhone (Capacitor)

La app nativa está en `mobile/`. Es un contenedor de Capacitor que abre el
sistema publicado (Laravel) dentro de un WebView nativo, así que cualquier
cambio en el sistema se ve en la app sin publicar una versión nueva en las
tiendas. Lo que suma la app frente a la PWA:

- **Avisos push nativos** por Firebase Cloud Messaging (FCM). Web Push no
  funciona dentro de una app de tienda. FCM entrega en Android y también en
  iPhone (Firebase los reenvía a Apple con la clave APNs).
- **Cámara y galería** del celular para las fotos de obra y de la
  calificación: los campos de foto existentes abren la cámara nativa.
- Ícono, pantalla de inicio y página "sin conexión" propios.

## Cómo funciona

| Parte | Dónde |
| --- | --- |
| Configuración de la app (id, nombre, URL del sistema) | `mobile/capacitor.config.ts` |
| Proyecto Android (Android Studio) | `mobile/android/` |
| Proyecto iPhone (Xcode) | `mobile/ios/` |
| Ícono y pantalla de inicio (fuentes) | `mobile/assets/` |
| Puente web ↔ app (registro del celular, abrir aviso tocado) | `resources/js/native.js` |
| Alta/baja del celular | `POST/DELETE /push/dispositivo` → `DeviceTokenController`, tabla `device_tokens` |
| Envío de avisos | `App\Notifications\Channels\FcmChannel` + `App\Services\FcmClient` (API HTTP v1) |

Cada aviso interno (trait `SendsAppNotification`) sale por la campanita, por
Web Push si la persona lo activó en el navegador, y por FCM a cada celular
con la app. Al cerrar sesión en la app, ese celular deja de recibir avisos de
esa cuenta. Si el mismo celular lo usa otra persona, el token pasa a la cuenta
que inició sesión.

## Puesta en marcha (una sola vez)

1. **Firebase**: crear un proyecto en <https://console.firebase.google.com>.
   - Agregar una app Android con el id `com.carpinteria.gestion` y bajar
     `google-services.json` a `mobile/android/app/`.
   - Agregar una app iOS con el mismo id y bajar `GoogleService-Info.plist` a
     `mobile/ios/App/App/` (en Xcode, arrastrarlo dentro del grupo *App*).
   - Configuración del proyecto → Cuentas de servicio → "Generar nueva clave
     privada". Ese JSON va al servidor, fuera de `public/`.
2. **Servidor** (`.env` de producción):
   ```
   FCM_PROJECT_ID=id-del-proyecto-firebase
   FCM_CREDENTIALS=/ruta/segura/firebase-cuenta-servicio.json
   ```
   y correr `php artisan migrate`. Los avisos van por la cola, como los emails.
3. **Apple** (cuenta de desarrollador, USD 99 por año): en
   developer.apple.com crear una clave APNs (.p8) y subirla en Firebase →
   Configuración → Cloud Messaging → app de iOS.
4. **Google Play** (cuenta de desarrollador, USD 25 una vez).

> El id `com.carpinteria.gestion` no se puede cambiar después de publicar.
> Si la carpintería tiene dominio propio conviene usarlo al revés (por ejemplo
> `ar.com.midominio.gestion`) y cambiarlo en `capacitor.config.ts`,
> `android/app/build.gradle` y en Xcode antes de la primera publicación.

## APK de prueba (sin Play Store)

El workflow `.github/workflows/apk.yml` arma un APK instalable en GitHub
Actions cada vez que cambia `mobile/`, o a mano desde *Actions → APK de prueba
(Android) → Run workflow*, donde se puede escribir la URL del sistema.

1. Definir la URL una vez: *Settings → Secrets and variables → Actions →
   Variables → New repository variable* `CARPINTERIA_URL`.
2. Opcional, para avisos push: secreto `GOOGLE_SERVICES_JSON` con el
   contenido de `google-services.json` de Firebase.
3. Abrir la ejecución terminada y bajar *carpinteria-apk-prueba* (un .zip con
   el .apk adentro).
4. En el celular: abrir el .apk, aceptar "Instalar apps de origen
   desconocido" para el navegador o el administrador de archivos, e instalar.
   Las versiones nuevas se instalan encima sin perder la sesión.

Es un APK de depuración: sirve para probar, no para publicar.

## Compilar

Requisitos: Node 22+, Android Studio (Android) y una Mac con Xcode 26+ (iPhone).

```bash
cd mobile
npm install
CARPINTERIA_URL=https://gestion.tudominio.com.ar npx cap sync   # URL del sistema publicado
npm run android   # abre Android Studio
npm run ios       # abre Xcode
```

Para probar contra la compu en la misma red wifi:
`CARPINTERIA_URL=http://192.168.0.10:8000 npx cap sync` (con
`php artisan serve --host=0.0.0.0`).

### Versión firmada para Play Store

1. Crear la clave de firma (guardarla en un lugar seguro, sin ella no se
   puede actualizar la app):
   ```bash
   keytool -genkey -v -keystore mobile/android/carpinteria-release.jks -alias carpinteria -keyalg RSA -keysize 2048 -validity 10000
   ```
2. Copiar `mobile/android/keystore.properties.example` a
   `keystore.properties` y completar las contraseñas.
3. `cd mobile/android && ./gradlew bundleRelease` genera
   `app/build/outputs/bundle/release/app-release.aab`, que se sube a Play
   Console. Antes de cada versión nueva, subir `versionCode` en
   `android/app/build.gradle`.

### Versión para App Store

En Xcode: elegir el equipo de desarrollador en *Signing & Capabilities*,
verificar que estén *Push Notifications* y *Background Modes → Remote
notifications*, poner `aps-environment` en `production` en
`App/App.entitlements`, y *Product → Archive → Distribute App*.

## Ícono y pantalla de inicio

Son provisorios (tres tableros con canto en azul marino y arena) hasta tener
el logo. Para cambiarlos, reemplazar `mobile/assets/icon-only.png` (1024×1024),
`icon-foreground.png`, `icon-background.png`, `splash.png` y `splash-dark.png`
(2732×2732) y correr `npm run assets` dentro de `mobile/`.

## Revisión de las tiendas

Apple rechaza apps que son solo un sitio web dentro de un marco. Esta app
suma avisos push nativos, cámara y ícono/pantallas propios, y el sistema
requiere cuenta, así que en la revisión hay que dar un usuario de prueba
(cliente y carpintero) con datos de ejemplo.
