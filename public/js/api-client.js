/**
 * API Client Utility
 * Automatically attaches org_slug header to API requests
 */

// Helper function to make API calls with org_slug header
window.apiRequest = async function(url, options = {}) {
    const orgSlug = localStorage.getItem('org_slug');
    const accessToken = localStorage.getItem('access_token');
    
    // Merge headers
    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        ...options.headers
    };
    
    // Add org_slug header if available
    if (orgSlug) {
        headers['X-Org-Slug'] = orgSlug;
    }
    
    // Add authorization header if available
    if (accessToken) {
        headers['Authorization'] = `Bearer ${accessToken}`;
    }
    
    // Add CSRF token for non-GET requests
    if (options.method && options.method !== 'GET') {
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken.content;
        }
    }
    
    // Make the request
    const response = await fetch(url, {
        ...options,
        headers,
        credentials: 'include' // Include cookies
    });
    
    // Handle token refresh if needed
    if (response.status === 401) {
        const refreshToken = localStorage.getItem('refresh_token');
        if (refreshToken) {
            try {
                // Try to refresh the token
                const refreshResponse = await fetch('/api/v1/auth/refresh', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({ refresh_token: refreshToken })
                });
                
                if (refreshResponse.ok) {
                    const refreshData = await refreshResponse.json();
                    localStorage.setItem('access_token', refreshData.data.access_token);
                    localStorage.setItem('refresh_token', refreshData.data.refresh_token);
                    
                    // Retry the original request with new token
                    headers['Authorization'] = `Bearer ${refreshData.data.access_token}`;
                    return await fetch(url, {
                        ...options,
                        headers,
                        credentials: 'include'
                    });
                }
            } catch (error) {
                console.error('Token refresh failed:', error);
                // Redirect to login
                window.location.href = '/login';
                throw error;
            }
        }
        
        // No refresh token, redirect to login
        window.location.href = '/login';
        throw new Error('Authentication required');
    }
    
    return response;
};

// Helper to get current org_slug
window.getCurrentOrgSlug = function() {
    return localStorage.getItem('org_slug');
};

// Helper to set org_slug
window.setOrgSlug = function(orgSlug) {
    localStorage.setItem('org_slug', orgSlug);
};

// Helper to clear auth data
window.clearAuthData = function() {
    localStorage.removeItem('user');
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('org_slug');
    localStorage.removeItem('firebase_uid');
};
