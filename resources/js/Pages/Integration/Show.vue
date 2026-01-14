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

                <!-- Test API Section -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Test API Integration</h3>
                        <p class="text-sm text-gray-500 mt-1">Test your API configuration with custom values to verify it works correctly</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            <!-- Test Data Inputs - Dynamically generated from request mapping -->
                            <div v-if="testFields.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div v-for="field in testFields" :key="field.key">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">
                                        {{ field.label }}
                                        <span class="text-xs text-gray-500 ml-1">({{ field.mapping }})</span>
                                    </label>
                                    <input
                                        v-model="testData[field.dataKey]"
                                        type="text"
                                        :placeholder="field.placeholder"
                                        class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                    />
                                </div>
                            </div>
                            <div v-else class="text-sm text-gray-500 italic">
                                No dynamic fields found in request mapping. All values are static or no mapping configured.
                            </div>

                            <!-- Test Button -->
                            <div class="flex items-center space-x-3">
                                <button
                                    @click="testApi"
                                    :disabled="testing"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 disabled:opacity-50"
                                >
                                    <svg v-if="testing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ testing ? 'Testing...' : 'Test API' }}
                                </button>
                                <span v-if="testResult" :class="testResult.success ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                                    {{ testResult.success ? '✓ Test Successful' : '✗ Test Failed' }}
                                </span>
                            </div>

                            <!-- Test Result -->
                            <div v-if="testResult" class="mt-4 p-4 rounded" :class="testResult.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
                                <div class="text-sm">
                                    <p class="font-medium mb-2" :class="testResult.success ? 'text-green-900' : 'text-red-900'">
                                        {{ testResult.message }}
                                    </p>
                                    <div v-if="testResult.response" class="space-y-2 text-xs">
                                        <div>
                                            <span class="font-medium">Status Code:</span> {{ testResult.response.status }}
                                        </div>
                                        <div v-if="testResult.response_time">
                                            <span class="font-medium">Response Time:</span> {{ testResult.response_time }}ms
                                        </div>
                                        <div v-if="testResult.response.body" class="mt-2">
                                            <span class="font-medium">Response Body:</span>
                                            <pre class="mt-1 p-2 bg-gray-100 rounded text-xs overflow-auto max-h-40">{{ JSON.stringify(testResult.response.body, null, 2) }}</pre>
                                        </div>
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
import { ref, computed, watch } from 'vue';

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

