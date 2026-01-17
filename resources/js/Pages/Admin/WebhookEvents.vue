<template>
    <AdminLayout>
        <Head title="Webhook Events" />

        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                        Webhook Events
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">
                        Raw webhook events from gateway providers for auditing and debugging
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-600">Total Events</p>
                    <p class="text-2xl font-bold text-gray-800">{{ stats.total }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-600">Processed</p>
                    <p class="text-2xl font-bold text-green-600">{{ stats.processed }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-600">Failed</p>
                    <p class="text-2xl font-bold text-red-600">{{ stats.failed }}</p>
                </div>
                <div class="bg-white shadow-sm rounded-lg p-4">
                    <p class="text-sm text-gray-600">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ stats.pending }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white shadow-sm rounded-lg p-6 mb-6">
                <form @submit.prevent="loadEvents" class="flex flex-wrap items-end gap-4">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                        <input
                            type="text"
                            v-model="filters.search"
                            placeholder="Session ID or Event ID"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                        />
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Source</label>
                        <select
                            v-model="filters.source"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                        >
                            <option value="">All Sources</option>
                            <option value="africastalking">AfricasTalking</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select
                            v-model="filters.status"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                        >
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="processed">Processed</option>
                            <option value="failed">Failed</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Event Type</label>
                        <select
                            v-model="filters.event_type"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                        >
                            <option value="">All Types</option>
                            <option value="session_end">Session End</option>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input
                            type="date"
                            v-model="filters.start_date"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                        />
                    </div>
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input
                            type="date"
                            v-model="filters.end_date"
                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-black focus:ring-black"
                        />
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="submit"
                            class="px-4 py-2 bg-black text-white rounded-md hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-black"
                        >
                            Apply Filters
                        </button>
                        <button
                            type="button"
                            @click="clearFilters"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            Clear
                        </button>
                    </div>
                </form>
            </div>

            <!-- Events Table -->
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Session ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">USSD Session</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Received At</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr v-for="event in events.data" :key="event.id">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ event.id }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    {{ event.source }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-mono">{{ event.session_id || 'N/A' }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ event.event_type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
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
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <Link 
                                    v-if="event.ussd_session_id"
                                    :href="route('admin.billing-report.business.sessions', { business: event.ussd_session?.ussd?.business_id || 0 })"
                                    class="text-blue-600 hover:text-blue-800"
                                >
                                    #{{ event.ussd_session_id }}
                                </Link>
                                <span v-else class="text-gray-400">N/A</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ formatDate(event.created_at) }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <Link 
                                    :href="route('admin.webhook-events.show', { webhookEvent: event.id })"
                                    class="text-blue-600 hover:text-blue-900"
                                >
                                    View Details
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div class="bg-white px-4 py-3 border-t border-gray-200 sm:px-6">
                    <div class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <Link
                                v-if="events.prev_page_url"
                                :href="events.prev_page_url"
                                class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Previous
                            </Link>
                            <Link
                                v-if="events.next_page_url"
                                :href="events.next_page_url"
                                class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                            >
                                Next
                            </Link>
                        </div>
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm text-gray-700">
                                    Showing
                                    <span class="font-medium">{{ events.from || 0 }}</span>
                                    to
                                    <span class="font-medium">{{ events.to || 0 }}</span>
                                    of
                                    <span class="font-medium">{{ events.total || 0 }}</span>
                                    results
                                </p>
                            </div>
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <Link
                                        v-if="events.prev_page_url"
                                        :href="events.prev_page_url"
                                        class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                    <Link
                                        v-if="events.next_page_url"
                                        :href="events.next_page_url"
                                        class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50"
                                    >
                                        Next
                                    </Link>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps({
    events: Object,
    stats: Object,
    filters: {
        type: Object,
        default: () => ({
            source: '',
            status: '',
            event_type: '',
            search: '',
            start_date: '',
            end_date: '',
        })
    }
});

const filters = ref({ ...props.filters });

const loadEvents = () => {
    router.get(route('admin.webhook-events'), filters.value, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    filters.value = {
        source: '',
        status: '',
        event_type: '',
        search: '',
        start_date: '',
        end_date: '',
    };
    loadEvents();
};

const formatDate = (dateString) => {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};
</script>
