<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Business Management
            </h2>
        </template>

        <div class="py-12">
            <!-- Filters and Search -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex flex-col sm:flex-row gap-4">
                        <div class="flex-1">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search businesses..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                @input="debounceSearch"
                            >
                        </div>
                        <div class="flex gap-2">
                            <select
                                v-model="statusFilter"
                                class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                @change="filterBusinesses"
                            >
                                <option value="all">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="verified">Verified</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Businesses Table -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Business
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Owner
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Contact
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Status
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Registered
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <tr v-for="business in businesses.data" :key="business.id" class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                {{ business.business_name }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ business.business_email }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ business.user.name }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ business.user.email }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            {{ business.phone }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ business.city }}, {{ business.state }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span :class="getStatusBadgeClass(business.verified)">
                                            {{ business.verified ? 'Verified' : 'Pending' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ formatDate(business.created_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex space-x-2">
                                            <Link :href="route('admin.businesses.show', business.id)" class="text-blue-600 hover:text-blue-900">
                                                View Details
                                            </Link>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="businesses.links && businesses.links.length > 3" class="mt-6">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700">
                                Showing {{ businesses.from }} to {{ businesses.to }} of {{ businesses.total }} results
                            </div>
                            <div class="flex space-x-2">
                                <Link
                                    v-for="link in businesses.links"
                                    :key="link.label"
                                    :href="link.url"
                                    :class="[
                                        'px-3 py-2 text-sm font-medium rounded-md',
                                        link.active
                                            ? 'bg-blue-600 text-white'
                                            : 'text-gray-500 hover:text-gray-700 hover:bg-gray-50',
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

        <!-- Business actions are now handled in the detail page -->
    </AdminLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    businesses: Object,
    filters: Object,
})

const search = ref(props.filters.search || '')
const statusFilter = ref(props.filters.status || 'all')

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const getStatusBadgeClass = (verified) => {
    return verified 
        ? 'bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full'
        : 'bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full'
}

const debounceSearch = () => {
    clearTimeout(searchTimeout)
    searchTimeout = setTimeout(() => {
        filterBusinesses()
    }, 300)
}

let searchTimeout = null

const filterBusinesses = () => {
    router.get(route('admin.businesses'), {
        search: search.value,
        status: statusFilter.value,
    }, {
        preserveState: true,
        preserveScroll: true,
    })
}

// Removed approve/reject functions - actions now happen in business detail page

// Watch for filter changes
watch([search, statusFilter], () => {
    filterBusinesses()
})
</script>