// Parse request mapping to extract dynamic fields
const testFields = computed(() => {
    const mapping = props.apiConfig.request_mapping || [];
    const fields = [];
    
    // Normalize mapping format (handle both [{key, value}] and {key: value})
    const normalizedMapping = {};
    if (Array.isArray(mapping)) {
        mapping.forEach(item => {
            if (item && typeof item === 'object') {
                if (item.key && item.value) {
                    // Array format: [{key: 'field', value: 'mapping'}]
                    normalizedMapping[item.key] = item.value;
                } else {
                    // Object format: {key: value}
                    Object.assign(normalizedMapping, item);
                }
            }
        });
    } else if (typeof mapping === 'object') {
        Object.assign(normalizedMapping, mapping);
    }
    
    // Extract fields that use template variables
    Object.keys(normalizedMapping).forEach(apiField => {
        const mappingValue = normalizedMapping[apiField];
        
        // Check if mapping value contains template variables (e.g., {session.phone_number})
        if (typeof mappingValue === 'string' && mappingValue.includes('{')) {
            // Extract the variable path (e.g., "session.phone_number" from "{session.phone_number}")
            const matches = mappingValue.match(/\{([^}]+)\}/g);
            if (matches && matches.length > 0) {
                matches.forEach(match => {
                    const variablePath = match.replace(/[{}]/g, '');
                    
                    // Determine the data key and label based on the variable path
                    let dataKey = '';
                    let label = apiField;
                    let placeholder = 'Enter value';
                    
                    // Helper to format label
                    const formatLabel = (name) => {
                        const labels = {
                            'phone_number': 'Phone Number',
                            'phone': 'Phone Number',
                            'amount': 'Amount',
                            'Pin': 'PIN',
                            'pin': 'PIN',
                            'network': 'Network',
                            'service': 'Service',
                            'number': 'Number',
                        };
                        return labels[name] || name.charAt(0).toUpperCase() + name.slice(1).replace(/_/g, ' ');
                    };
                    
                    // Helper to get placeholder
                    const getPlaceholder = (name) => {
                        const placeholders = {
                            'phone_number': '+2348012345678',
                            'phone': '+2348012345678',
                            'amount': '1000',
                            'Pin': '1234',
                            'pin': '1234',
                            'network': 'MTN',
                            'service': 'data',
                            'number': '+2348012345678',
                        };
                        return placeholders[name] || `Enter ${name}`;
                    };
                    
                    if (variablePath === 'session.phone_number') {
                        dataKey = 'phone_number';
                        label = formatLabel('phone_number');
                        placeholder = getPlaceholder('phone_number');
                    } else if (variablePath.startsWith('session.data.selected_item_data.')) {
                        // Extract field name from session.data.selected_item_data.field_name
                        const fieldName = variablePath.replace('session.data.selected_item_data.', '');
                        dataKey = fieldName;
                        label = formatLabel(fieldName);
                        placeholder = getPlaceholder(fieldName);
                    } else if (variablePath.startsWith('session.data.')) {
                        // Extract field name from session.data.field_name
                        const fieldName = variablePath.replace('session.data.', '');
                        dataKey = fieldName;
                        label = formatLabel(fieldName);
                        placeholder = getPlaceholder(fieldName);
                    } else if (variablePath.startsWith('session.')) {
                        // Extract field name from session.field_name
                        const fieldName = variablePath.replace('session.', '');
                        dataKey = fieldName;
                        label = formatLabel(fieldName);
                        placeholder = getPlaceholder(fieldName);
                    } else if (variablePath.startsWith('input.')) {
                        const fieldName = variablePath.replace('input.', '');
                        dataKey = fieldName;
                        label = formatLabel(fieldName);
                        placeholder = getPlaceholder(fieldName);
                    }
                    
                    // Only add if we found a valid data key and it's not already added
                    if (dataKey && !fields.find(f => f.dataKey === dataKey)) {
                        fields.push({
                            key: apiField,
                            label: label,
                            mapping: mappingValue,
                            dataKey: dataKey,
                            placeholder: placeholder
                        });
                    }
                });
            }
        }
    });
    
    return fields;
});

// Initialize test data as reactive ref (editable)
const testData = ref({});

// Watch testFields and initialize testData when fields change
watch(testFields, (fields) => {
    const data = {};
    fields.forEach(field => {
        // Only set default if not already set (preserve user input)
        if (!testData.value[field.dataKey]) {
            // Set default values based on field type
            if (field.dataKey === 'phone_number' || field.dataKey === 'phone') {
                data[field.dataKey] = '+2348012345678';
            } else if (field.dataKey === 'amount') {
                data[field.dataKey] = '1000';
            } else if (field.dataKey === 'network') {
                data[field.dataKey] = 'MTN';
            } else if (field.dataKey === 'Pin' || field.dataKey === 'pin') {
                data[field.dataKey] = '1234';
            } else {
                data[field.dataKey] = '';
            }
        } else {
            // Preserve existing value
            data[field.dataKey] = testData.value[field.dataKey];
        }
    });
    testData.value = data;
}, { immediate: true });

const testing = ref(false);
const testResult = ref(null);

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

const testApi = async () => {
    testing.value = true;
    testResult.value = null;

    try {
        const response = await fetch(`/integration/${props.apiConfig.id}/test`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            },
            body: JSON.stringify({
                test_data: testData.value
            })
        });

        const result = await response.json();
        testResult.value = result;
    } catch (error) {
        testResult.value = {
            success: false,
            message: 'Failed to test API: ' + error.message
        };
    } finally {
        testing.value = false;
    }
};
</script>
