// js/api-config.js
const API_CONFIG = {
    // Gunakan ini jika akses via localhost
    baseURL: '/BloomWell/backend/api',
    
    // Atau gunakan ini jika akses via domain
    // baseURL: 'http://bloomwell.test/backend/api',
    
    // Atau gunakan ini jika akses via IP
    // baseURL: 'http://localhost/BloomWell/backend/api',
};

// Fungsi helper untuk fetch API
async function apiFetch(endpoint, options = {}) {
    const url = `${API_CONFIG.baseURL}/${endpoint}`;
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