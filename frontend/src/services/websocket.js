import Pusher from 'pusher-js'

export function createWebSocket(userId) {
  return new Pusher(import.meta.env.VITE_REVERB_APP_KEY, {
    wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
    wsPort: parseInt(import.meta.env.VITE_REVERB_PORT || '8080'),
    wssPort: parseInt(import.meta.env.VITE_REVERB_PORT || '8080'),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'ws') === 'wss',
    enabledTransports: ['ws', 'wss'],
    cluster: 'mt1',
    authEndpoint: `${import.meta.env.VITE_API_URL || 'http://localhost'}/broadcasting/auth`,
    auth: {
      headers: {
        'X-User-Id': String(userId),
      },
    },
  })
}

export default createWebSocket
