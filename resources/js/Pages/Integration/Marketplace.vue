<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    API Marketplace
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
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Header -->
                <div class="mb-8">
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Browse Pre-configured APIs</h3>
                    <p class="text-gray-600">Choose from our curated collection of verified API integrations. Simply add your credentials and start using them in your USSD flows.</p>
                </div>

                <!-- Category Navigation -->
                <div class="mb-8">
                    <div class="flex flex-wrap gap-4">
                        <button
                            v-for="(category, key) in categories"
                            :key="key"
                            @click="selectedCategory = selectedCategory === key ? null : key"
                            :class="[
                                'flex items-center px-4 py-2 rounded-lg border transition-colors',
                                selectedCategory === key
                                    ? 'bg-blue-50 border-blue-200 text-blue-700'
                                    : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50'
                            ]"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="category.icon === 'phone'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                <path v-else-if="category.icon === 'bank'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                <path v-else-if="category.icon === 'credit-card'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                <path v-else-if="category.icon === 'bolt'" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            {{ category.name }}
                            <span class="ml-2 text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded-full">
                                {{ getCategoryCount(key) }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- API Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <div
                        v-for="api in getAllApis()"
                        :key="api.id"
                        class="bg-white border border-gray-200 rounded-lg p-6 hover:shadow-lg transition-all duration-200 hover:border-blue-300 group"
                    >
                        <!-- Category Badge -->
                        <div class="flex items-center justify-between mb-3">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                {{ getCategoryName(api.category) }}
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                {{ api.provider_name }}
                            </span>
                        </div>

                        <!-- API Header -->
                        <div class="mb-4">
                            <h4 class="text-lg font-semibold text-gray-900 mb-2 group-hover:text-blue-600 transition-colors">
                                {{ api.name }}
                            </h4>
                            <p class="text-sm text-gray-600 line-clamp-3">{{ api.description }}</p>
                        </div>

                        <!-- API Details -->
                        <div class="space-y-2 mb-6">
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                <span class="font-medium">{{ api.method }}</span>
                                <span class="mx-1">•</span>
                                <span>{{ api.timeout }}s timeout</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Verified & Tested</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-600">
                                <svg class="w-4 h-4 mr-2 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                <span>{{ getAuthTypeDisplay(api.auth_type) }}</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex flex-col space-y-2">
                            <button
                                @click="showAddModal(api)"
                                class="w-full inline-flex justify-center items-center px-4 py-2.5 bg-blue-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add to Account
                            </button>
                            <button
                                @click="showDetails(api)"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150"
                            >
                                View Details
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="getAllApis().length === 0" class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No APIs found</h3>
                    <p class="mt-1 text-sm text-gray-500">Try selecting a different category or check back later for new integrations.</p>
                </div>
            </div>
        </div>

        <!-- Add API Modal -->
        <Modal :show="showModal" @close="closeModal">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    Add {{ selectedApi?.name }} to Your Account
                </h3>
                
                
                <!-- Success Message -->
                <div v-if="successMessage" class="mb-4 bg-green-50 border border-green-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ successMessage }}</p>
                        </div>
                    </div>
                </div>

                <!-- Error Message -->
                <div v-if="errorMessage" class="mb-4 bg-red-50 border border-red-200 rounded-md p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ errorMessage }}</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="addApiToAccount">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                API Credentials
                            </label>
                            <div class="space-y-3">
                                <div v-for="(value, key) in getAuthConfig(selectedApi)" :key="key">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ formatAuthField(key) }}
                                        <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        v-model="authConfig[key]"
                                        type="text"
                                        :placeholder="`Enter your ${formatAuthField(key)}`"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                        required
                                    />
                                    <p class="text-xs text-gray-500 mt-1">
                                        This will be securely stored and used for API calls
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-blue-800">Security Note</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p>Your API credentials are encrypted and stored securely. They will only be used to make API calls on your behalf.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="closeModal"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="isSubmitting"
                            class="inline-flex justify-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                        >
                            <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ isSubmitting ? 'Adding...' : 'Add API' }}
                        </button>
                    </div>
                </form>
            </div>
        </Modal>

        <!-- API Details Modal -->
        <Modal :show="showDetailsModal" @close="closeDetailsModal">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900">
                        {{ selectedApi?.name }} Details
                    </h3>
                    <button
                        @click="closeDetailsModal"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div v-if="selectedApi" class="space-y-6">
                    <!-- API Overview -->
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Overview</h4>
                        <p class="text-sm text-gray-600">{{ selectedApi.description }}</p>
                    </div>

                    <!-- API Information -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Provider</h4>
                            <p class="text-sm text-gray-600">{{ selectedApi.provider_name }}</p>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Method</h4>
                            <p class="text-sm text-gray-600">{{ selectedApi.method }}</p>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Timeout</h4>
                            <p class="text-sm text-gray-600">{{ selectedApi.timeout }} seconds</p>
                        </div>
                        <div class="bg-white border border-gray-200 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Authentication</h4>
                            <p class="text-sm text-gray-600">{{ getAuthTypeDisplay(selectedApi.auth_type) }}</p>
                        </div>
                    </div>

                    <!-- Endpoint Information -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Endpoint</h4>
                        <div class="bg-gray-100 rounded p-3 font-mono text-sm text-gray-800 break-all">
                            {{ selectedApi.endpoint_url }}
                        </div>
                    </div>

                    <!-- Required Credentials -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Required Credentials</h4>
                        <div class="space-y-2">
                            <div v-for="(value, key) in selectedApi.auth_config" :key="key" class="flex items-center justify-between py-2 border-b border-gray-100 last:border-b-0">
                                <span class="text-sm font-medium text-gray-700">{{ formatAuthField(key) }}</span>
                                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">Required</span>
                            </div>
                        </div>
                    </div>

                    <!-- Features -->
                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                        <h4 class="text-sm font-medium text-gray-900 mb-3">Features</h4>
                        <div class="flex flex-wrap gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Verified & Tested
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Fast Response
                            </span>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                </svg>
                                Secure
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex space-x-3 pt-4 border-t border-gray-200">
                        <button
                            @click="closeDetailsModal"
                            class="flex-1 px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition ease-in-out duration-150"
                        >
                            Close
                        </button>
                        <button
                            @click="showAddModal(selectedApi); closeDetailsModal()"
                            class="flex-1 inline-flex justify-center items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-sm text-white hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Add to Account
                        </button>
                    </div>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    marketplaceApis: {
        type: Object,
        default: () => ({})
    },
    categories: {
        type: Object,
        default: () => ({})
    }
});

