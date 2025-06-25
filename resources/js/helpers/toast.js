import { useToast } from 'vue-toastification';

const toast = useToast();

// Map Laravel flash message types to toast types
const messageTypeMap = {
    'success': 'success',
    'error': 'error',
    'warning': 'warning',
    'info': 'info',
    'danger': 'error',
    'primary': 'info',
    'secondary': 'info',
    'light': 'info',
    'dark': 'info'
};

// Handle flash messages from Laravel backend
export function handleFlashMessages(pageProps) {
    // Check if pageProps exist
    if (!pageProps) {
        return;
    }
    
    if (pageProps.flash) {
        const flash = pageProps.flash;
        
        // Handle success messages
        if (flash.success) {
            toast.success(flash.success);
        }
        
        // Handle error messages
        if (flash.error) {
            toast.error(flash.error);
        }
        
        // Handle warning messages
        if (flash.warning) {
            toast.warning(flash.warning);
        }
        
        // Handle info messages
        if (flash.info) {
            toast.info(flash.info);
        }
        
        // Handle generic messages
        if (flash.message) {
            toast.info(flash.message);
        }
    }
}

// Helper functions for manual toast calls
export const showToast = {
    success: (message, options = {}) => toast.success(message, options),
    error: (message, options = {}) => toast.error(message, options),
    warning: (message, options = {}) => toast.warning(message, options),
    info: (message, options = {}) => toast.info(message, options),
    default: (message, options = {}) => toast.default(message, options),
};

export default toast; 