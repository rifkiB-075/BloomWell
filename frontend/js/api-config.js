// js/api-config.js
// Konfigurasi API terpusat — dipakai oleh SEMUA halaman frontend
// agar base URL konsisten dan mudah diubah.

const API_CONFIG = {
    // Set baseURL untuk override manual (tanpa trailing slash).
    // Biarkan null agar terdeteksi otomatis dari URL halaman.
    baseURL: null,

    // Mode deteksi:
    // - 'auto' : deteksi dari hostname & path halaman
    // - 'prod' : paksa http://bloomwell.test/backend/api
    mode: 'auto'
};

/**
 * Mendapatkan base URL backend API secara otomatis.
 * Mendukung:
 *  - akses via http://bloomwell.test/...
 *  - akses via http://localhost/BloomWell/...
 *  - akses via http://127.0.0.1/BloomWell/...
 *  - akses via port lain (mis. http://localhost:3000/...)
 */
function getApiBaseURL() {
    if (API_CONFIG.baseURL) {
        return API_CONFIG.baseURL.replace(/\/+$/, '');
    }

    if (API_CONFIG.mode === 'prod') {
        return 'http://bloomwell.test/backend/api';
    }

    const hostname = window.location.hostname;
    const protocol = window.location.protocol;
    const port = window.location.port ? ':' + window.location.port : '';
    const host = hostname + port;
    const path = window.location.pathname;

    // Via virtual host (mis. bloomwell.test)
    if (hostname === 'bloomwell.test') {
        return `${protocol}//bloomwell.test${port}/backend/api`;
    }

    // Via folder /BloomWell/ di localhost atau IP
    if (path.includes('/BloomWell/')) {
        return `${protocol}//${host}/BloomWell/backend/api`;
    }

    // Fallback: /backend/api di host yang sama
    return `${protocol}//${host}/backend/api`;
}

/**
 * Mendapatkan base URL server AI Node.js (server.js).
 * Chat AI dan analisis memakai server Express di port 3000,
 * sedangkan backend PHP dihandle Apache (port 80).
 */
function getChatAPIBase() {
    const override = API_CONFIG.chatAIURL;
    if (override) return override.replace(/\/+$/, '');
    return 'http://localhost:3000';
}

// Fungsi helper untuk fetch API
async function apiFetch(endpoint, options = {}) {
    const url = `${getApiBaseURL()}/${endpoint}`;
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
        },
    };

    const mergedOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers,
        },
    };

    try {
        const response = await fetch(url, mergedOptions);
        return await response.json();
    } catch (error) {
        console.error('API Error:', error);
        throw error;
    }
}
