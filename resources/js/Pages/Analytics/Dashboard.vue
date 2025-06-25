<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Analytics Dashboard</h2>
        <div class="flex items-center gap-4">
          <button @click="refreshData" class="px-3 py-1 text-xs bg-blue-100 text-blue-600 rounded hover:bg-blue-200">
            Refresh Data
          </button>
          <Link 
            :href="route('analytics.export')" 
            class="px-3 py-1 text-xs bg-green-100 text-green-600 rounded hover:bg-green-200"
          >
            Export All
          </Link>
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Overall Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <!-- Total Sessions -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Sessions</p>
                <p class="text-2xl font-bold text-gray-900">{{ overallStats.total_sessions }}</p>
                <p class="text-xs text-gray-500">Last 30 days</p>
              </div>
            </div>
          </div>

          <!-- Completed Sessions -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Completed Sessions</p>
                <p class="text-2xl font-bold text-gray-900">{{ overallStats.completed_sessions }}</p>
                <p class="text-xs text-green-600">{{ overallStats.completion_rate }}% success rate</p>
              </div>
            </div>
          </div>

          <!-- Total Interactions -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Total Interactions</p>
                <p class="text-2xl font-bold text-gray-900">{{ overallStats.total_interactions }}</p>
                <p class="text-xs text-gray-500">User inputs & responses</p>
              </div>
            </div>
          </div>

          <!-- Average Session Duration -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
              <div class="flex-shrink-0">
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                  <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
              </div>
              <div class="ml-4">
                <p class="text-sm font-medium text-gray-600">Avg Session Duration</p>
                <p class="text-2xl font-bold text-gray-900">{{ overallStats.avg_session_duration }}s</p>
                <p class="text-xs text-gray-500">Seconds per session</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Top Performing USSD Services -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Top Performing USSD Services</h3>
            <span class="text-sm text-gray-500">Last 30 days</span>
          </div>
          
          <div v-if="topServices.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500">No USSD services found</p>
          </div>
          
          <div v-else class="space-y-4">
            <div v-for="service in topServices" :key="service.id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-900">{{ service.name }}</h4>
                  <p class="text-xs text-gray-500">{{ service.pattern }}</p>
                </div>
              </div>
              <div class="flex items-center space-x-6">
                <div class="text-right">
                  <p class="text-sm font-medium text-gray-900">{{ service.session_count }}</p>
                  <p class="text-xs text-gray-500">Sessions</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium text-green-600">{{ service.completion_rate }}%</p>
                  <p class="text-xs text-gray-500">Success Rate</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium text-gray-900">{{ service.avg_duration }}s</p>
                  <p class="text-xs text-gray-500">Avg Duration</p>
                </div>
                <Link 
                  :href="route('analytics.ussd', service.id)" 
                  class="px-3 py-1 text-xs bg-indigo-100 text-indigo-600 rounded hover:bg-indigo-200"
                >
                  View Details
                </Link>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
          <div class="flex items-center justify-between mb-6">
            <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
            <span class="text-sm text-gray-500">Last 20 interactions</span>
          </div>
          
          <div v-if="recentActivity.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500">No recent activity</p>
          </div>
          
          <div v-else class="space-y-3">
            <div v-for="activity in recentActivity" :key="activity.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div class="flex items-center space-x-3">
                <div class="w-8 h-8 rounded-full flex items-center justify-center" :class="getStatusColor(activity.status)">
                  <svg class="w-4 h-4" :class="getStatusIconColor(activity.status)" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                  </svg>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">{{ activity.ussd_name }}</p>
                  <p class="text-xs text-gray-500">{{ activity.action_type }} - {{ activity.flow_name }}</p>
                </div>
              </div>
              <div class="text-right">
                <p class="text-xs text-gray-500">{{ formatTimestamp(activity.timestamp) }}</p>
                <p v-if="activity.input_data" class="text-xs text-gray-400">Input: {{ activity.input_data }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- USSD Services Overview -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">USSD Services Overview</h3>
            <div v-if="ussds.length === 0" class="text-center py-8">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
              <p class="mt-2 text-sm text-gray-500">No USSD services found</p>
              <Link 
                :href="route('ussd.create')" 
                class="mt-4 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700"
              >
                Create First USSD
              </Link>
            </div>
            <div v-else class="space-y-3">
              <div v-for="ussd in ussds" :key="ussd.id" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                <div>
                  <h4 class="text-sm font-medium text-gray-900">{{ ussd.name }}</h4>
                  <p class="text-xs text-gray-500">{{ ussd.pattern }}</p>
                </div>
                <div class="flex items-center space-x-2">
                  <span :class="ussd.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'" class="px-2 py-1 text-xs rounded-full">
                    {{ ussd.is_active ? 'Active' : 'Inactive' }}
                  </span>
                  <Link 
                    :href="route('analytics.ussd', ussd.id)" 
                    class="px-2 py-1 text-xs bg-indigo-100 text-indigo-600 rounded hover:bg-indigo-200"
                  >
                    Analytics
                  </Link>
                </div>
              </div>
            </div>
          </div>

          <!-- Performance Insights -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Performance Insights</h3>
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Overall Success Rate</span>
                <span class="text-sm font-medium text-green-600">{{ overallStats.completion_rate }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-green-600 h-2 rounded-full" :style="{ width: overallStats.completion_rate + '%' }"></div>
              </div>
              
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Error Rate</span>
                <span class="text-sm font-medium text-red-600">{{ overallStats.error_rate }}%</span>
              </div>
              <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-red-600 h-2 rounded-full" :style="{ width: overallStats.error_rate + '%' }"></div>
              </div>
              
              <div class="pt-4 border-t border-gray-200">
                <p class="text-xs text-gray-500">
                  Based on {{ overallStats.total_sessions }} sessions in the last 30 days
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  ussds: Array,
  overallStats: Object,
  recentActivity: Array,
  topServices: Array,
})

const refreshData = () => {
  router.reload()
}

const getStatusColor = (status) => {
  switch (status) {
    case 'success':
      return 'bg-green-100'
    case 'error':
      return 'bg-red-100'
    default:
      return 'bg-gray-100'
  }
}

const getStatusIconColor = (status) => {
  switch (status) {
    case 'success':
      return 'text-green-600'
    case 'error':
      return 'text-red-600'
    default:
      return 'text-gray-600'
  }
}

const formatTimestamp = (timestamp) => {
  const date = new Date(timestamp)
  const now = new Date()
  const diffInHours = Math.floor((now - date) / (1000 * 60 * 60))
  
  if (diffInHours < 1) {
    return 'Just now'
  } else if (diffInHours < 24) {
    return `${diffInHours}h ago`
  } else {
    return date.toLocaleDateString()
  }
}
</script> 