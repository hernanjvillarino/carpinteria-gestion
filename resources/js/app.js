// App instalable (PWA): service worker, avisos push y actualizaciones en vivo.

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js').catch(() => {}));
}

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content;

// Refresca un fragmento de HTML cada tantos segundos (actividad en vivo del tablero).
document.querySelectorAll('[data-live-activity]').forEach((el) => {
    const refresh = async () => {
        if (document.hidden) return;
        try {
            const res = await fetch(el.dataset.liveActivity, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (res.ok) el.innerHTML = await res.text();
        } catch {}
    };
    setInterval(refresh, 15000);
});

// Contador de la campanita.
const bell = document.querySelector('[data-unread-count]');
if (bell) {
    const update = async () => {
        if (document.hidden) return;
        try {
            const res = await fetch(bell.dataset.unreadCount, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const { unread } = await res.json();
            const badge = bell.querySelector('[data-badge]');
            badge.textContent = unread > 99 ? '99+' : unread;
            badge.hidden = unread === 0;
        } catch {}
    };
    setInterval(update, 30000);
}

// Activar / desactivar avisos en este dispositivo.
const pushBox = document.querySelector('[data-push]');
if (pushBox) {
    const status = pushBox.querySelector('[data-push-status]');
    const button = pushBox.querySelector('button');
    const key = pushBox.dataset.vapidKey;

    const toUint8 = (base64) => {
        const padded = (base64 + '='.repeat((4 - (base64.length % 4)) % 4)).replace(/-/g, '+').replace(/_/g, '/');
        return Uint8Array.from(atob(padded), (c) => c.charCodeAt(0));
    };

    const render = async () => {
        if (!('serviceWorker' in navigator) || !('PushManager' in window) || !key) {
            status.textContent = 'Este navegador no admite avisos. En iPhone, primero agregá la app a la pantalla de inicio.';
            button.hidden = true;
            return;
        }
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        status.textContent = sub ? 'Los avisos están activados en este dispositivo.' : 'Los avisos están desactivados en este dispositivo.';
        button.textContent = sub ? 'Desactivar avisos' : 'Activar avisos';
        button.hidden = false;
    };

    button.addEventListener('click', async () => {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        const headers = { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf(), Accept: 'application/json' };
        if (sub) {
            await fetch(pushBox.dataset.endpoint, { method: 'DELETE', headers, body: JSON.stringify({ endpoint: sub.endpoint }) });
            await sub.unsubscribe();
        } else {
            if ((await Notification.requestPermission()) !== 'granted') {
                status.textContent = 'No diste permiso para mostrar avisos. Podés habilitarlo en la configuración del navegador.';
                return;
            }
            const created = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: toUint8(key) });
            const json = created.toJSON();
            const encoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];
            await fetch(pushBox.dataset.endpoint, { method: 'POST', headers, body: JSON.stringify({ ...json, contentEncoding: encoding }) });
        }
        render();
    });

    render();
}
