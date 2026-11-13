(function () {
    'use strict';
    var button = document.querySelector('.cb-menu-toggle');
    var backdrop = document.querySelector('.cb-sidebar-backdrop');
    var sidebar = document.querySelector('.cb-sidebar');
    if (!button || !backdrop || !sidebar) return;

    var activeMenu = document.querySelector('.cb-sidebar .dropdown.active');
    if (activeMenu) {
        activeMenu.classList.add('open');
        var activeToggle = activeMenu.querySelector('.dropdown-toggle');
        if (activeToggle) activeToggle.setAttribute('aria-expanded', 'true');
    }

    function isMobile() {
        return window.innerWidth <= 767;
    }

    function updateButton(open) {
        button.setAttribute('aria-expanded', open ? 'true' : 'false');
        button.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
        button.title = open ? 'Tutup menu navigasi' : 'Buka menu navigasi';
    }

    function setMobileOpen(open) {
        document.body.classList.toggle('cb-sidebar-open', open);
        backdrop.hidden = !open;
        updateButton(open);
    }

    var mobile = isMobile();
    var userInteracted = false;
    button.addEventListener('click', function () {
        userInteracted = true;
        if (isMobile()) {
            setMobileOpen(!document.body.classList.contains('cb-sidebar-open'));
        } else {
            var collapsed = document.body.classList.toggle('cb-sidebar-collapsed');
            updateButton(!collapsed);
        }
    });
    backdrop.addEventListener('click', function () {
        setMobileOpen(false);
        button.focus();
    });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && isMobile() && document.body.classList.contains('cb-sidebar-open')) {
            setMobileOpen(false);
            button.focus();
        }
    });
    window.addEventListener('resize', function () {
        if (mobile === isMobile()) return;
        mobile = isMobile();
        document.body.classList.remove('cb-sidebar-open');
        backdrop.hidden = true;
        updateButton(!mobile && !document.body.classList.contains('cb-sidebar-collapsed'));
    });
    updateButton(!mobile && !document.body.classList.contains('cb-sidebar-collapsed'));

    document.addEventListener('DOMContentLoaded', function () {
        window.requestAnimationFrame(function () {
            window.requestAnimationFrame(function () {
                if (isMobile() || userInteracted || sidebar.contains(document.activeElement)) return;
                document.body.classList.add('cb-sidebar-collapsed');
                updateButton(false);
            });
        });
    });
}());
