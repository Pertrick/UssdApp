<template>
    <AdminLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Analytics
            </h2>
        </template>

        <div class="py-12">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Businesses</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ verificationStats.total }}</dd>
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Verified</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ verificationStats.verified }}</dd>
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Pending</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ verificationStats.pending }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Rejected</dt>
                                    <dd class="text-lg font-medium text-gray-900">{{ verificationStats.rejected }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Monthly Registrations Chart -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Monthly Business Registrations</h3>
                        <div class="h-64 flex items-end justify-between space-x-2">
                            <div v-for="(item, index) in monthlyRegistrations" :key="index" class="flex-1 flex flex-col items-center">
                                <div class="w-full bg-blue-200 rounded-t" :style="{ height: getBarHeight(item.count) }"></div>
                                <div class="text-xs text-gray-500 mt-2 text-center">
                                    {{ formatMonth(item.month) }}
                                </div>
                                <div class="text-xs font-medium text-gray-900">
                                    {{ item.count }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Recent Activity</h3>
                        <div class="space-y-4">
                            <div v-for="business in recentActivity" :key="business.id" class="flex items-center justify-between p-3 border border-gray-200 rounded-lg">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-gray-900">{{ business.business_name }}</h4>
                                    <p class="text-sm text-gray-500">{{ business.user.name }}</p>
                                    <p class="text-xs text-gray-400">{{ formatDate(business.created_at) }}</p>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span :class="getStatusBadgeClass(business.verified)">
                                        {{ business.verified ? 'Verified' : 'Pending' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'

const props = defineProps({
    monthlyRegistrations: Array,
    verificationStats: Object,
    recentActivity: Array,
})

const formatDate = (date) => {
    return new Date(date).toLocaleDateString()
}

const formatMonth = (monthString) => {
    const date = new Date(monthString + '-01')
    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' })
}

const getStatusBadgeClass = (verified) => {
    return verified 
        ? 'bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full'
        : 'bg-yellow-100 text-yellow-800 text-xs px-2 py-1 rounded-full'
}

const getBarHeight = (count) => {
    const maxCount = Math.max(...props.monthlyRegistrations.map(item => item.count))
    const percentage = maxCount > 0 ? (count / maxCount) * 100 : 0
    return `${percentage}%`
}
</script>
