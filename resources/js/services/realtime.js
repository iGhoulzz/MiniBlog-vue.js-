import axios from 'axios';
import Pusher from 'pusher-js';

const appKey = import.meta.env.VITE_PUSHER_APP_KEY;
const cluster = import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1';
const appUrl = (import.meta.env.VITE_APP_URL ?? '').replace(/\/$/, '');
const authEndpoint = appUrl ? `${appUrl}/broadcasting/auth` : '/broadcasting/auth';

const rawScheme = (import.meta.env.VITE_PUSHER_SCHEME ?? '').toLowerCase();
const rawForceTls = import.meta.env.VITE_PUSHER_FORCE_TLS;
const rawHost = (import.meta.env.VITE_PUSHER_HOST ?? '').trim();
const rawPort = import.meta.env.VITE_PUSHER_PORT;
const rawPath = (import.meta.env.VITE_PUSHER_PATH ?? import.meta.env.VITE_PUSHER_WS_PATH ?? '').trim();

const parseBoolean = (value, fallback = false) => {
  if (value === undefined || value === null || value === '') return fallback;
  if (typeof value === 'boolean') return value;
  const normalized = String(value).trim().toLowerCase();
  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;
  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;
  return fallback;
};

const debugRealtime = parseBoolean(import.meta.env.VITE_CHAT_DEBUG, import.meta.env.DEV ?? false);
if (debugRealtime && typeof Pusher !== 'undefined') {
  Pusher.logToConsole = true;
}

const fallbackTls = rawScheme ? rawScheme === 'https' : true;
const forceTLS = parseBoolean(rawForceTls, fallbackTls);

const resolvePort = () => {
  if (!rawPort && rawPort !== 0) return null;
  const port = Number(rawPort);
  return Number.isFinite(port) && port > 0 ? port : null;
};

const resolvedPort = resolvePort();
const resolvedHost = rawHost || null;
const resolvedPath = rawPath || null;

let pusher = null;

const http = axios.create({
  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  },
  withCredentials: true,
});

const applyTokenHeader = (token) => {
  if (token) {
    http.defaults.headers.common.Authorization = `Bearer ${token}`;
  } else {
    delete http.defaults.headers.common.Authorization;
  }
};

const createAuthorizer = () => (channel) => ({
  authorize(socketId, callback) {
    http
      .post(authEndpoint, {
        socket_id: socketId,
        channel_name: channel.name,
      })
      .then(({ data }) => callback(null, data))
      .catch((error) => {
        if (import.meta.env.DEV) {
          console.error('[Pusher] Channel auth failed', {
            status: error.response?.status,
            data: error.response?.data,
          });
        }
        callback(
          true,
          error.response?.data ?? { message: error.message || 'Channel authorization failed.' }
        );
      });
  },
});

applyTokenHeader(null);

if (!appKey) {
  console.warn('[Pusher] Missing VITE_PUSHER_APP_KEY - realtime disabled.');
} else {
  const options = {
    cluster,
    forceTLS,
    authorizer: createAuthorizer(),
    enabledTransports: ['ws', 'wss'],
  };

  if (resolvedHost) {
    options.wsHost = resolvedHost;
  }

  if (resolvedPort) {
    options.wsPort = resolvedPort;
    options.wssPort = resolvedPort;
  }

  if (resolvedPath) {
    options.wsPath = resolvedPath;
  }

  pusher = new Pusher(appKey, options);
}

export function setRealtimeAuthToken(token) {
  applyTokenHeader(token);
}

export default pusher;
