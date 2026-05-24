{{-- ════════════════════════════════════════════════════════════════════════════
     SYGRAD — QR Scanner View
     Loaded inside the "Kamera ile QR Okut" modal action on the Saha dashboard.
     Robust error handling for laptop testing, auto-fallback, and premium Tailwind v3 styles.
 ════════════════════════════════════════════════════════════════════════════ --}}

<div id="sygrad-scanner-root" class="w-full max-w-md mx-auto p-4 text-center">
    
    {{-- Status/Badge Alert --}}
    <div class="mb-4">
        <div id="sygrad-status-badge" class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-gray-50 border-gray-100 text-gray-500 dark:bg-gray-900/30 dark:border-gray-800 transition-all duration-300">
            <span id="sygrad-status-dot" class="w-2 h-2 rounded-full bg-gray-500 animate-pulse"></span>
            <span id="sygrad-status-text">{{ __('system.kamera_araniyor') }}</span>
        </div>
    </div>

    {{-- Viewfinder Frame --}}
    <div class="relative w-full aspect-square rounded-2xl overflow-hidden bg-gray-950 shadow-lg ring-1 ring-gray-950/10 dark:ring-white/10">
        {{-- Viewfinder corners --}}
        <div class="absolute top-4 left-4 w-6 h-6 border-t-4 border-l-4 border-amber-500 rounded-tl-md z-10 pointer-events-none"></div>
        <div class="absolute top-4 right-4 w-6 h-6 border-t-4 border-r-4 border-amber-500 rounded-tr-md z-10 pointer-events-none"></div>
        <div class="absolute bottom-4 left-4 w-6 h-6 border-b-4 border-l-4 border-amber-500 rounded-bl-md z-10 pointer-events-none"></div>
        <div class="absolute bottom-4 right-4 w-6 h-6 border-b-4 border-r-4 border-amber-500 rounded-br-md z-10 pointer-events-none"></div>

        {{-- Laser scanner line --}}
        <div id="sygrad-laser" class="absolute left-6 right-6 top-[15%] h-0.5 bg-gradient-to-r from-transparent via-amber-500 to-transparent rounded shadow-[0_0_8px_rgba(245,158,11,0.8)] z-10 animate-laser"></div>

        {{-- HTML5 QR Code element --}}
        <div id="sygrad-reader" class="w-full h-full object-cover"></div>

        {{-- Loading Spinner --}}
        <div id="sygrad-loading-spinner" class="absolute inset-0 flex flex-col items-center justify-center bg-gray-950/80 z-20 transition-opacity duration-300">
            <svg class="animate-spin h-10 w-10 text-amber-500" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <span class="text-xs text-gray-400 mt-3 font-medium">{{ __('system.kamera_araniyor') }}</span>
        </div>
    </div>

    {{-- Error State (hidden by default) --}}
    <div id="sygrad-error-box" class="hidden rounded-xl p-4 bg-danger-50 dark:bg-danger-950/20 border border-danger-200 dark:border-danger-900/50 flex flex-col items-center gap-2 mt-4 text-center text-sm font-medium text-danger-600 dark:text-danger-400">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
        </svg>
        <span id="sygrad-error-text">
            {{ __('system.kamera_baslatilamadi_manuel') }}
        </span>
    </div>

    {{-- Description / Manual Helper Text --}}
    <div class="mt-4">
        <p id="sygrad-hint-text" class="text-xs font-medium text-gray-500 dark:text-gray-400 leading-relaxed">
            {{ __('system.kamera_taraniyor_hizala') }}
        </p>
    </div>

</div>

<style>
@keyframes sygrad-laser {
    0%   { top: 15%; opacity: 0.9; }
    50%  { top: 85%; opacity: 1; }
    100% { top: 15%; opacity: 0.9; }
}
.animate-laser {
    animation: sygrad-laser 2s ease-in-out infinite;
}

/* Hide default html5-qrcode UI elements */
#sygrad-reader > img,
#sygrad-reader__dashboard,
#sygrad-reader__dashboard_section,
#sygrad-reader__dashboard_section_csr,
#sygrad-reader__header_message,
#sygrad-reader select,
#sygrad-reader__filescan_input,
#sygrad-reader__camera_permission_button,
#sygrad-reader__scan_region > img {
    display: none !important;
}
#sygrad-reader {
    border: none !important;
}
#sygrad-reader video {
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    display: block !important;
}
</style>

