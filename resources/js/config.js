/**
 * Runtime settings injected by the Blade view (resources/views/app.blade.php),
 * with defaults for the static build.
 */
const injected = window.__APP_CONFIG__ ?? {};

export const appConfig = {
    name: injected.name ?? 'Saving Challenge',
    currency: injected.currency ?? 'USD',
    locale: navigator.language || 'en-US',
    apiBaseUrl: import.meta.env.VITE_API_BASE_URL ?? '/api/v1',
};
