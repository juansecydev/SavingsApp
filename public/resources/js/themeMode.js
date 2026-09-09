"use strict";
const htmlElement = document.documentElement; // Targets the <html> element
const currentTheme = htmlElement.getAttribute('data-bs-theme');
//const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
const newTheme = "dark";
htmlElement.setAttribute('data-bs-theme', newTheme);