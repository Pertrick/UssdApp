<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Admin Dashboard
            </h2>
        </template>

        <div class="py-12">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ stats.total_users }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Businesses</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ stats.total_businesses }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Approvals</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ stats.pending_approvals }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Verified Businesses</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ stats.verified_businesses }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Recent Registrations</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ stats.recent_registrations }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Recent Businesses -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Business Registrations</h3>
                        <div class="space-y-4">
                            <div v-for="business in recentBusinesses" :key="business.id" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">{{ business.business_name }}</h4>
                                    <p class="text-sm text-gray-500">{{ business.user.name }}</p>
                                    <p class="text-xs text-gray-400">{{ formatDate(business.created_at) }}</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span :class="getStatusBadgeClass(business.verified)">
                                        {{ business.verified ? 'Verified' : 'Pending' }}
                                    </span>
                                    <Link :href="route('admin.businesses.show', business.id)" class="text-blue-600 hover:text-blue-800 text-sm">
                                        View
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <Link :href="route('admin.businesses')" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View all businesses →
                            </Link>
                        </div>
                    </div>
                </div>

                <!-- Recent Pending Businesses -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Pending Businesses</h3>
                        <div class="space-y-4">
                            <div v-for="business in pendingBusinesses" :key="business.id" class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">{{ business.business_name }}</h4>
                                    <p class="text-sm text-gray-500">{{ business.user.name }}</p>
                                    <p class="text-xs text-gray-400">{{ formatDate(business.created_at) }}</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full">
                                        Pending
                                    </span>
                                    <Link :href="route('admin.businesses.show', business.id)" class="text-blue-600 hover:text-blue-800 text-sm">
                                        View
                                    </Link>
                                </div>
                            </div>
                        </div>
                        <div class="mt-4">
                            <Link :href="route('admin.businesses', { status: 'pending' })" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View all pending →
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    stats: Object,
    recentBusinesses: Array,
    pendingBusinesses: Array,
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const getStatusBadgeClass = (verified) => {
    return verified 
        ? 'bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full'
        : 'bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full'
}


</script>
