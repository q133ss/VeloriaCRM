// Pusher's app key and cluster are not secrets (the web dashboard already
// prints them into every page's HTML, see layouts/app.blade.php's
// `data-pusher-key`) — safe to ship in the app bundle like API_BASE_URL.
export const PUSHER_APP_KEY = process.env.EXPO_PUBLIC_PUSHER_APP_KEY?.trim() || '';
export const PUSHER_APP_CLUSTER = process.env.EXPO_PUBLIC_PUSHER_APP_CLUSTER?.trim() || undefined;
