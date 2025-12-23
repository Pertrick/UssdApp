<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ apiConfig.name }}
                </h2>
                <div class="flex space-x-3">
                    <Link
                        :href="route('integration.index')"
                        class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Integrations
                    </Link>
                    <Link
                        v-if="!apiConfig.is_marketplace_template"
                        :href="route('integration.edit', apiConfig.id)"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Edit
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- API Overview -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">API Overview</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Description</h4>
                                <p class="text-gray-900">{{ apiConfig.description || 'No description provided' }}</p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Provider</h4>
                                <p class="text-gray-900">{{ apiConfig.provider_name || 'Custom API' }}</p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Endpoint</h4>
                                <p class="text-gray-900 font-mono text-sm">{{ apiConfig.endpoint_url }}</p>
                            </div>
                            <div>
                                <h4 class="text-sm font-medium text-gray-500 mb-2">Method</h4>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ apiConfig.method }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Usage Statistics -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Usage Statistics</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div class="text-center">
                                <p class="text-2xl font-bold text-gray-900">{{ usageStats.total_calls }}</p>
                                <p class="text-sm text-gray-500">Total Calls</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-green-600">{{ usageStats.successful_calls }}</p>
                                <p class="text-sm text-gray-500">Successful</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-red-600">{{ usageStats.failed_calls }}</p>
                                <p class="text-sm text-gray-500">Failed</p>
                            </div>
                            <div class="text-center">
                                <p class="text-2xl font-bold text-blue-600">{{ usageStats.success_rate }}%</p>
                                <p class="text-sm text-gray-500">Success Rate</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Configuration Details -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Configuration Details</h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Request Configuration -->
                            <div>
                                <h4 class="text-md font-medium text-gray-900 mb-4">Request Configuration</h4>
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Timeout:</span>
                                        <span class="ml-2 text-gray-900">{{ apiConfig.timeout }} seconds</span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Retry Attempts:</span>
                                        <span class="ml-2 text-gray-900">{{ apiConfig.retry_attempts }}</span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Authentication:</span>
                                        <span class="ml-2 text-gray-900">{{ getAuthTypeDisplay(apiConfig.auth_type) }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Information -->
                            <div>
                                <h4 class="text-md font-medium text-gray-900 mb-4">Status Information</h4>
                                <div class="space-y-3">
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Test Status:</span>
                                        <span
                                            :class="{
                                                'bg-green-100 text-green-800': usageStats.test_status === 'success',
                                                'bg-red-100 text-red-800': usageStats.test_status === 'failed',
                                                'bg-yellow-100 text-yellow-800': usageStats.test_status === 'pending'
                                            }"
                                            class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                                        >
                                            {{ usageStats.test_status }}
                                        </span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Last Tested:</span>
                                        <span class="ml-2 text-gray-900">{{ usageStats.last_tested || 'Never' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-sm font-medium text-gray-500">Average Response Time:</span>
                                        <span class="ml-2 text-gray-900">{{ usageStats.average_response_time || 'N/A' }}ms</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    apiConfig: {
        type: Object,
        required: true
    },
    usageStats: {
        type: Object,
        default: () => ({
            total_calls: 0,
            successful_calls: 0,
            failed_calls: 0,
            success_rate: 0,
            test_status: 'pending',
            last_tested: null,
            average_response_time: null
        })
    },
    recentLogs: {
        type: Array,
        default: () => []
    }
});

const getAuthTypeDisplay = (authType) => {
    const authTypes = {
        'api_key': 'API Key',
        'bearer_token': 'Bearer Token',
        'basic': 'Basic Authentication',
        'oauth': 'OAuth 2.0',
        'none': 'No Authentication'
    };
    return authTypes[authType] || authType;
};
</script>
