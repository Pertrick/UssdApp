<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Create Custom API
                </h2>
                <Link
                    :href="route('integration.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Integrations
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">API Configuration</h3>
                        <p class="text-sm text-gray-600 mt-1">Configure your custom API integration</p>
                    </div>

                    <form @submit.prevent="submitForm" class="p-6 space-y-8">
                        <!-- Basic Information -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Basic Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        API Name *
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="e.g., My Payment API"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        HTTP Method *
                                    </label>
                                    <select
                                        v-model="form.method"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">Select Method</option>
                                        <option v-for="(label, value) in httpMethods" :key="value" :value="value">
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Description
                                    </label>
                                    <textarea
                                        v-model="form.description"
                                        rows="3"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Describe what this API does..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- API Endpoint -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">API Endpoint</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="md:col-span-3">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Endpoint URL *
                                    </label>
                                    <input
                                        v-model="form.endpoint_url"
                                        type="url"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="https://api.example.com/v1/endpoint"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Timeout (seconds)
                                    </label>
                                    <input
                                        v-model="form.timeout"
                                        type="number"
                                        min="5"
                                        max="120"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="30"
                                    />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Retry Attempts
                                    </label>
                                    <input
                                        v-model="form.retry_attempts"
                                        type="number"
                                        min="0"
                                        max="5"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="3"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Authentication -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Authentication</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Authentication Type *
                                    </label>
                                    <select
                                        v-model="form.auth_type"
                                        required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option value="">Select Authentication</option>
                                        <option v-for="(label, value) in authTypes" :key="value" :value="value">
                                            {{ label }}
                                        </option>
                                    </select>
                                </div>

                                <!-- Dynamic Auth Config -->
                                <div v-if="form.auth_type && form.auth_type !== 'none'" class="bg-gray-50 p-4 rounded-md">
                                    <h5 class="text-sm font-medium text-gray-700 mb-3">Authentication Configuration</h5>
                                    <div class="space-y-3">
                                        <div v-if="form.auth_type === 'api_key'">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
                                            <input
                                                v-model="form.auth_config.api_key"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Enter your API key"
                                            />
                                        </div>
                                        <div v-if="form.auth_type === 'bearer_token'">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Bearer Token</label>
                                            <input
                                                v-model="form.auth_config.bearer_token"
                                                type="text"
                                                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                placeholder="Enter your bearer token"
                                            />
                                        </div>
                                        <div v-if="form.auth_type === 'basic'">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                                                    <input
                                                        v-model="form.auth_config.username"
                                                        type="text"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                        placeholder="Enter username"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                                                    <input
                                                        v-model="form.auth_config.password"
                                                        type="password"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                                        placeholder="Enter password"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Headers -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Request Headers</h4>
                            <div class="space-y-3">
                                <div v-for="(header, index) in form.headers" :key="index" class="flex space-x-3">
                                    <input
                                        v-model="header.key"
                                        type="text"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Header name"
                                    />
                                    <input
                                        v-model="header.value"
                                        type="text"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Header value"
                                    />
                                    <button
                                        type="button"
                                        @click="removeHeader(index)"
                                        class="px-3 py-2 text-red-600 hover:text-red-800"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    @click="addHeader"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add Header
                                </button>
                            </div>
                        </div>

                        <!-- Request/Response Mapping -->
                        <div class="space-y-8">
                            <!-- Request Mapping -->
                            <div class="block">
                                <h4 class="text-md font-medium text-gray-900 mb-4">Request Mapping</h4>
                                <div class="space-y-3">
                                    <div v-for="(mapping, index) in form.request_mapping" :key="mapping.id || index" class="flex space-x-3">
                                        <input
                                            v-model="mapping.key"
                                            type="text"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="API field"
                                        />
                                        <input
                                            v-model="mapping.value"
                                            type="text"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="{{input.field_name}}"
                                        />
                                        <button
                                            type="button"
                                            @click.prevent="removeRequestMapping(index)"
                                            class="px-3 py-2 text-red-600 hover:text-red-800 cursor-pointer"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addRequestMapping"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Add Mapping
                                    </button>
                                </div>
                            </div>

                            <!-- Response Mapping -->
                            <div class="block">
                                <h4 class="text-md font-medium text-gray-900 mb-4">Response Mapping</h4>
                                <div class="space-y-3">
                                    <div v-for="(mapping, index) in form.response_mapping" :key="index" class="flex space-x-3">
                                        <input
                                            v-model="mapping.key"
                                            type="text"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="USSD field"
                                        />
                                        <input
                                            v-model="mapping.value"
                                            type="text"
                                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="response.data.field"
                                        />
                                        <button
                                            type="button"
                                            @click="removeResponseMapping(index)"
                                            class="px-3 py-2 text-red-600 hover:text-red-800"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <button
                                        type="button"
                                        @click="addResponseMapping"
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Add Mapping
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- API Response Configuration -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">API Response Configuration</h4>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Data Path (Optional)
                                    </label>
                                    <input
                                        v-model="form.data_path"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="data (default) or response.data or body.result"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">
                                        Specify the path to extract data from API response. Supports dot notation (e.g., "response.data"). 
                                        If empty, will use list_path from dynamic flow config, or fallback to "data".
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        Error Path (Optional)
                                    </label>
                                    <input
                                        v-model="form.error_path"
                                        type="text"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="error.message (default) or error or message"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">
                                        Specify the path to extract error messages from API response. Supports dot notation (e.g., "error.message"). 
                                        If empty, will try common paths like "message", "error", "error_message".
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Success Criteria -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Success Criteria</h4>
                            <div class="space-y-3">
                                <div v-for="(criteria, index) in form.success_criteria" :key="index" class="flex space-x-3">
                                    <input
                                        v-model="criteria.field"
                                        type="text"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="response.data.status"
                                    />
                                    <select
                                        v-model="criteria.operator"
                                        class="px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                    >
                                        <option v-for="(label, value) in operators" :key="value" :value="value">
                                            {{ label }}
                                        </option>
                                    </select>
                                    <input
                                        v-model="criteria.value"
                                        type="text"
                                        class="flex-1 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="success"
                                    />
                                    <button
                                        type="button"
                                        @click="removeSuccessCriteria(index)"
                                        class="px-3 py-2 text-red-600 hover:text-red-800"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                                <button
                                    type="button"
                                    @click="addSuccessCriteria"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add Criteria
                                </button>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <Link
                                :href="route('integration.index')"
                                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="inline-flex justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                            >
                                <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                {{ form.processing ? 'Creating...' : 'Create API' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    authTypes: {
        type: Object,
        default: () => ({})
    },
    httpMethods: {
        type: Object,
        default: () => ({})
    },
    operators: {
        type: Object,
        default: () => ({})
    }
});

