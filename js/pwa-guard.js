
(function () {
    'use strict';

    function isStandalone() {
        return window.matchMedia &&
               window.matchMedia('(display-mode: standalone)').matches
               || window.navigator.standalone === true;
    }

    function forceMobileRoute() {
        if (!isStandalone()) return;

        const path = window.location.pathname || '';
        const appBase = '/index.php/apps/reinhardterp/';
        const mobileBase = appBase + 'mobile';

        // Already inside a mobile NextERP route -> do nothing.
        if (path === mobileBase || path.startsWith(mobileBase + '/')) return;

        // Only interfere with NextERP itself, never with another Nextcloud app.
        if (path.startsWith(appBase)) {
            window.location.replace(mobileBase + '?pwa=1');
        }
    }

    forceMobileRoute();
    window.addEventListener('pageshow', forceMobileRoute);
})();
