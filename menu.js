(() => {
  const header = document.querySelector('.site-header');
  const toggle = header?.querySelector('.menu-toggle');
  const nav = header?.querySelector('.top-nav');

  if (!header || !toggle || !nav) return;

  const setMenuState = (isOpen) => {
    header.classList.toggle('menu-open', isOpen);
    toggle.setAttribute('aria-expanded', String(isOpen));
    toggle.setAttribute('aria-label', isOpen ? 'ปิดเมนู' : 'เปิดเมนู');
  };

  toggle.addEventListener('click', () => {
    setMenuState(!header.classList.contains('menu-open'));
  });

  nav.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', () => setMenuState(false));

    const linkFile = link.pathname.split('/').pop() || 'inbex.html';
    const currentFile = window.location.pathname.split('/').pop() || 'inbex.html';
    if (linkFile.toLowerCase() === currentFile.toLowerCase()) {
      link.classList.add('active');
      link.setAttribute('aria-current', 'page');
    }
  });

  document.addEventListener('click', (event) => {
    if (!header.contains(event.target)) setMenuState(false);
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      setMenuState(false);
      toggle.focus();
    }
  });

  window.addEventListener('resize', () => {
    if (window.innerWidth > 760) setMenuState(false);
  });
})();
