/*=============== TOGGLE SIDEBAR VISIBILITY ===============*/
const toggleSidebarVisibility = (toggleId, sidebarId, headerId, mainId) => {
    const toggleButton = document.getElementById(toggleId),
          sidebarMenu = document.getElementById(sidebarId),
          headerElement = document.getElementById(headerId),
          mainContent = document.getElementById(mainId);

    if (toggleButton && sidebarMenu && headerElement && mainContent) {
        toggleButton.addEventListener('click', () => {
            sidebarMenu.classList.toggle('show-sidebar');
            headerElement.classList.toggle('left-pd');
            mainContent.classList.toggle('left-pd');
        });
    }
};
toggleSidebarVisibility('header-toggle', 'sidebar', 'header', 'main');

/*=============== LINK ACTIVE ===============*/
const sidebarLinks = document.querySelectorAll('.sidebar-list a');

function setActiveLinkColor() {
    sidebarLinks.forEach(link => link.classList.remove('active-link'));
    this.classList.add('active-link');
    
    // Close the sidebar after clicking a link (for mobile view)
    const sidebarMenu = document.getElementById('sidebar');
    const headerElement = document.getElementById('header');
    const mainContent = document.getElementById('main');
    sidebarMenu.classList.remove('show-sidebar');
    headerElement.classList.remove('left-pd');
    mainContent.classList.remove('left-pd');
}

sidebarLinks.forEach(link => link.addEventListener('click', setActiveLinkColor));

// Highlight active link on page load based on URL
window.addEventListener('load', () => {
    const currentPage = window.location.pathname;
    sidebarLinks.forEach(link => {
        if (link.href.includes(currentPage)) {
            link.classList.add('active-link');
        }
    });
});

/*=============== DARK LIGHT THEME ===============*/
const themeToggleButton = document.getElementById('theme-button');
const themeText = themeToggleButton.querySelector('span'); // Target the <span> inside the button
const darkModeClass = 'dark-theme';
const sunIconClass = 'bi-cloud-sun-fill'; // Dark mode icon
const lightIconClass = 'bi-cloud-sun'; // Light mode icon

const userTheme = localStorage.getItem('currentTheme');
const userIcon = localStorage.getItem('currentIcon');

// Apply saved theme and icon state on page load
if (userTheme) {
    document.body.classList.toggle(darkModeClass, userTheme === 'dark');
    themeToggleButton.classList.toggle(sunIconClass, userTheme === 'dark');
    themeToggleButton.classList.toggle(lightIconClass, userTheme !== 'dark');
    themeText.textContent = userTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
} else {
    // Default to light mode
    document.body.classList.remove(darkModeClass);
    themeToggleButton.classList.remove(sunIconClass);
    themeToggleButton.classList.add(lightIconClass);
    themeText.textContent = 'Dark Mode';
    localStorage.setItem('currentTheme', 'light');
    localStorage.setItem('currentIcon', lightIconClass);
}

// Toggle theme and icon
themeToggleButton.addEventListener('click', () => {
    const isDarkMode = document.body.classList.toggle(darkModeClass);

    themeToggleButton.classList.toggle(sunIconClass, isDarkMode);
    themeToggleButton.classList.toggle(lightIconClass, !isDarkMode);
    themeText.textContent = isDarkMode ? 'Light Mode' : 'Dark Mode';

    localStorage.setItem('currentTheme', isDarkMode ? 'dark' : 'light');
    localStorage.setItem('currentIcon', isDarkMode ? sunIconClass : lightIconClass);
});

// Listen for storage changes (for multiple tabs)
window.addEventListener('storage', () => {
    const newTheme = localStorage.getItem('currentTheme');
    document.body.classList.toggle(darkModeClass, newTheme === 'dark');
    themeToggleButton.classList.toggle(sunIconClass, newTheme === 'dark');
    themeToggleButton.classList.toggle(lightIconClass, newTheme !== 'dark');
    themeText.textContent = newTheme === 'dark' ? 'Light Mode' : 'Dark Mode';
});
