(function () {
    'use strict';

    var MOBILE_COLLAPSE_BREAKPOINT = 480;

    function initializeSidebarState() {
        var sidebar = document.getElementById('accordionSidebar');

        if (!sidebar) {
            return;
        }

        var autoCollapsed = false;
        var manualCollapsed = window.innerWidth >= MOBILE_COLLAPSE_BREAKPOINT
            && sidebar.classList.contains('toggled');

        function hideOpenMenus() {
            if (window.jQuery && typeof window.jQuery.fn.collapse === 'function') {
                window.jQuery(sidebar).find('.collapse').collapse('hide');
            }
        }

        function setCollapsed(collapsed) {
            sidebar.classList.toggle('toggled', collapsed);
            document.body.classList.toggle('sidebar-toggled', collapsed);
        }

        function syncWithViewport() {
            if (window.innerWidth < MOBILE_COLLAPSE_BREAKPOINT) {
                if (!manualCollapsed) {
                    setCollapsed(true);
                    autoCollapsed = true;
                    sidebar.dataset.autoCollapsed = 'true';
                    hideOpenMenus();
                }

                return;
            }

            if (autoCollapsed || sidebar.dataset.autoCollapsed === 'true') {
                setCollapsed(false);
                autoCollapsed = false;
                delete sidebar.dataset.autoCollapsed;
            }
        }

        document.querySelectorAll('#sidebarToggle, #sidebarToggleTop').forEach(function (button) {
            button.addEventListener('click', function () {
                window.setTimeout(function () {
                    manualCollapsed = sidebar.classList.contains('toggled');
                    autoCollapsed = false;
                    delete sidebar.dataset.autoCollapsed;
                }, 0);
            });
        });

        window.addEventListener('resize', syncWithViewport, { passive: true });
        window.addEventListener('pageshow', syncWithViewport);
        syncWithViewport();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSidebarState);
    } else {
        initializeSidebarState();
    }
})();
