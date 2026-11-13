(function () {
    'use strict';
    var button = document.querySelector('.cb-menu-toggle');
    var backdrop = document.querySelector('.cb-sidebar-backdrop');
    if (!button || !backdrop) return;

    var activeMenu = document.querySelector('.cb-sidebar .dropdown.active');
    if (activeMenu) {
        activeMenu.classList.add('open');
        var activeToggle = activeMenu.querySelector('.dropdown-toggle');
        if (activeToggle) activeToggle.setAttribute('aria-expanded', 'true');
    }

    function setOpen(open) {
        document.body.classList.toggle('cb-sidebar-open', open);
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        button.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        backdrop.hidden = !open;
    }

    button.addEventListener('click', function () {
        setOpen(!document.body.classList.contains('cb-sidebar-open'));
    });
    backdrop.addEventListener('click', function () {
        setOpen(false);
        button.focus();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && document.body.classList.contains('cb-sidebar-open')) {
            setOpen(false);
            button.focus();
        }
    });
    window.addEventListener('resize', function () {
        if (window.innerWidth > 767) setOpen(false);
    });
}());
