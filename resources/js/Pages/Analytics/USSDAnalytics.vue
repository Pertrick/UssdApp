<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-bold text-gray-900">{{ ussd.name }} Analytics</h2>
          <p class="text-sm text-gray-600">{{ ussd.pattern }}</p>
        </div>
        <div class="flex items-center gap-4">
          <div class="flex items-center space-x-2">
            <input 
              v-model="dateRange.start_date" 
              type="date" 
              class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
            <span class="text-gray-500">to</span>
            <input 
              v-model="dateRange.end_date" 
              type="date" 
              class="px-3 py-1 text-sm border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
            >
            <button @click="updateDateRange" class="px-3 py-1 text-xs bg-blue-100 text-blue-600 rounded hover:bg-blue-200">
              Update
            </button>
          </div>
          <button @click="refreshData" class="px-3 py-1 text-xs bg-green-100 text-green-600 rounded hover:bg-green-200">
            Refresh
          </button>
          <a
            :href="route('analytics.export.ussd', ussd.id)" 
            class="px-3 py-1 text-xs bg-purple-100 text-purple-600 rounded hover:bg-purple-200"
          >
            Export
          </a>
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Analytics Summary Cards -->
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
                <p class="text-2xl font-bold text-gray-900">{{ analytics.total_sessions || 0 }}</p>
                <p class="text-xs text-gray-500">Selected period</p>
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
                <p class="text-2xl font-bold text-gray-900">{{ analytics.completed_sessions || 0 }}</p>
                <p class="text-xs text-green-600">{{ analytics.completion_rate || 0 }}% success rate</p>
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
                <p class="text-2xl font-bold text-gray-900">{{ analytics.total_interactions || 0 }}</p>
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
                <p class="text-2xl font-bold text-gray-900">{{ analytics.average_session_duration || 0 }}s</p>
                <p class="text-xs text-gray-500">Seconds per session</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <!-- Daily Sessions Chart -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Daily Sessions</h3>
            <div v-if="chartsData.daily_sessions && chartsData.daily_sessions.length > 0" class="h-64">
              <!-- Chart placeholder - you can integrate Chart.js or other charting library here -->
              <div class="flex items-end justify-between h-48 space-x-2">
                <div 
                  v-for="(day, index) in chartsData.daily_sessions" 
                  :key="index"
                  class="flex-1 bg-blue-200 rounded-t"
                  :style="{ height: (day.count / maxDailySessions * 100) + '%' }"
                ></div>
              </div>
              <div class="flex justify-between text-xs text-gray-500 mt-2">
                <span>{{ chartsData.daily_sessions[0]?.date }}</span>
                <span>{{ chartsData.daily_sessions[chartsData.daily_sessions.length - 1]?.date }}</span>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              No data available for the selected period
            </div>
          </div>

          <!-- Hourly Distribution Chart -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Hourly Distribution</h3>
            <div v-if="chartsData.hourly_distribution && chartsData.hourly_distribution.length > 0" class="h-64">
              <!-- Chart placeholder -->
              <div class="flex items-end justify-between h-48 space-x-1">
                <div 
                  v-for="(hour, index) in chartsData.hourly_distribution" 
                  :key="index"
                  class="flex-1 bg-green-200 rounded-t"
                  :style="{ height: (hour.count / maxHourlyCount * 100) + '%' }"
                ></div>
              </div>
              <div class="flex justify-between text-xs text-gray-500 mt-2">
                <span>00:00</span>
                <span>23:00</span>
              </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">
              No data available for the selected period
            </div>
          </div>
        </div>

        <!-- Flow Performance -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Flow Performance</h3>
          <div v-if="flowPerformance.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500">No flow performance data available</p>
          </div>
          <div v-else class="space-y-4">
            <div v-for="flow in flowPerformance" :key="flow.flow_id" class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
              <div class="flex items-center space-x-4">
                <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center">
                  <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                  </svg>
                </div>
                <div>
                  <h4 class="text-sm font-medium text-gray-900">{{ flow.flow_name }}</h4>
                  <p class="text-xs text-gray-500">{{ flow.total_interactions }} interactions</p>
                </div>
              </div>
              <div class="flex items-center space-x-6">
                <div class="text-right">
                  <p class="text-sm font-medium text-red-600">{{ flow.error_count }}</p>
                  <p class="text-xs text-gray-500">Errors</p>
                </div>
                <div class="text-right">
                  <p class="text-sm font-medium" :class="flow.error_rate > 10 ? 'text-red-600' : 'text-green-600'">
                    {{ flow.error_rate }}%
                  </p>
                  <p class="text-xs text-gray-500">Error Rate</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Error Analysis -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Error Analysis</h3>
          <div v-if="errorAnalysis.recent_errors.length === 0" class="text-center py-8">
            <svg class="mx-auto h-12 w-12 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="mt-2 text-sm text-gray-500">No errors found in the selected period</p>
          </div>
          <div v-else class="space-y-4">
            <div v-for="error in errorAnalysis.recent_errors.slice(0, 10)" :key="error.id" class="p-4 bg-red-50 border border-red-200 rounded-lg">
              <div class="flex items-start justify-between">
                <div class="flex-1">
                  <p class="text-sm font-medium text-red-900">{{ error.action_type }}</p>
                  <p class="text-xs text-red-700 mt-1">{{ error.error_message }}</p>
                  <p class="text-xs text-gray-500 mt-1">Flow: {{ error.flow_name }}</p>
                  <p v-if="error.input_data" class="text-xs text-gray-500 mt-1">Input: {{ error.input_data }}</p>
                </div>
                <div class="text-right">
                  <p class="text-xs text-gray-500">{{ formatTimestamp(error.timestamp) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Type Distribution -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Action Type Distribution</h3>
          <div v-if="chartsData.action_type_distribution && chartsData.action_type_distribution.length > 0" class="space-y-3">
            <div v-for="action in chartsData.action_type_distribution" :key="action.action_type" class="flex items-center justify-between">
              <span class="text-sm font-medium text-gray-900">{{ action.action_type }}</span>
              <div class="flex items-center space-x-3">
                <div class="w-32 bg-gray-200 rounded-full h-2">
                  <div 
                    class="bg-indigo-600 h-2 rounded-full" 
                    :style="{ width: (action.count / maxActionCount * 100) + '%' }"
                  ></div>
                </div>
                <span class="text-sm text-gray-600 w-12 text-right">{{ action.count }}</span>
              </div>
            </div>
          </div>
          <div v-else class="text-center py-8 text-gray-500">
            No action type data available
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { showToast } from '@/helpers/toast'

const props = defineProps({
  ussd: Object,
  analytics: Object,
  chartsData: Object,
  flowPerformance: Array,
  errorAnalysis: Object,
  dateRange: Object,
})

// Ensure proper date range with defaults
const dateRange = ref({
  start_date: props.dateRange?.start_date || getDefaultStartDate(),
  end_date: props.dateRange?.end_date || getDefaultEndDate(),
})

// Helper functions to get proper default dates
function getDefaultEndDate() {
  const today = new Date()
  return today.toISOString().split('T')[0] // YYYY-MM-DD format
}

function getDefaultStartDate() {
  const thirtyDaysAgo = new Date()
  thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30)
  return thirtyDaysAgo.toISOString().split('T')[0] // YYYY-MM-DD format
}

// Validate and fix date range if needed
function validateDateRange() {
  const start = new Date(dateRange.value.start_date)
  const end = new Date(dateRange.value.end_date)
  
  if (start > end) {
    // Swap dates if they're in wrong order
    const temp = dateRange.value.start_date
    dateRange.value.start_date = dateRange.value.end_date
    dateRange.value.end_date = temp
  }
}

// Validate dates on component mount
onMounted(() => {
  validateDateRange()
})

const refreshData = () => {
  showToast.info('Refreshing analytics data...')
  router.reload()
}

const updateDateRange = () => {
  validateDateRange() // Validate before sending
  showToast.info('Updating date range...')
  router.get(route('analytics.ussd', props.ussd.id), {
    start_date: dateRange.value.start_date,
    end_date: dateRange.value.end_date,
  })
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

// Computed properties for chart scaling
const maxDailySessions = computed(() => {
  if (!props.chartsData?.daily_sessions) return 1
  return Math.max(...props.chartsData.daily_sessions.map(d => d.count))
})

const maxHourlyCount = computed(() => {
  if (!props.chartsData?.hourly_distribution) return 1
  return Math.max(...props.chartsData.hourly_distribution.map(h => h.count))
})

const maxActionCount = computed(() => {
  if (!props.chartsData?.action_type_distribution) return 1
  return Math.max(...props.chartsData.action_type_distribution.map(a => a.count))
})
</script> 