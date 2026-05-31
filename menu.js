(function() {
  const sidebarStorageKey = 'tw-sidebar-collapsed';
  const desktopMediaQuery = window.matchMedia('(min-width: 992px)');

  const menuItems = [
    { href: "index.php", label: "Start" },
    { href: "riegen.php", label: "Riegen" },
    { href: "kari.php", label: "Kari-Wertungseingabe" },
    { href: "ergebnisse.php", label: "Wettkampf Ergebnisse" },
    { href: "geraete_verwaltung.php", label: "Geräte" },
    { href: "geraetetypen_verwaltung.php", label: "Gerätetypen" },
    { href: "durchgaenge.php", label: "Durchgänge" },
    { href: "riegen_verwaltung.php", label: "Riegen Verwaltung" },
    { href: "durchgaenge_zuordnung.php", label: "Riegen <-> Geräte <-> Durchgänge" },
    { href: "wettkaempfe_verwaltung.php", label: "Wettkämpfe" },
    { href: "vereine_verwaltung.php", label: "Vereine" },
    { href: "turner_verwaltung.php", label: "Turner" },
    { href: "wertungen.php", label: "Wertungen" }
  ];

  const getCurrentFile = () => {
    const file = window.location.pathname.split('/').pop();
    return file && file.length > 0 ? file : 'index.php';
  };

  const getStoredCollapsedState = () => {
    try {
      return localStorage.getItem(sidebarStorageKey) === '1';
    } catch (error) {
      return false;
    }
  };

  const storeCollapsedState = (collapsed) => {
    try {
      localStorage.setItem(sidebarStorageKey, collapsed ? '1' : '0');
    } catch (error) {
      // The menu still works for the current page when persistent storage is blocked.
    }
  };

  const buildNavList = (currentFile) => {
    return menuItems.map(item => {
      const isActive = item.href === currentFile;
      const activeClass = isActive ? ' active' : '';
      return `<li><a class="tw-nav-link${activeClass}" href="${item.href}">${item.label}</a></li>`;
    }).join('');
  };

  const ensureStyles = () => {
    if (document.getElementById('tw-menu-styles')) return;
    const style = document.createElement('style');
    style.id = 'tw-menu-styles';
    style.textContent = `
      :root {
        --tw-sidebar-width: 250px;
        --tw-sidebar-bg: #111827;
        --tw-sidebar-text: #e5e7eb;
        --tw-sidebar-muted: #9ca3af;
        --tw-sidebar-accent: #10b981;
      }
      body.tw-has-sidebar {
        padding-left: var(--tw-sidebar-width);
      }
      body.tw-has-sidebar.tw-sidebar-collapsed {
        padding-left: 0;
      }
      #tw-sidebar {
        position: fixed;
        inset: 0 auto 0 0;
        width: var(--tw-sidebar-width);
        background: var(--tw-sidebar-bg);
        color: var(--tw-sidebar-text);
        z-index: 1040;
        display: flex;
        flex-direction: column;
        padding: 16px;
        gap: 12px;
        overflow-y: auto;
      }
      body.tw-sidebar-collapsed #tw-sidebar {
        transform: translateX(-100%);
      }
      #tw-sidebar .tw-brand-wrap {
        min-height: 42px;
        padding-left: 48px;
      }
      #tw-sidebar .tw-brand {
        font-size: 1.1rem;
        font-weight: 700;
        letter-spacing: 0.02em;
      }
      #tw-sidebar .tw-sub {
        color: var(--tw-sidebar-muted);
        font-size: 0.85rem;
      }
      .tw-nav {
        list-style: none;
        padding: 0;
        margin: 8px 0 0 0;
        display: grid;
        gap: 6px;
      }
      .tw-nav-link {
        display: block;
        padding: 10px 12px;
        border-radius: 10px;
        color: var(--tw-sidebar-text);
        text-decoration: none;
        font-size: 0.95rem;
      }
      .tw-nav-link:hover {
        background: rgba(255,255,255,0.08);
        color: #fff;
      }
      .tw-nav-link.active {
        background: var(--tw-sidebar-accent);
        color: #052e1d;
        font-weight: 600;
      }
      #tw-menu-toggle {
        position: fixed;
        top: 12px;
        left: 12px;
        z-index: 1060;
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.18);
      }
      .tw-hamburger-lines,
      .tw-hamburger-lines::before,
      .tw-hamburger-lines::after {
        display: block;
        width: 18px;
        height: 2px;
        background: currentColor;
        border-radius: 999px;
      }
      .tw-hamburger-lines {
        position: relative;
      }
      .tw-hamburger-lines::before,
      .tw-hamburger-lines::after {
        content: "";
        position: absolute;
        left: 0;
      }
      .tw-hamburger-lines::before {
        top: -6px;
      }
      .tw-hamburger-lines::after {
        top: 6px;
      }
      .tw-offcanvas {
        background: var(--tw-sidebar-bg);
        color: var(--tw-sidebar-text);
      }
      .tw-offcanvas .tw-nav-link {
        color: var(--tw-sidebar-text);
      }
      .tw-offcanvas .tw-nav-link.active {
        background: var(--tw-sidebar-accent);
        color: #052e1d;
      }
      @media (max-width: 991px) {
        body.tw-has-sidebar {
          padding-left: 0;
        }
        #tw-sidebar {
          display: none;
        }
      }
      @media print {
        body.tw-has-sidebar {
          padding-left: 0 !important;
        }
        #tw-sidebar,
        #tw-menu-toggle,
        #tw-offcanvas {
          display: none !important;
        }
      }
    `;
    document.head.appendChild(style);
  };

  const setExpandedState = (expanded) => {
    const toggle = document.getElementById('tw-menu-toggle');
    if (!toggle) return;

    toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    toggle.setAttribute('title', expanded ? 'Menü ausblenden' : 'Menü einblenden');
    toggle.setAttribute('aria-label', expanded ? 'Menü ausblenden' : 'Menü einblenden');
  };

  const syncDesktopState = () => {
    if (!desktopMediaQuery.matches) {
      document.body.classList.remove('tw-sidebar-collapsed');
      setExpandedState(false);
      return;
    }

    const collapsed = getStoredCollapsedState();
    document.body.classList.toggle('tw-sidebar-collapsed', collapsed);
    setExpandedState(!collapsed);
  };

  const toggleMenu = () => {
    if (!desktopMediaQuery.matches) {
      const offcanvasElement = document.getElementById('tw-offcanvas');
      if (offcanvasElement && window.bootstrap && window.bootstrap.Offcanvas) {
        window.bootstrap.Offcanvas.getOrCreateInstance(offcanvasElement).toggle();
      }
      return;
    }

    const collapsed = !document.body.classList.contains('tw-sidebar-collapsed');
    document.body.classList.toggle('tw-sidebar-collapsed', collapsed);
    storeCollapsedState(collapsed);
    setExpandedState(!collapsed);
  };

  const initMenu = () => {
    const currentFile = getCurrentFile();
    ensureStyles();

    if (!document.body.classList.contains('tw-has-sidebar')) {
      document.body.classList.add('tw-has-sidebar');
    }

    if (!document.getElementById('tw-sidebar')) {
      const sidebar = document.createElement('aside');
      sidebar.id = 'tw-sidebar';
      sidebar.innerHTML = `
        <div class="tw-brand-wrap">
          <div class="tw-brand">Turnwettkampf Verwaltung</div>
        </div>
        <nav>
          <ul class="tw-nav">
            ${buildNavList(currentFile)}
          </ul>
        </nav>
      `;
      document.body.insertAdjacentElement('afterbegin', sidebar);
    }

    if (!document.getElementById('tw-menu-toggle')) {
      const btn = document.createElement('button');
      btn.id = 'tw-menu-toggle';
      btn.className = 'btn btn-dark';
      btn.type = 'button';
      btn.setAttribute('aria-controls', 'tw-sidebar tw-offcanvas');
      btn.innerHTML = '<span class="tw-hamburger-lines" aria-hidden="true"></span>';
      btn.addEventListener('click', toggleMenu);
      document.body.appendChild(btn);
    }

    if (!document.getElementById('tw-offcanvas')) {
      const offcanvas = document.createElement('div');
      offcanvas.className = 'offcanvas offcanvas-start tw-offcanvas';
      offcanvas.id = 'tw-offcanvas';
      offcanvas.tabIndex = -1;
      offcanvas.setAttribute('aria-labelledby', 'tw-offcanvas-label');
      offcanvas.innerHTML = `
        <div class="offcanvas-header">
          <h5 class="offcanvas-title" id="tw-offcanvas-label">Turnwettkampf Verwaltung</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Schließen"></button>
        </div>
        <div class="offcanvas-body">
          <ul class="tw-nav">
            ${buildNavList(currentFile)}
          </ul>
        </div>
      `;
      document.body.appendChild(offcanvas);
    }

    syncDesktopState();
    if (desktopMediaQuery.addEventListener) {
      desktopMediaQuery.addEventListener('change', syncDesktopState);
    } else {
      desktopMediaQuery.addListener(syncDesktopState);
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMenu);
  } else {
    initMenu();
  }
})();

