function setLayout(name) {
  var base = document.documentElement.dataset.nexusLayoutBase || '';
  var link = document.getElementById('tz-layout-css');
  if (link && base) link.href = base + name + '.css';
  localStorage.setItem('tz-layout', name);
  if (name === 'material' && !document.getElementById('roboto-font')) {
    var l = document.createElement('link');
    l.id = 'roboto-font'; l.rel = 'stylesheet';
    l.href = 'https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap';
    document.head.appendChild(l);
  }
  if (name === 'android' && !document.getElementById('googlesans-font')) {
    var l = document.createElement('link');
    l.id = 'googlesans-font'; l.rel = 'stylesheet';
    l.href = 'https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;500;600;700&display=swap';
    document.head.appendChild(l);
  }
  document.querySelectorAll('.layout-option').forEach(function(b) {
    b.classList.toggle('active', b.dataset.layout === name);
  });
}

function setTheme(name) {
  var base = document.documentElement.dataset.nexusThemeBase || '';
  var link = document.getElementById('tz-theme-css');
  if (link && base) link.href = base + name + '.css';
  localStorage.setItem('tz-color-theme', name);
  document.querySelectorAll('.theme-swatch').forEach(function(s) {
    s.classList.toggle('active', s.dataset.theme === name);
  });
  var label = document.getElementById('theme-name-label');
  if (label) label.textContent = {rose:'Rose',ocean:'Ocean',emerald:'Emerald',violet:'Violet',sunset:'Sunset',liquidglass:'Liquid Glass'}[name] || name;
}

function toggleSidebar() {
  var hidden = document.body.classList.toggle('sidebar-hidden');
  localStorage.setItem('tz-sidebar', hidden ? 'hidden' : 'visible');
}

function closeMobileSidebar() {
  var sidebar = document.getElementById('sidebar');
  var overlay = document.getElementById('sidebar-overlay');
  if (sidebar) sidebar.classList.remove('mobile-open');
  if (overlay) overlay.classList.remove('show');
}

function toggleDarkMode() {
  var html = document.documentElement;
  var isDark = html.getAttribute('data-bs-theme') === 'dark';
  var newMode = isDark ? 'light' : 'dark';
  html.setAttribute('data-bs-theme', newMode);
  localStorage.setItem('tz-theme', newMode);
  var btn = document.getElementById('darkBtn');
  if (btn) btn.querySelector('i').className = isDark ? 'bi bi-moon' : 'bi bi-sun';
  if (typeof visDarkmodeSave === 'function') visDarkmodeSave(newMode);
}

document.addEventListener('DOMContentLoaded', function () {
  var isDark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
  var btn = document.getElementById('darkBtn');
  if (btn) btn.querySelector('i').className = isDark ? 'bi bi-sun' : 'bi bi-moon';

  var sidebarState = localStorage.getItem('tz-sidebar');
  if (sidebarState === 'hidden') document.body.classList.add('sidebar-hidden');
  document.documentElement.classList.remove('sidebar-hidden-early');

  var overlay = document.getElementById('sidebar-overlay');
  if (overlay) overlay.addEventListener('click', closeMobileSidebar);

  var savedLayout = localStorage.getItem('tz-layout');
  if (savedLayout) setLayout(savedLayout);

  var savedColorTheme = localStorage.getItem('tz-color-theme');
  if (savedColorTheme) setTheme(savedColorTheme);
});
