import { computed, ref } from 'vue';

/**
 * Day / night mode.
 *
 * Until the user picks a mode, the app follows the operating-system setting (and keeps
 * following it live). Once they toggle, the choice is remembered in localStorage.
 * The same logic runs inline in the page <head> before first paint to avoid a flash.
 */
const STORAGE_KEY = 'theme';
const systemQuery = window.matchMedia('(prefers-color-scheme: dark)');

function readStoredPreference() {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);

        return stored === 'light' || stored === 'dark' ? stored : null;
    } catch {
        return null;
    }
}

const preference = ref(readStoredPreference());
const systemPrefersDark = ref(systemQuery.matches);

const isDark = computed(() => (preference.value ? preference.value === 'dark' : systemPrefersDark.value));

function apply() {
    document.documentElement.classList.toggle('dark', isDark.value);
}

systemQuery.addEventListener('change', (event) => {
    systemPrefersDark.value = event.matches;
    apply();
});

export function useTheme() {
    function setTheme(mode) {
        preference.value = mode;

        try {
            localStorage.setItem(STORAGE_KEY, mode);
        } catch {
            // Storage unavailable (private mode, blocked): the choice lasts for this visit only.
        }

        apply();
    }

    function toggleTheme() {
        setTheme(isDark.value ? 'light' : 'dark');
    }

    return { isDark, setTheme, toggleTheme };
}
