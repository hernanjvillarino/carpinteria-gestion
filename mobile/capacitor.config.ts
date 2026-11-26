import type { CapacitorConfig } from '@capacitor/cli';
import { writeFileSync } from 'node:fs';

// La app abre el sistema publicado (Laravel) dentro de un WebView nativo.
// CARPINTERIA_URL se define al compilar; en desarrollo puede ser la IP de la compu,
// por ejemplo CARPINTERIA_URL=http://192.168.0.10:8000 npx cap sync
//
// Sin CARPINTERIA_URL (o con "muestra") se arma la versión de muestra: funciona sin
// servidor, con datos ficticios, a partir de muestra/index.html.
const url = process.env.CARPINTERIA_URL && process.env.CARPINTERIA_URL !== 'muestra' ? process.env.CARPINTERIA_URL : null;

// La pantalla "sin conexión" (www/sin-conexion.html) necesita saber a dónde reintentar.
if (url) writeFileSync('www/destino.js', `window.CARPINTERIA_URL = ${JSON.stringify(`${url}/panel`)};\n`);

const config: CapacitorConfig = {
    // Identificador único en Play Store y App Store: no se puede cambiar después de publicar.
    appId: 'com.carpinteria.gestion',
    appName: 'Carpintería',
    webDir: url ? 'www' : 'muestra',
    server: url
        ? {
              url: `${url}/panel`,
              allowNavigation: [new URL(url).host],
              cleartext: url.startsWith('http://'),
              errorPath: 'sin-conexion.html',
          }
        : undefined,
    android: {
        // Para depurar el WebView desde chrome://inspect solo en builds de prueba.
        webContentsDebuggingEnabled: false,
    },
    ios: {
        contentInset: 'automatic',
        limitsNavigationsToAppBoundDomains: false,
    },
    // Evita un choque de nombres de paquetes Swift con el plugin de Firebase (ver su README).
    experimental: {
        ios: {
            spm: {
                packageOptions: {
                    '@capacitor-firebase/messaging': { symlink: true },
                },
            },
        },
    },
    plugins: {
        // Barra de estado con íconos claros sobre el azul marino; la página no se mete debajo.
        SystemBars: {
            insetsHandling: 'css',
            style: 'DARK',
        },
        SplashScreen: {
            launchShowDuration: 800,
            backgroundColor: '#17263A',
            showSpinner: false,
        },
        FirebaseMessaging: {
            presentationOptions: ['badge', 'sound', 'alert'],
        },
    },
};

export default config;
