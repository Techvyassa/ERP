/**
 * API Client Utility
 * Automatically attaches org_slug header to API requests
 * Handles token expiration and automatic refresh
 */

// Token refresh lock to prevent multiple simultaneous refresh attempts
let isRefreshing = false;
let failedRequestsQueue = [];

/**
 * Process the queue of failed requests after token refresh
 */
const processQueue = (error, token = null) => {
    failedRequestsQueue.forEach(({ resolve, reject }) => {
        if (error) {
            reject(error);
        } else {
            resolve(token);
        }
    });
    failedRequestsQueue = [];
};

/**
 * Helper function to make API calls with org_slug header
 */
window.apiRequest = async function (url, options = {}) {
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
        const errorData = await response.json().catch(() => ({}));

        // Check if it's a token expiration error
        const isTokenExpired = errorData?.error?.code === 'TOKEN_EXPIRED' ||
            errorData?.message?.toLowerCase().includes('expired');

        if (isTokenExpired) {
            const refreshToken = localStorage.getItem('refresh_token');

            if (refreshToken) {
                if (!isRefreshing) {
                    isRefreshing = true;

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

                            // Store new tokens
                            localStorage.setItem('access_token', refreshData.data.access_token);
                            if (refreshData.data.refresh_token) {
                                localStorage.setItem('refresh_token', refreshData.data.refresh_token);
                            }

                            // Update authorization header with new token
                            headers['Authorization'] = `Bearer ${refreshData.data.access_token}`;

                            // Process queued requests
                            processQueue(null, refreshData.data.access_token);

                            // Retry the original request with new token
                            return await fetch(url, {
                                ...options,
                                headers,
                                credentials: 'include'
                            });
                        } else {
                            // Refresh failed - clear tokens and redirect to login
                            console.error('Token refresh failed:', refreshResponse.status);
                            window.clearAuthData();
                            window.location.href = '/login';
                            throw new Error('Token refresh failed');
                        }
                    } catch (error) {
                        console.error('Token refresh error:', error);
                        processQueue(error, null);
                        window.clearAuthData();
                        window.location.href = '/login';
                        throw error;
                    } finally {
                        isRefreshing = false;
                    }
                } else {
                    // Another request is already refreshing - queue this request
                    return new Promise((resolve, reject) => {
                        failedRequestsQueue.push({ resolve, reject });
                    }).then(token => {
                        headers['Authorization'] = `Bearer ${token}`;
                        return fetch(url, {
                            ...options,
                            headers,
                            credentials: 'include'
                        });
                    });
                }
            }

            // No refresh token available
            console.warn('No refresh token available');
            window.clearAuthData();
            window.location.href = '/login';
            throw new Error('Authentication required');
        }

        // Other 401 error - redirect to login
        console.warn('Unauthorized request:', errorData);
        window.clearAuthData();
        window.location.href = '/login';
        throw new Error('Authentication required');
    }

    return response;
};

// Helper to get current org_slug
window.getCurrentOrgSlug = function () {
    return localStorage.getItem('org_slug');
};

// Helper to set org_slug
window.setOrgSlug = function (orgSlug) {
    localStorage.setItem('org_slug', orgSlug);
};

// Helper to clear auth data
window.clearAuthData = function () {
    localStorage.removeItem('user');
    localStorage.removeItem('access_token');
    localStorage.removeItem('refresh_token');
    localStorage.removeItem('org_slug');
    localStorage.removeItem('firebase_uid');
};
