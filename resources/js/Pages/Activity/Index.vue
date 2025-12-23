<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { debounce } from 'lodash';

const props = defineProps({
    activities: Object,
    filters: Object,
});

const search = ref(props.filters?.search || '');
const selectedType = ref(props.filters?.type || '');

// Debounced search function
const performSearch = debounce(() => {
    router.get(route('activities.index'), {
        search: search.value,
        type: selectedType.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
}, 300);

// Watch for changes in search and type filters
watch([search, selectedType], () => {
    performSearch();
});

const activityTypes = [
    { value: '', label: 'All Activities' },
    { value: 'login', label: 'Login' },
    { value: 'logout', label: 'Logout' },
    { value: 'ussd_created', label: 'USSD Created' },
    { value: 'ussd_updated', label: 'USSD Updated' },
    { value: 'ussd_deleted', label: 'USSD Deleted' },
    { value: 'business_created', label: 'Business Created' },
    { value: 'business_verified', label: 'Business Verified' },
];
</script>

<template>
    <Head title="Activity Log" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Activity Log</h1>
                    <p class="text-sm text-gray-600 mt-1">Track all your activities and system interactions</p>
                </div>
                <Link
                    :href="route('dashboard')"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Dashboard
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Filters Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Search Filter -->
                        <div>
                            <label for="search" class="block text-sm font-medium text-gray-700 mb-2">Search Activities</label>
                            <input
                                id="search"
                                v-model="search"
                                type="text"
                                placeholder="Search by description..."
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <!-- Type Filter -->
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Activity Type</label>
                            <select
                                id="type"
                                v-model="selectedType"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            >
                                <option v-for="type in activityTypes" :key="type.value" :value="type.value">
                                    {{ type.label }}
                                </option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Activities List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Activities</h3>
                    </div>

                    <div v-if="activities.data.length === 0" class="p-8 text-center">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No activities found</h3>
                        <p class="text-gray-500">Start using the system to see your activity log here.</p>
                    </div>

                    <div v-else class="divide-y divide-gray-200">
                        <div
                            v-for="activity in activities.data"
                            :key="activity.id"
                            class="p-6 hover:bg-gray-50 transition-colors"
                        >
                            <div class="flex items-start space-x-4">
                                <!-- Activity Icon -->
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center" :class="activity.color">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="activity.icon" />
                                        </svg>
                                    </div>
                                </div>

                                <!-- Activity Details -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-sm font-medium text-gray-900">{{ activity.description }}</p>
                                            <div class="flex items-center space-x-2 mt-1">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ activity.activity_type.replace('_', ' ').toUpperCase() }}
                                                </span>
                                                <span class="text-sm text-gray-500">{{ activity.time_ago }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Additional Details -->
                                    <div v-if="activity.properties" class="mt-2 text-sm text-gray-600">
                                        <div v-if="activity.properties.ussd_name" class="flex items-center">
                                            <span class="font-medium">USSD:</span>
                                            <span class="ml-1">{{ activity.properties.ussd_name }}</span>
                                        </div>
                                        <div v-if="activity.properties.business_name" class="flex items-center">
                                            <span class="font-medium">Business:</span>
                                            <span class="ml-1">{{ activity.properties.business_name }}</span>
                                        </div>
                                    </div>

                                    <!-- IP Address and User Agent -->
                                    <div class="mt-2 text-xs text-gray-400">
                                        <div v-if="activity.ip_address" class="flex items-center">
                                            <span>IP: {{ activity.ip_address }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div v-if="activities.links && activities.links.length > 3" class="px-6 py-4 border-t border-gray-200">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing {{ activities.from }} to {{ activities.to }} of {{ activities.total }} results
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    v-for="link in activities.links"
                                    :key="link.label"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-2 text-sm font-medium rounded-md',
                                        link.active
                                            ? 'bg-blue-600 text-white'
                                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100',
                                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                                    ]"
                                    v-html="link.label"
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 