const form = useForm({
    name: '',
    description: '',
    endpoint_url: '',
    method: '',
    timeout: 30,
    retry_attempts: 3,
    auth_type: '',
    auth_config: {},
    headers: [],
    request_mapping: [],
    response_mapping: [],
    data_path: '',
    error_path: '',
    success_criteria: []
});

const addHeader = () => {
    form.headers.push({ key: '', value: '' });
};

const removeHeader = (index) => {
    form.headers.splice(index, 1);
};

const addRequestMapping = () => {
    form.request_mapping.push({ 
        id: Date.now() + Math.random(), 
        key: '', 
        value: '' 
    });
};

const removeRequestMapping = (index) => {
    if (index >= 0 && index < form.request_mapping.length) {
        form.request_mapping = form.request_mapping.filter((_, i) => i !== index);
    }
};

const addResponseMapping = () => {
    form.response_mapping.push({ key: '', value: '' });
};

const removeResponseMapping = (index) => {
    form.response_mapping.splice(index, 1);
};

const addSuccessCriteria = () => {
    form.success_criteria.push({ field: '', operator: 'equals', value: '' });
};

const removeSuccessCriteria = (index) => {
    form.success_criteria.splice(index, 1);
};

const page = usePage();

const submitForm = () => {
    // Clean up empty mappings before submitting
    form.headers = form.headers.filter(h => h.key && h.value);
    form.request_mapping = form.request_mapping.filter(m => m.key && m.value);
    form.response_mapping = form.response_mapping.filter(m => m.key && m.value);
    form.success_criteria = form.success_criteria.filter(c => c.field && c.value);
    
    // Get CSRF token from meta tag or Inertia props
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
        || page.props.csrf_token;
    
    if (!csrfToken) {
        alert('CSRF token not found. Please refresh the page and try again.');
        window.location.reload();
        return;
    }
    
    // useForm automatically handles CSRF tokens via Inertia
    // But we'll add explicit error handling for 419 errors
    form.post(route('integration.store'), {
        preserveScroll: true,
        preserveState: false,
        onError: (errors) => {
            console.error('Error creating API:', errors);
            
            // Check for 419 error in the error object
            const errorMessage = errors.message || errors.error || '';
            const errorString = JSON.stringify(errors).toLowerCase();
            
            if (errorMessage.includes('419') || errorMessage.includes('CSRF') || 
                errorString.includes('419') || errorString.includes('csrf')) {
                alert('Your session has expired. Please refresh the page and try again.');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
                return;
            }
            
            // Handle validation errors
            if (Object.keys(errors).length > 0 && !errors.message) {
                const firstError = Object.values(errors)[0];
                if (typeof firstError === 'string') {
                    alert(firstError);
                } else if (Array.isArray(firstError) && firstError.length > 0) {
                    alert(firstError[0]);
                }
            }
        },
        onSuccess: () => {
            // Success is handled by Inertia redirect in the controller
        },
        onFinish: () => {
            // This runs after success or error
        }
    });
};
</script>
