/**
 * Hosanna Church - Progressive Web App (PWA) Install Prompt Engine
 * Prompts mobile & desktop users to install the church app on their home screen.
 */

(function () {
    'use strict';

    // Register Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js')
                .then(function (reg) {
                    console.log('[PWA] Service Worker registered successfully.');
                })
                .catch(function (err) {
                    console.warn('[PWA] Service Worker registration failed:', err);
                });
        });
    }

    let deferredPrompt = null;
    const isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    const isIOS = /iphone|ipad|ipod/.test(navigator.userAgent.toLowerCase()) && !window.MSStream;

    // Listen for beforeinstallprompt (Android / Chrome / Edge)
    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;

        // Show install button in sidebar / navbar if present
        document.querySelectorAll('.pwa-install-btn').forEach(btn => {
            btn.classList.remove('hidden');
            btn.style.display = 'block';
        });

        // Automatically show popup if not dismissed recently
        const dismissedAt = localStorage.getItem('pwa_dismissed_at');
        const now = Date.now();
        const oneDay = 24 * 60 * 60 * 1000;

        if (!dismissedAt || (now - parseInt(dismissedAt, 10)) > oneDay) {
            setTimeout(showInstallPopup, 2000);
        }
    });

    // Check if app was installed
    window.addEventListener('appinstalled', function () {
        deferredPrompt = null;
        hideInstallPopup();
        console.log('[PWA] App was installed successfully.');
    });

    /**
     * Trigger Native or Custom Install Prompt
     */
    window.showPwaInstallPrompt = function () {
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function (choiceResult) {
                if (choiceResult.outcome === 'accepted') {
                    console.log('[PWA] User accepted the install prompt');
                }
                deferredPrompt = null;
                hideInstallPopup();
            });
        } else if (isIOS && !isStandalone) {
            showIOSGuideModal();
        } else if (isStandalone) {
            alert('App hii tayari imewekwa kwenye kifaa chako!');
        } else {
            showInstallPopup();
        }
    };

    /**
     * Build & Display the PWA Install Bottom Sheet / Popup
     */
    function showInstallPopup() {
        if (isStandalone) return;
        if (document.getElementById('pwaInstallModal')) {
            document.getElementById('pwaInstallModal').classList.remove('hidden');
            return;
        }

        const modalHtml = `
        <div id="pwaInstallModal" style="position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%); z-index: 99999; width: 92%; max-width: 440px; animation: slideUp 0.4s ease-out;">
            <div style="background: linear-gradient(135deg, #1e3a8a 0%, #1e1b4b 100%); color: white; border-radius: 20px; padding: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.15); backdrop-filter: blur(10px);">
                <div style="display: flex; align-items: center; gap: 14px; margin-bottom: 14px;">
                    <div style="width: 52px; height: 52px; border-radius: 14px; background: white; display: flex; align-items: center; justify-content: center; box-shadow: 0 6px 16px rgba(0,0,0,0.25); flex-shrink: 0;">
                        <img src="/images/icons/icon.svg" alt="App Icon" style="width: 40px; height: 40px; border-radius: 10px;" onerror="this.src='/images/sda-logo.png'">
                    </div>
                    <div style="flex-grow: 1;">
                        <h4 style="margin: 0; font-size: 16px; font-weight: 700; color: #ffffff; line-height: 1.2;">Weka App ya Kanisa Kwenye Simu</h4>
                        <p style="margin: 4px 0 0; font-size: 12px; color: #93c5fd; line-height: 1.3;">Tumia kama application ya simu kwa ufunguzi wa haraka!</p>
                    </div>
                    <button onclick="window.dismissPwaPopup()" style="background: rgba(255,255,255,0.1); border: none; color: #cbd5e1; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 14px;">✕</button>
                </div>
                
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <button onclick="window.showPwaInstallPrompt()" style="flex: 1; background: linear-gradient(90deg, #22c55e, #16a34a); color: white; border: none; padding: 12px 16px; border-radius: 12px; font-weight: 700; font-size: 13px; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; box-shadow: 0 6px 14px rgba(34,197,94,0.35);">
                        <i class="fas fa-download"></i> Weka Sasa (Install App)
                    </button>
                    <button onclick="window.dismissPwaPopup()" style="background: rgba(255,255,255,0.12); color: #e2e8f0; border: 1px solid rgba(255,255,255,0.2); padding: 12px 14px; border-radius: 12px; font-weight: 600; font-size: 13px; cursor: pointer;">
                        Baadaye
                    </button>
                </div>
            </div>
        </div>
        <style>
            @keyframes slideUp {
                from { opacity: 0; transform: translate(-50%, 40px); }
                to { opacity: 1; transform: translate(-50%, 0); }
            }
        </style>
        `;

        const wrapper = document.createElement('div');
        wrapper.innerHTML = modalHtml;
        document.body.appendChild(wrapper);
    }

    /**
     * Show iOS Safari Specific "Add to Home Screen" Instructions
     */
    function showIOSGuideModal() {
        hideInstallPopup();
        const iosModal = `
        <div id="iosInstallModal" style="position: fixed; inset: 0; background: rgba(0,0,0,0.7); z-index: 99999; display: flex; align-items: flex-end; justify-content: center;">
            <div style="background: white; border-radius: 24px 24px 0 0; padding: 24px; max-width: 500px; width: 100%; text-align: center; color: #1e293b; box-shadow: 0 -10px 30px rgba(0,0,0,0.3);">
                <div style="width: 40px; height: 5px; background: #cbd5e1; border-radius: 10px; margin: 0 auto 16px;"></div>
                <h3 style="font-size: 18px; font-weight: 800; margin-bottom: 8px; color: #0f172a;">Weka kwenye iPhone / iPad</h3>
                <p style="font-size: 13px; color: #64748b; margin-bottom: 20px;">Fuata hatua hizi mbili rahisi ili kuweka kama App:</p>
                
                <div style="background: #f8fafc; border-radius: 16px; padding: 16px; text-align: left; margin-bottom: 20px; border: 1px solid #e2e8f0; font-size: 14px; line-height: 1.6;">
                    <p style="margin-bottom: 10px;"><strong>1.</strong> Bofya kitufe cha <strong>Shiriki (Share)</strong> <i class="fas fa-share-square" style="color: #2563eb;"></i> chini ya kivinjari chako cha Safari.</p>
                    <p style="margin: 0;"><strong>2.</strong> Shuka chini kisha chagua <strong>'Add to Home Screen' (Weka kwenye Skrini Kuu)</strong> <i class="fas fa-plus-square" style="color: #2563eb;"></i>.</p>
                </div>
                
                <button onclick="document.getElementById('iosInstallModal').remove()" style="width: 100%; background: #2563eb; color: white; border: none; padding: 14px; border-radius: 14px; font-weight: 700; font-size: 14px; cursor: pointer;">
                    Nimeelewa, Asante!
                </button>
            </div>
        </div>
        `;
        const wrapper = document.createElement('div');
        wrapper.innerHTML = iosModal;
        document.body.appendChild(wrapper);
    }

    window.dismissPwaPopup = function () {
        localStorage.setItem('pwa_dismissed_at', Date.now().toString());
        hideInstallPopup();
    };

    function hideInstallPopup() {
        const modal = document.getElementById('pwaInstallModal');
        if (modal) modal.remove();
    }

    // Auto check for mobile visit after login
    document.addEventListener('DOMContentLoaded', function () {
        if (!isStandalone) {
            setTimeout(function () {
                if (deferredPrompt || isIOS) {
                    showInstallPopup();
                }
            }, 2500);
        }
    });
})();
