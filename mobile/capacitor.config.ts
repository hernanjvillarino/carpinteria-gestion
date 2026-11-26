import type { CapacitorConfig } from '@capacitor/cli';
import { writeFileSync } from 'node:fs';

// La app abre el sistema publicado (Laravel) dentro de un WebView nativo.
// CARPINTERIA_URL se define al compilar; en desarrollo puede ser la IP de la compu,
// por ejemplo CARPINTERIA_URL=http://192.168.0.10:8000 npx cap sync
const url = process.env.CARPINTERIA_URL ?? 'https://gestion.example.com';
const host = new URL(url).host;

// La pantalla "sin conexión" (www/sin-conexion.html) necesita saber a dónde reintentar.
writeFileSync('www/destino.js', `window.CARPINTERIA_URL = ${JSON.stringify(`${url}/panel`)};\n`);

const config: CapacitorConfig = {
    // Identificador único en Play Store y App Store: no se puede cambiar después de publicar.
    appId: 'com.carpinteria.gestion',
    appName: 'Carpintería',
    webDir: 'www',
    server: {
        url: `${url}/panel`,
        allowNavigation: [host],
        cleartext: url.startsWith('http://'),
        errorPath: 'sin-conexion.html',
    },
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