<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
(function () {
    'use strict';

    let html5QrCode = null;
    let scanActive  = false;
    let redirecting = false;

    const statusDot       = document.getElementById('sygrad-status-dot');
    const statusText      = document.getElementById('sygrad-status-text');
    const statusBadge     = document.getElementById('sygrad-status-badge');
    const laser           = document.getElementById('sygrad-laser');
    const hintText        = document.getElementById('sygrad-hint-text');
    const loadingSpinner  = document.getElementById('sygrad-loading-spinner');
    const errorBox        = document.getElementById('sygrad-error-box');
    const errorText       = document.getElementById('sygrad-error-text');

    function setStatus(text, type) {
        const states = {
            init:     ['text-gray-500 bg-gray-50 border-gray-100 dark:bg-gray-900/30 dark:border-gray-800', 'bg-gray-500'],
            scanning: ['text-amber-600 bg-amber-50 border-amber-100 dark:bg-amber-950/30 dark:border-amber-900/30', 'bg-amber-600 dark:bg-amber-400'],
            success:  ['text-emerald-600 bg-emerald-50 border-emerald-100 dark:bg-emerald-950/30 dark:border-emerald-900/30', 'bg-emerald-600'],
            error:    ['text-danger-600 bg-danger-50 border-danger-100 dark:bg-danger-950/30 dark:border-danger-900/30', 'bg-danger-600'],
        };
        const [badgeClass, dotClass] = states[type] || states.init;
        
        if (statusText) statusText.innerText = text;
        if (statusBadge && statusBadge.style.display !== 'none') {
            statusBadge.className = `inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold border transition-all duration-300 ${badgeClass}`;
        }
        if (statusDot) {
            statusDot.className = `w-2 h-2 rounded-full animate-pulse ${dotClass}`;
        }
    }

    function showError(errName) {
        if (loadingSpinner) loadingSpinner.style.display = 'none';
        if (laser) laser.style.display = 'none';
        if (statusBadge) statusBadge.style.display = 'none';
        
        if (errorBox) {
            errorBox.classList.remove('hidden');
        }

        let msg = '{{ __('system.kamera_baslatilamadi_manuel') }}';
        if (errorText) {
            errorText.innerText = msg;
        }

        setStatus(msg, 'error');
        if (hintText) hintText.innerText = '{{ __('system.manual_code_helper') }}';
    }

    function onScanSuccess(decodedText) {
        if (redirecting) return;
        redirecting = true;

        if (laser) laser.style.display = 'none';
        setStatus('{{ __('system.kod_okundu') }}' + decodedText, 'success');
        if (hintText) hintText.innerText = '{{ __('system.yonlendiriliyor') }}';

        stopScanner().finally(function () {
            Livewire.dispatch('qr_code_scanned', { qrCode: decodedText });
        });
    }

    function onScanFailure(error) {
        // Frame-by-frame scanner errors are normal and can be safely ignored
    }

    function stopScanner() {
        if (html5QrCode && scanActive) {
            scanActive = false;
            return html5QrCode.stop().catch(function (err) {
                console.warn('[SYGRAD QR] Stop error:', err);
            });
        }
        return Promise.resolve();
    }

    function startWithConstraint(cameraConstraint, cameras) {
        html5QrCode = new Html5Qrcode('sygrad-reader');

        const config = {
            fps: 15,
            qrbox: function (viewfinderWidth, viewfinderHeight) {
                const side = Math.min(viewfinderWidth, viewfinderHeight) * 0.70;
                return { width: side, height: side };
            },
            aspectRatio: 1.0,
            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE],
        };

        html5QrCode.start(cameraConstraint, config, onScanSuccess, onScanFailure)
            .then(function () {
                scanActive = true;
                if (loadingSpinner) loadingSpinner.style.display = 'none';
                setStatus('{{ __('system.kamera_taraniyor_hizala') }}', 'scanning');
                if (hintText) hintText.innerText = '{{ __('system.kamera_taraniyor_hizala') }}';
                if (laser)    laser.style.display = 'block';
            })
            .catch(function (err) {
                console.warn('[SYGRAD QR] Camera start failed:', err);
                
                // Fallback attempt: if environmental camera failed, try using user-facing front camera
                if (cameraConstraint && cameraConstraint.facingMode === 'environment') {
                    console.info('[SYGRAD QR] Retrying with front-facing camera...');
                    startWithConstraint({ facingMode: 'user' }, cameras);
                } else if (cameras && cameras.length > 0) {
                    // Final fallback: try to use the first listed camera ID directly
                    console.info('[SYGRAD QR] Retrying with first available camera ID:', cameras[0].id);
                    startWithConstraint(cameras[0].id, null);
                } else {
                    showError(err.name || 'CameraError');
                }
            });
    }

    function initScanner() {
        const readerEl = document.getElementById('sygrad-reader');
        if (!readerEl || scanActive) return;

        setStatus('{{ __('system.kamera_araniyor') }}', 'init');

        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            showError('NotFoundError');
            return;
        }

        Html5Qrcode.getCameras()
            .then(function (cameras) {
                if (!cameras || cameras.length === 0) {
                    showError('NotFoundError');
                    return;
                }
                
                // Start with ideal environmental camera, pass list of cameras for fallback
                startWithConstraint({ facingMode: { ideal: 'environment' } }, cameras);
            })
            .catch(function (err) {
                console.error('[SYGRAD QR] Error getting cameras:', err);
                showError(err.name || 'CameraError');
            });
    }

    // Bootstrap the scanner slightly after rendering
    setTimeout(initScanner, 300);

    // Watch for modal dismissal to cleanup and turn off camera
    const observer = new MutationObserver(function (mutations) {
        for (const mutation of mutations) {
            for (const node of mutation.removedNodes) {
                if (node.nodeType === 1) {
                    const root = document.getElementById('sygrad-scanner-root');
                    if (!root) {
                        stopScanner();
                        observer.disconnect();
                        return;
                    }
                }
            }
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });
})();
</script>