const selectedCategory = ref(null);
const showModal = ref(false);
const showDetailsModal = ref(false);
const selectedApi = ref(null);
const authConfig = ref({});
const isSubmitting = ref(false);
const errorMessage = ref('');
const successMessage = ref('');

const filteredMarketplaceApis = computed(() => {
    if (!selectedCategory.value) {
        return props.marketplaceApis;
    }
    return {
        [selectedCategory.value]: props.marketplaceApis[selectedCategory.value] || []
    };
});

const getCategoryCount = (categoryKey) => {
    return props.marketplaceApis[categoryKey]?.length || 0;
};

const getAllApis = () => {
    const allApis = [];
    Object.keys(props.marketplaceApis).forEach(categoryKey => {
        if (!selectedCategory.value || selectedCategory.value === categoryKey) {
            const apis = props.marketplaceApis[categoryKey] || [];
            apis.forEach(api => {
                allApis.push({
                    ...api,
                    category: categoryKey
                });
            });
        }
    });
    return allApis;
};

const getCategoryName = (categoryKey) => {
    return props.categories[categoryKey]?.name || categoryKey;
};

const getAuthConfig = (api) => {
    if (!api || !api.auth_config) return {};
    
    // If auth_config is a string, try to parse it as JSON
    if (typeof api.auth_config === 'string') {
        try {
            return JSON.parse(api.auth_config);
        } catch (e) {
            console.error('Failed to parse auth_config JSON:', e);
            return {};
        }
    }
    
    // If it's already an object, return it
    // The template values like {{API_KEY}} don't matter - we just need the keys for input fields
    return api.auth_config;
};

const getAuthTypeDisplay = (authType) => {
    const authTypes = {
        'api_key': 'API Key',
        'bearer_token': 'Bearer Token',
        'basic': 'Basic Auth',
        'oauth': 'OAuth 2.0',
        'none': 'No Auth'
    };
    return authTypes[authType] || authType;
};

const formatAuthField = (field) => {
    return field.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
};

const showAddModal = (api) => {
    selectedApi.value = api;
    authConfig.value = {};
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    selectedApi.value = null;
    authConfig.value = {};
    errorMessage.value = '';
    successMessage.value = '';
};

const addApiToAccount = async () => {
    console.log('addApiToAccount called');
    console.log('selectedApi.value:', selectedApi.value);
    console.log('selectedApi.value.id:', selectedApi.value?.id);
    console.log('authConfig.value:', authConfig.value);
    
    if (!selectedApi.value || !selectedApi.value.id) {
        errorMessage.value = 'No API selected. Please try again.';
        return;
    }
    
    // Validate that all required auth fields are filled
    const requiredAuthFields = getAuthConfig(selectedApi.value);
    const missingFields = [];
    
    for (const key in requiredAuthFields) {
        if (!authConfig.value[key] || authConfig.value[key].trim() === '') {
            missingFields.push(formatAuthField(key));
        }
    }
    
    if (missingFields.length > 0) {
        errorMessage.value = `Please fill in all required fields: ${missingFields.join(', ')}`;
        return;
    }
    
    isSubmitting.value = true;
    errorMessage.value = '';
    successMessage.value = '';
    
    try {
        const response = await router.post(route('integration.marketplace.add'), {
            template_id: selectedApi.value.id,
            auth_config: authConfig.value
        }, {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                successMessage.value = 'API added successfully! Redirecting to configuration...';
                setTimeout(() => {
                    closeModal();
                }, 1500);
            },
            onError: (errors) => {
                console.log('errors:', errors);
                if (errors.template_id) {
                    errorMessage.value = errors.template_id[0];
                } else if (errors.auth_config) {
                    errorMessage.value = errors.auth_config[0];
                } else {
                    errorMessage.value = 'Failed to add API. Please try again.';
                }
            }
        });
        
    } catch (error) {
        console.error('Error adding API:', error);
        errorMessage.value = 'An unexpected error occurred. Please try again.';
    } finally {
        isSubmitting.value = false;
    }
};

const showDetails = (api) => {
    selectedApi.value = api;
    showDetailsModal.value = true;
};

const closeDetailsModal = () => {
    showDetailsModal.value = false;
    selectedApi.value = null;
};
</script>

<style scoped>
.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
