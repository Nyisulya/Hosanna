/**
 * Church Management System - Ultra-Fast Zero-Refresh SPA Engine
 * Provides instant page transitions, seamless form submissions, and zero-reload navigation.
 */

(function () {
    'use strict';

    if (!window.fetch || !window.history || !window.history.pushState) {
        return;
    }

    // Configure NProgress
    if (window.NProgress) {
        window.NProgress.configure({
            showSpinner: false,
            speed: 200,
            minimum: 0.25,
            trickleSpeed: 60
        });
    }

    const CACHE = new Map();
    const MAX_CACHE_SIZE = 30;

    /**
     * Resolve raw URL to internal application path
     */
    function getInternalUrl(rawUrl) {
        if (!rawUrl) return null;
        const trimmed = String(rawUrl).trim();
        if (!trimmed || trimmed === '#' || trimmed.startsWith('javascript:')) return null;

        try {
            const parsed = new URL(trimmed, window.location.href);
            const winHost = window.location.hostname;
            const targetHost = parsed.hostname;

            const isLocalHost = (targetHost === 'localhost' || targetHost === '127.0.0.1' || targetHost === '::1');
            const isWinLocal = (winHost === 'localhost' || winHost === '127.0.0.1' || winHost === '::1');

            if (parsed.origin === window.location.origin || (isLocalHost && isWinLocal)) {
                return parsed.pathname + parsed.search + parsed.hash;
            }
            return null;
        } catch (e) {
            return null;
        }
    }

    /**
     * Determine if a link should be handled by SPA Engine
     */
    function getEligibleLinkPath(link) {
        if (!link) return null;

        const rawHref = link.getAttribute('href');
        if (!rawHref) return null;

        const internalPath = getInternalUrl(rawHref);
        if (!internalPath) return null;

        // Skip same page hash jumps
        const currentPathNoHash = window.location.pathname + window.location.search;
        const targetPathNoHash = internalPath.split('#')[0];
        if (link.hash && currentPathNoHash === targetPathNoHash) return null;

        // Skip explicitly excluded links
        if (link.hasAttribute('data-no-pjax') || link.hasAttribute('download') || link.getAttribute('target') === '_blank') {
            return null;
        }

        // Skip AdminLTE / Bootstrap pushmenu, modal triggers, tab triggers
        if (link.hasAttribute('data-widget') && link.getAttribute('data-widget') === 'pushmenu') {
            return null;
        }
        if (link.hasAttribute('data-toggle') && ['dropdown', 'modal', 'collapse', 'tab', 'pill'].includes(link.getAttribute('data-toggle'))) {
            return null;
        }

        // Skip parent menus in sidebar that have a treeview sub-menu when clicking the accordion trigger
        const parentLi = link.closest('.nav-item');
        if (parentLi && parentLi.querySelector('.nav-treeview') && (rawHref === '#' || rawHref.startsWith('javascript:'))) {
            return null;
        }

        // Skip file downloads / media endpoints
        const path = targetPathNoHash.toLowerCase();
        const forbiddenExtensions = ['.pdf', '.xlsx', '.xls', '.csv', '.zip', '.png', '.jpg', '.jpeg', '.webp'];
        if (forbiddenExtensions.some(ext => path.endsWith(ext))) return null;
        if (path.includes('/download') || path.includes('receipt_pdf') || path.includes('id-card-download')) return null;

        // Skip logout
        if (path.includes('logout')) return null;

        return internalPath;
    }

    /**
     * Determine if a form should be handled by SPA Engine
     */
    function getEligibleFormPath(form) {
        if (!form) return null;

        const rawAction = form.getAttribute('action') || window.location.href;
        const internalPath = getInternalUrl(rawAction);
        if (!internalPath) return null;

        if (form.hasAttribute('data-no-pjax') || form.getAttribute('target') === '_blank') {
            return null;
        }

        const path = internalPath.toLowerCase();
        if (path.includes('logout') || path.includes('export') || path.includes('download')) {
            return null;
        }

        return internalPath;
    }

    /**
     * Execute any <script> tags within the given element safely
     */
    function executeScripts(container) {
        const scripts = container.querySelectorAll('script');
        scripts.forEach(oldScript => {
            if (oldScript.type && !['text/javascript', 'module', ''].includes(oldScript.type.toLowerCase())) {
                return;
            }

            if (oldScript.src) {
                if (!document.querySelector(`script[src="${oldScript.src}"]`)) {
                    const s = document.createElement('script');
                    Array.from(oldScript.attributes).forEach(attr => s.setAttribute(attr.name, attr.value));
                    document.head.appendChild(s);
                }
            } else if (oldScript.textContent.trim()) {
                try {
                    const fn = new Function('$', 'jQuery', oldScript.textContent);
                    fn(window.jQuery, window.jQuery);
                } catch (err) {
                    // Fallback to direct script injection
                    try {
                        const newScript = document.createElement('script');
                        newScript.textContent = '(function(){\n' + oldScript.textContent + '\n})();';
                        document.body.appendChild(newScript);
                        newScript.remove();
                    } catch (e) {}
                }
            }
        });
    }

    /**
     * Re-initialize common UI plugins
     */
    function reinitializePlugins() {
        if (window.jQuery) {
            const $ = window.jQuery;
            
            if ($.fn.tooltip) {
                $('[data-toggle="tooltip"]').tooltip({ boundary: 'window' });
            }
            if ($.fn.popover) {
                $('[data-toggle="popover"]').popover();
            }
            if ($.fn.select2) {
                $('.select2').select2({ theme: 'bootstrap4' });
            }

            $(document).trigger('ready');
            $(window).trigger('load');
        }

        document.dispatchEvent(new CustomEvent('spa:contentLoaded', { bubbles: true }));
    }

    /**
     * Update active sidebar navigation links
     */
    function updateSidebarActive(targetUrl) {
        try {
            const urlObj = new URL(targetUrl, window.location.href);
            const targetPath = urlObj.pathname;

            document.querySelectorAll('.main-sidebar .nav-sidebar .nav-link').forEach(link => {
                const rawHref = link.getAttribute('href');
                if (!rawHref || rawHref === '#') return;

                try {
                    const linkPath = new URL(rawHref, window.location.href).pathname;
                    
                    if (linkPath === targetPath || (linkPath !== '/' && linkPath !== '/dashboard' && targetPath.startsWith(linkPath))) {
                        link.classList.add('active');
                        const parentTree = link.closest('.nav-treeview');
                        if (parentTree) {
                            const parentItem = parentTree.closest('.nav-item');
                            if (parentItem) parentItem.classList.add('menu-open');
                        }
                    } else {
                        link.classList.remove('active');
                    }
                } catch (e) {}
            });
        } catch (e) {}
    }

    /**
     * Load page content seamlessly via fetch
     */
    async function loadPage(targetUrl, pushState = true, isBack = false) {
        const wrapper = document.querySelector('.content-wrapper') || document.querySelector('.wrapper');
        if (!wrapper) {
            window.location.href = targetUrl;
            return;
        }

        if (window.NProgress) window.NProgress.start();
        wrapper.classList.add('spa-loading');

        try {
            let html = null;
            if (CACHE.has(targetUrl)) {
                html = CACHE.get(targetUrl);
            } else {
                const response = await fetch(targetUrl, {
                    headers: {
                        'Accept': 'text/html, application/xhtml+xml',
                        'X-PJAX': 'true',
                        'X-SPA': 'true'
                    }
                });

                if (!response.ok) {
                    window.location.href = targetUrl;
                    return;
                }

                html = await response.text();
                if (CACHE.size >= MAX_CACHE_SIZE) {
                    const firstKey = CACHE.keys().next().value;
                    CACHE.delete(firstKey);
                }
                CACHE.set(targetUrl, html);
            }

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');

            const newWrapper = doc.querySelector('.content-wrapper') || doc.querySelector('.wrapper');
            if (!newWrapper) {
                window.location.href = targetUrl;
                return;
            }

            // Update title
            if (doc.title) {
                document.title = doc.title;
            }

            // Update DOM content
            wrapper.innerHTML = newWrapper.innerHTML;

            // Update URL in browser history
            if (pushState && !isBack) {
                window.history.pushState({ url: targetUrl }, doc.title || '', targetUrl);
            }

            // Update active menu link
            updateSidebarActive(targetUrl);

            // Scroll to top
            if (!isBack) {
                window.scrollTo({ top: 0, behavior: 'instant' });
            }

            // Execute scripts in new content
            executeScripts(wrapper);

            // Re-init plugins
            reinitializePlugins();

        } catch (error) {
            console.warn('[SPA Engine] Navigation error:', error);
            window.location.href = targetUrl;
        } finally {
            wrapper.classList.remove('spa-loading');
            if (window.NProgress) window.NProgress.done();
        }
    }

    /**
     * Handle Form Submission via AJAX
     */
    async function submitForm(form, submitBtn) {
        const wrapper = document.querySelector('.content-wrapper') || document.querySelector('.wrapper');
        if (!wrapper) {
            form.submit();
            return;
        }

        if (window.NProgress) window.NProgress.start();
        wrapper.classList.add('spa-loading');

        let originalBtnHtml = '';
        if (submitBtn) {
            originalBtnHtml = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm mr-1" role="status" aria-hidden="true"></span> Inapakia...';
        }

        try {
            const action = form.action || window.location.href;
            const method = (form.method || 'POST').toUpperCase();
            const formData = new FormData(form);

            let fetchOptions = {
                method: method,
                headers: {
                    'Accept': 'text/html, application/xhtml+xml',
                    'X-PJAX': 'true',
                    'X-SPA': 'true'
                }
            };

            if (method === 'GET') {
                const searchParams = new URLSearchParams(formData).toString();
                const targetUrl = action.split('?')[0] + (searchParams ? '?' + searchParams : '');
                await loadPage(targetUrl, true);
                return;
            } else {
                fetchOptions.body = formData;
            }

            const response = await fetch(action, fetchOptions);
            const finalUrl = response.url || action;
            const html = await response.text();

            CACHE.clear();

            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newWrapper = doc.querySelector('.content-wrapper') || doc.querySelector('.wrapper');

            if (newWrapper) {
                if (doc.title) document.title = doc.title;
                wrapper.innerHTML = newWrapper.innerHTML;
                window.history.pushState({ url: finalUrl }, doc.title || '', finalUrl);
                updateSidebarActive(finalUrl);
                window.scrollTo({ top: 0, behavior: 'smooth' });
                executeScripts(wrapper);
                reinitializePlugins();
            } else {
                window.location.href = finalUrl;
            }

        } catch (error) {
            console.error('[SPA Engine] Form submit error:', error);
            window.location.href = form.action;
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnHtml;
            }
            wrapper.classList.remove('spa-loading');
            if (window.NProgress) window.NProgress.done();
        }
    }

    // Prefetching on Hover
    document.addEventListener('mouseover', function (e) {
        const link = e.target.closest('a');
        if (!link) return;

        const path = getEligibleLinkPath(link);
        if (!path || CACHE.has(path)) return;

        fetch(path, { headers: { 'Accept': 'text/html', 'X-SPA-Prefetch': 'true' } })
            .then(res => res.text())
            .then(html => {
                if (CACHE.size >= MAX_CACHE_SIZE) {
                    const firstKey = CACHE.keys().next().value;
                    CACHE.delete(firstKey);
                }
                CACHE.set(path, html);
            })
            .catch(() => {});
    }, { passive: true });

    // Global Link Click Listener (Capture Phase)
    document.addEventListener('click', function (e) {
        const link = e.target.closest('a');
        if (!link) return;

        const targetPath = getEligibleLinkPath(link);
        if (targetPath) {
            e.preventDefault();
            e.stopPropagation();
            loadPage(targetPath, true);
        }
    }, true);

    // Global Form Submit Listener (Capture Phase)
    document.addEventListener('submit', function (e) {
        const form = e.target;
        const targetPath = getEligibleFormPath(form);
        if (targetPath) {
            e.preventDefault();
            e.stopPropagation();
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]') || document.activeElement;
            submitForm(form, submitBtn);
        }
    }, true);

    // Handle Browser Back/Forward buttons
    window.addEventListener('popstate', function (e) {
        const url = (e.state && e.state.url) ? e.state.url : window.location.href;
        loadPage(url, false, true);
    });

    document.addEventListener('DOMContentLoaded', function () {
        reinitializePlugins();
    });

    window.SpaEngine = {
        load: loadPage,
        reinit: reinitializePlugins
    };
})();
