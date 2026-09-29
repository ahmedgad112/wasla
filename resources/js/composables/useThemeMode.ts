import { onMounted, ref } from 'vue';

export function useThemeMode() {
    const darkMode = ref(false);

    onMounted(() => {
        const isDark = localStorage.getItem('fatrna_theme') === 'dark';
        darkMode.value = isDark;
        document.documentElement.classList.toggle('dark', isDark);

        if (!('fatrna_theme' in localStorage)) {
            localStorage.setItem('fatrna_theme', 'light');
        }
    });

    const toggleDarkMode = (): void => {
        const next = !darkMode.value;
        darkMode.value = next;
        document.documentElement.classList.toggle('dark', next);
        localStorage.setItem('fatrna_theme', next ? 'dark' : 'light');
    };

    return { darkMode, toggleDarkMode };
}
