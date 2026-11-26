// Puente con la app nativa de Android / iPhone (Capacitor).
//
// La app abre este mismo sitio dentro de un WebView y le inyecta window.Capacitor
// con los plugins nativos. Acá registramos el celular para los avisos push
// (Firebase Cloud Messaging) y abrimos el aviso que tocó la persona.

const TOKEN_KEY = 'carpinteria.fcmToken';

export const isNativeApp = () => Boolean(window.Capacitor?.isNativePlatform?.());

const plugin = (name) => window.Capacitor?.Plugins?.[name];

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

const send = (method, body) =>
    fetch(document.querySelector('meta[name="device-endpoint"]').content, {
        method,
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' },
        body: JSON.stringify(body),
    });

async function registerDevice(messaging, status) {
    let { receive } = await messaging.checkPermissions();
    if (receive === 'prompt' || receive === 'prompt-with-rationale') {
        ({ receive } = await messaging.requestPermissions());
    }
    if (receive !== 'granted') {
        if (status) status.textContent = 'La app no tiene permiso para mostrar avisos. Habilitalo en Ajustes > Notificaciones del celular.';
        return;
    }

    const { token } = await messaging.getToken();
    const platform = window.Capacitor.getPlatform();
    const device = plugin('Device') ? await plugin('Device').getInfo() : {};
    const res = await send('POST', { token, platform, device_name: device.model || null });
    if (res.ok) {
        localStorage.setItem(TOKEN_KEY, token);
        if (status) status.textContent = 'Los avisos están activados en este celular.';
    }
}

export function setupNativeApp() {
    document.documentElement.classList.add('app-nativa');
    const messaging = plugin('FirebaseMessaging');
    const loggedIn = Boolean(document.querySelector('meta[name="device-endpoint"]'));

    // Al cerrar sesión, el celular deja de recibir los avisos de esta cuenta.
    document.querySelectorAll('form[data-logout]').forEach((form) =>
        form.addEventListener('submit', () => {
            const token = localStorage.getItem(TOKEN_KEY);
            if (!token) return;
            const input = Object.assign(document.createElement('input'), { type: 'hidden', name: 'device_token', value: token });
            form.append(input);
            localStorage.removeItem(TOKEN_KEY);
        }),
    );

    if (!messaging) return;

    // Android 8+: canal propio para que los avisos suenen y se vean arriba.
    if (window.Capacitor.getPlatform() === 'android') {
        messaging.createChannel({ id: 'avisos', name: 'Avisos de trabajos', importance: 4 }).catch(() => {});
    }

    // Tocar un aviso abre la pantalla correspondiente.
    messaging.addListener('notificationActionPerformed', ({ notification }) => {
        const url = notification?.data?.url;
        if (url && new URL(url, location.href).origin === location.origin) location.href = url;
    });

    // Con la app abierta, el aviso no aparece en la barra del sistema: actualizamos la campanita.
    messaging.addListener('notificationReceived', () => document.dispatchEvent(new Event('avisos:nuevo')));

    // Firebase puede renovar el token: lo volvemos a mandar.
    messaging.addListener('tokenReceived', ({ token }) => {
        if (loggedIn) send('POST', { token, platform: window.Capacitor.getPlatform() }).then(() => localStorage.setItem(TOKEN_KEY, token));
    });

    // La sección de avisos muestra el estado del celular en vez del botón del navegador.
    const pushBox = document.querySelector('[data-push]');
    const status = pushBox?.querySelector('[data-push-status]');
    if (pushBox) {
        const button = pushBox.querySelector('button');
        button.textContent = 'Activar avisos';
        button.hidden = false;
        button.addEventListener('click', () => registerDevice(messaging, status).catch(() => {}));
        messaging.checkPermissions().then(({ receive }) => {
            status.textContent = receive === 'granted' ? 'Los avisos están activados en este celular.' : 'Activá los avisos para enterarte de los trabajos aunque la app esté cerrada.';
            button.hidden = receive === 'granted';
        });
    }

    // Con la sesión iniciada, registramos el celular (pide permiso la primera vez).
    if (loggedIn) registerDevice(messaging, status).catch(() => {});
}
