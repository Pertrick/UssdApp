// CSRF Token Management Utility
export const csrfToken = {
    /**
     * Get the current CSRF token from meta tag
     */
    get() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : null;
    },

    /**
     * Refresh the CSRF token by making a request to get a new one
     */
    async refresh() {
        try {
            const response = await fetch('/csrf-token', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (response.ok) {
                const data = await response.json();
                const metaTag = document.querySelector('meta[name="csrf-token"]');
                if (metaTag && data.token) {
                    metaTag.setAttribute('content', data.token);
                    // Update axios default headers
                    if (window.axios) {
                        window.axios.defaults.headers.common['X-CSRF-TOKEN'] = data.token;
                    }
                    return data.token;
                }
            }
        } catch (error) {
            console.error('Failed to refresh CSRF token:', error);
        }
        return null;
    },

    /**
     * Set up automatic CSRF token refresh
     */
    setupAutoRefresh() {
        // Refresh token every 30 minutes
        setInterval(() => {
            this.refresh();
        }, 30 * 60 * 1000);

        // Refresh token on page visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.refresh();
            }
        });
    }
};

// Initialize CSRF token management
if (typeof window !== 'undefined') {
    csrfToken.setupAutoRefresh();
}
