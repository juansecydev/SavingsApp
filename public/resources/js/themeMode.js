"use strict";

const themeButton = document.getElementById('theme-toggle');
const themeIcon = themeButton?.querySelector('i');
const themePreferenceKey = 'savings-app-theme';

const getSavedTheme = function () {
    try {
        const savedTheme = window.localStorage.getItem(themePreferenceKey);
        return savedTheme === 'light' || savedTheme === 'dark' ? savedTheme : 'dark';
    } catch (error) {
        console.warn('Unable to read the saved theme preference.', error);
        return 'dark';
    }
};

const applyTheme = function (theme) {
    document.documentElement.setAttribute('data-bs-theme', theme);

    if (!themeButton || !themeIcon) {
        return;
    }

    const switchToLight = theme === 'dark';
    const iconClass = switchToLight ? 'bi-brightness-high-fill' : 'bi-moon-fill';
    const label = switchToLight ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro';

    themeIcon.classList.remove('bi-brightness-high-fill', 'bi-moon-fill');
    themeIcon.classList.add('bi', iconClass);
    themeButton.setAttribute('aria-label', label);
    themeButton.setAttribute('title', label);
};

applyTheme(getSavedTheme());

if (themeButton) {
    themeButton.addEventListener('click', function () {
        const currentTheme = document.documentElement.getAttribute('data-bs-theme');
        const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';

        applyTheme(nextTheme);

        try {
            window.localStorage.setItem(themePreferenceKey, nextTheme);
        } catch (error) {
            console.warn('Unable to save the theme preference.', error);
        }
    });
}
