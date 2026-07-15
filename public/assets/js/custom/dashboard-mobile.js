(function () {
    'use strict';

    function initMobileSidebar() {
        var sidebar = document.getElementById('kt_app_sidebar');
        var toggle = document.getElementById('kt_app_sidebar_mobile_toggle');
        var closeButton = document.getElementById('kt_app_sidebar_close');

        if (! sidebar || ! toggle) {
            return;
        }

        var mobileMedia = window.matchMedia('(max-width: 991.98px)');
        var drawer = null;

        if (typeof KTDrawer !== 'undefined') {
            drawer = KTDrawer.getInstance(sidebar) || new KTDrawer(sidebar);
        }

        function isShown() {
            if (drawer && typeof drawer.isShown === 'function') {
                return drawer.isShown();
            }

            return sidebar.classList.contains('drawer-on');
        }

        function getFocusableElements() {
            return Array.prototype.slice.call(sidebar.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )).filter(function (element) {
                return element.offsetWidth > 0 || element.offsetHeight > 0;
            });
        }

        function focusToggle() {
            if (! mobileMedia.matches || ! document.contains(toggle)) {
                return;
            }

            window.requestAnimationFrame(function () {
                toggle.focus({ preventScroll: true });
            });
        }

        function syncAccessibility(forceShown) {
            var shown = typeof forceShown === 'boolean' ? forceShown : isShown();
            var mobile = mobileMedia.matches;

            toggle.setAttribute('aria-expanded', mobile && shown ? 'true' : 'false');
            sidebar.setAttribute('aria-hidden', mobile && ! shown ? 'true' : 'false');

            if (mobile) {
                sidebar.setAttribute('role', 'dialog');
                sidebar.setAttribute('aria-modal', 'true');
                sidebar.setAttribute('aria-label', 'Menu navigasi');
            } else {
                sidebar.removeAttribute('role');
                sidebar.removeAttribute('aria-modal');
                sidebar.setAttribute('aria-label', 'Menu navigasi utama');
            }
        }

        function hideDrawer(returnFocus) {
            if (! mobileMedia.matches) {
                return;
            }

            if (drawer && typeof drawer.hide === 'function') {
                drawer.hide();
            } else {
                sidebar.classList.remove('drawer-on');
                document.body.removeAttribute('data-kt-drawer');
                document.body.removeAttribute('data-kt-drawer-app-sidebar');
                document.querySelectorAll('.drawer-overlay').forEach(function (overlay) {
                    overlay.remove();
                });
            }

            syncAccessibility(false);

            if (returnFocus) {
                focusToggle();
            }
        }

        if (drawer && typeof drawer.on === 'function') {
            drawer.on('kt.drawer.shown', function () {
                syncAccessibility(true);

                if (closeButton && mobileMedia.matches) {
                    window.requestAnimationFrame(function () {
                        closeButton.focus({ preventScroll: true });
                    });
                }
            });

            drawer.on('kt.drawer.after.hidden', function () {
                syncAccessibility(false);
                focusToggle();
            });
        }

        toggle.addEventListener('click', function () {
            window.setTimeout(syncAccessibility, 0);
        });

        if (closeButton) {
            closeButton.addEventListener('click', function () {
                /*
                 * Metronic menangani data-kt-drawer-close pada fase bubble.
                 * Timeout ini menjadi fallback bila instance terlambat dibuat.
                 */
                window.setTimeout(function () {
                    if (isShown()) {
                        hideDrawer(true);
                    } else {
                        syncAccessibility(false);
                        focusToggle();
                    }
                }, 0);
            });
        }

        document.addEventListener('keydown', function (event) {
            if (! mobileMedia.matches || ! isShown()) {
                return;
            }

            if (event.key === 'Escape') {
                event.preventDefault();
                hideDrawer(true);
                return;
            }

            if (event.key !== 'Tab') {
                return;
            }

            var focusableElements = getFocusableElements();
            if (focusableElements.length === 0) {
                event.preventDefault();
                return;
            }

            var firstElement = focusableElements[0];
            var lastElement = focusableElements[focusableElements.length - 1];
            var activeElement = document.activeElement;

            if (event.shiftKey && (activeElement === firstElement || ! sidebar.contains(activeElement))) {
                event.preventDefault();
                lastElement.focus();
                return;
            }

            if (! event.shiftKey && activeElement === lastElement) {
                event.preventDefault();
                firstElement.focus();
            }
        }, true);

        var touchStartX = 0;
        var touchStartY = 0;

        sidebar.addEventListener('touchstart', function (event) {
            var touch = event.changedTouches[0];
            touchStartX = touch ? touch.clientX : 0;
            touchStartY = touch ? touch.clientY : 0;
        }, { passive: true });

        sidebar.addEventListener('touchend', function (event) {
            if (! mobileMedia.matches || ! isShown()) {
                return;
            }

            var touch = event.changedTouches[0];
            if (! touch) {
                return;
            }

            var deltaX = touch.clientX - touchStartX;
            var deltaY = touch.clientY - touchStartY;

            if (deltaX < -70 && Math.abs(deltaX) > Math.abs(deltaY)) {
                hideDrawer(true);
            }
        }, { passive: true });

        var handleViewportChange = function () {
            window.setTimeout(function () {
                syncAccessibility();
            }, 50);
        };

        if (typeof mobileMedia.addEventListener === 'function') {
            mobileMedia.addEventListener('change', handleViewportChange);
        } else {
            mobileMedia.addListener(handleViewportChange);
        }

        window.addEventListener('resize', handleViewportChange);
        syncAccessibility();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initMobileSidebar);
    } else {
        initMobileSidebar();
    }
})();
