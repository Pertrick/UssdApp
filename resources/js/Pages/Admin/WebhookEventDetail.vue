<template>
    <AdminLayout>
        <Head :title="`Webhook Event #${event.id}`" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Webhook Event #{{ event.id }}
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Raw webhook payload and processing details
                    </p>
                </div>
                <Link 
                    :href="route('admin.webhook-events')"
                    class="px-4 py-2 bg-black text-white rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-black"
                >
                    ← Back to Events
                </Link>
            </div>
        </template>

        <div class="py-6">
            <!-- Event Overview -->
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Event Overview</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Event ID</p>
                        <p class="text-lg font-medium text-gray-900">{{ event.id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Source</p>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ event.source }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Event Type</p>
                        <p class="text-lg font-medium text-gray-900">{{ event.event_type }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Processing Status</p>
                        <span 
                            :class="{
                                'bg-green-100 text-green-800': event.processing_status === 'processed',
                                'bg-yellow-100 text-yellow-800': event.processing_status === 'pending',
                                'bg-red-100 text-red-800': event.processing_status === 'failed'
                            }"
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                        >
                            {{ event.processing_status }}
                        </span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Session ID (AT)</p>
                        <p class="text-lg font-medium text-gray-900 font-mono">{{ event.session_id || 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">USSD Session ID</p>
                        <Link 
                            v-if="event.ussd_session_id"
                            :href="route('admin.billing-report.business.sessions', { business: event.ussd_session?.ussd?.business_id || 0 })"
                            class="text-lg font-medium text-blue-600 hover:text-blue-800"
                        >
                            #{{ event.ussd_session_id }}
                        </Link>
                        <span v-else class="text-lg font-medium text-gray-400">N/A</span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Received At</p>
                        <p class="text-lg font-medium text-gray-900">{{ formatDate(event.created_at) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Processed At</p>
                        <p class="text-lg font-medium text-gray-900">{{ event.processed_at ? formatDate(event.processed_at) : 'N/A' }}</p>
                    </div>
                    <div v-if="event.ip_address">
                        <p class="text-sm text-gray-600">IP Address</p>
                        <p class="text-lg font-medium text-gray-900 font-mono">{{ event.ip_address }}</p>
                    </div>
                </div>

                <!-- Error Message (if failed) -->
                <div v-if="event.processing_error" class="mt-4 p-4 bg-red-50 border border-red-200 rounded-md">
                    <p class="text-sm font-medium text-red-800 mb-1">Processing Error</p>
                    <p class="text-sm text-red-700 whitespace-pre-wrap">{{ event.processing_error }}</p>
                </div>
            </div>

            <!-- Raw Payload -->
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">Raw Payload</h3>
                <div class="bg-gray-50 rounded-md p-4 overflow-x-auto">
                    <pre class="text-sm text-gray-800 font-mono whitespace-pre-wrap">{{ JSON.stringify(event.payload, null, 2) }}</pre>
                </div>
            </div>

            <!-- Headers (if available) -->
            <div v-if="event.headers && Object.keys(event.headers).length > 0" class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold mb-4">HTTP Headers</h3>
                <div class="bg-gray-50 rounded-md p-4 overflow-x-auto">
                    <pre class="text-sm text-gray-800 font-mono whitespace-pre-wrap">{{ JSON.stringify(event.headers, null, 2) }}</pre>
                </div>
            </div>

            <!-- Related USSD Session Info (if available) -->
            <div v-if="event.ussd_session" class="bg-white shadow-sm rounded-lg p-6">
                <h3 class="text-lg font-semibold mb-4">Related USSD Session</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-600">Session ID</p>
                        <p class="text-lg font-medium text-gray-900">#{{ event.ussd_session.id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Phone Number</p>
                        <p class="text-lg font-medium text-gray-900">{{ event.ussd_session.phone_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Status</p>
                        <p class="text-lg font-medium text-gray-900">{{ event.ussd_session.status }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Business</p>
                        <p class="text-lg font-medium text-gray-900">
                            {{ event.ussd_session.ussd?.business?.business_name || 'N/A' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    event: Object,
});

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });
};
</script>
