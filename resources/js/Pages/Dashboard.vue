<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    user: Object,
    business: Object,
    ussdStats: Object,
    recentActivities: Array,

});

const formatDate = (date) => {
    return date.toLocaleDateString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

// Logout method
const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                    <p class="text-sm text-gray-600 mt-1">Welcome back, {{ user?.name }}!</p>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">{{ formatDate(new Date()) }}</span>
                    <form @submit.prevent="logout" class="inline">
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Welcome Section -->
                <div class="bg-gradient-to-r from-blue-600 to-indigo-700 rounded-2xl shadow-xl mb-8">
                    <div class="px-8 py-12">
                        <div class="flex items-center justify-between">
                            <div class="text-white">
                                <h2 class="text-3xl font-bold mb-2">Welcome to Your Dashboard</h2>
                                <p class="text-blue-100 text-lg mb-4">Manage your business and USSD services from one place</p>
                                <div v-if="business" class="flex items-center space-x-4">
                                    <div class="bg-white bg-opacity-20 rounded-lg px-4 py-2">
                                        <span class="text-sm text-blue-100">Business:</span>
                                        <span class="text-white font-semibold ml-2">{{ business.business_name }}</span>
                                    </div>
                                    <div class="bg-white bg-opacity-20 rounded-lg px-4 py-2">
                                        <span class="text-sm text-blue-100">Status:</span>
                                        <span class="text-white font-semibold ml-2">
                                            <span v-if="business.registration_status === 'verified'" class="text-green-200">✓ Verified</span>
                                            <span v-else class="text-yellow-200">⚠ Pending Verification</span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="hidden lg:block">
                                <div class="w-24 h-24 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                    <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div v-if="ussdStats" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total USSD Services -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Total USSD Services</p>
                                <p class="text-2xl font-bold text-gray-900">{{ ussdStats.total }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Active Services -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Active Services</p>
                                <p class="text-2xl font-bold text-gray-900">{{ ussdStats.active }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Inactive Services -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Inactive Services</p>
                                <p class="text-2xl font-bold text-gray-900">{{ ussdStats.inactive }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Business Status -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-600">Verification Status</p>
                                <div class="flex items-center mt-1">
                                    <span v-if="business?.registration_status === 'verified'" class="text-lg font-semibold text-green-600">✓ Verified</span>
                                    <span v-else class="text-sm font-semibold text-orange-600">⚠ Pending Verification</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Section -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
                    <!-- USSD Management -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">USSD Services</h3>
                            <Link
                                :href="route('ussd.create')"
                                class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                New USSD
                            </Link>
                        </div>
                        
                        <div class="space-y-4">
                            <Link
                                :href="route('ussd.index')"
                                class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group"
                            >
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center group-hover:bg-blue-200 transition-colors">
                                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">View All USSD Services</h4>
                                    <p class="text-sm text-gray-500">Manage and monitor your USSD services</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>

                            <Link
                                :href="route('ussd.create')"
                                class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors group"
                            >
                                <div class="flex-shrink-0">
                                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center group-hover:bg-green-200 transition-colors">
                                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="ml-4 flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">Create New USSD</h4>
                                    <p class="text-sm text-gray-500">Set up a new USSD service for your business</p>
                                </div>
                                <svg class="w-5 h-5 text-gray-400 group-hover:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </Link>
                        </div>
                    </div>

                    <!-- USSD Analytics & Insights -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h3 class="text-lg font-semibold text-gray-900">Analytics & Insights</h3>
                            <button class="text-sm text-blue-600 hover:text-blue-700 font-medium">View Details</button>
                        </div>
                        
                        <div class="space-y-4">
                            <!-- Performance Overview -->
                            <div class="p-4 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg border border-blue-100">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-sm font-medium text-blue-900">Performance Overview</h4>
                                        <p class="text-xs text-blue-700 mt-1">Last 30 days</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-lg font-bold text-blue-900">{{ ussdStats?.active || 0 }}</p>
                                        <p class="text-xs text-blue-600">Active Services</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Stats -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            </svg>
                                        </div>
                                        <div class="ml-2">
                                            <p class="text-xs font-medium text-gray-600">Success Rate</p>
                                            <p class="text-sm font-bold text-gray-900">98.5%</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-2">
                                            <p class="text-xs font-medium text-gray-600">Avg Response</p>
                                            <p class="text-sm font-bold text-gray-900">1.2s</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Items -->
                            <div class="p-4 bg-amber-50 rounded-lg border border-amber-100">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-amber-800">Quick Actions</h4>
                                        <p class="text-sm text-amber-700">Configure your USSD flows and test services</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                        <Link :href="route('activities.index')" class="text-sm text-blue-600 hover:text-blue-700 font-medium">View All</Link>
                    </div>
                    
                    <div v-if="!recentActivities || recentActivities.length === 0" class="text-center py-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-medium text-gray-900 mb-2">No recent activity</h4>
                        <p class="text-sm text-gray-500">Start using the system to see your activity log here.</p>
                    </div>
                    
                    <div v-else class="space-y-4">
                        <div 
                            v-for="activity in recentActivities" 
                            :key="activity.id"
                            class="flex items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors"
                        >
                            <div class="flex-shrink-0">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="activity.color">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="activity.icon" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-medium text-gray-900">{{ activity.description }}</p>
                                <div class="flex items-center space-x-2 mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ activity.activity_type.replace('_', ' ').toUpperCase() }}
                                    </span>
                                </div>
                            </div>
                            <span class="text-xs text-gray-400">{{ activity.time_ago }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
