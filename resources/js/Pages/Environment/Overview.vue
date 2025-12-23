<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Environment Management Overview
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Environment Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <!-- Total USSDs -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md p-3 bg-blue-500">
                  <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Total USSDs</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ ussdsWithStatus.length }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Live USSDs -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md p-3 bg-green-500">
                  <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Live Services</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ liveCount }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Testing USSDs -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md p-3 bg-blue-500">
                  <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Testing Services</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ testingCount }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Ready to Go Live -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md p-3 bg-yellow-500">
                  <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Ready to Go Live</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ readyToGoLiveCount }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- USSD Services List -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center justify-between mb-6">
              <h3 class="text-lg font-medium text-gray-900">USSD Services Environment Status</h3>
              <p class="text-sm text-gray-500">Manage your USSD services environments</p>
            </div>

            <div class="space-y-4">
              <div v-for="item in ussdsWithStatus" :key="item.ussd.id" 
                   class="border border-gray-200 rounded-lg p-4 hover:border-gray-300 transition-colors">
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-4">
                    <!-- Environment Status Indicator -->
                    <div class="flex items-center space-x-2">
                      <div class="w-3 h-3 rounded-full"
                           :class="item.environmentStatus.is_live ? 'bg-green-500' : getRequirementsStatusDotColor(item.environmentStatus.requirements)"></div>
                      <span class="text-sm font-medium"
                            :class="item.environmentStatus.is_live ? 'text-green-600' : getRequirementsStatusColor(item.environmentStatus.requirements, 'text')">
                        {{ item.environmentStatus.is_live ? 'Production' : 'Testing' }}
                      </span>
                    </div>

                    <!-- USSD Info -->
                    <div>
                      <h4 class="text-lg font-medium text-gray-900">{{ item.ussd.name }}</h4>
                      <p class="text-sm text-gray-500">{{ item.ussd.description }}</p>
                      <p class="text-xs text-gray-400">USSD Code: {{ item.environmentStatus.current_ussd_code || 'Not configured' }}</p>
                    </div>
                  </div>

                  <div class="flex items-center space-x-3">
                    <!-- Requirements Status -->
                    <div class="text-right">
                      <div class="flex items-center space-x-1">
                        <span class="text-sm text-gray-500">Requirements:</span>
                        <span class="text-sm font-medium"
                              :class="getRequirementsStatusColor(item.environmentStatus.requirements, 'text')">
                          {{ item.environmentStatus.all_requirements_met ? 'Met' : 'Pending' }}
                        </span>
                      </div>
                      <p class="text-xs"
                         :class="getRequirementsStatusColor(item.environmentStatus.requirements, 'text')">
                        {{ getMetRequiredCount(item.environmentStatus.requirements) }}/{{ getTotalRequiredCount(item.environmentStatus.requirements) }} required met
                      </p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex items-center space-x-2">
                      <Link
                        :href="route('ussd.environment', { ussd: item.ussd.id })"
                        class="px-3 py-1 text-sm font-medium text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-md transition-colors"
                      >
                        Manage
                      </Link>
                      
                      <Link
                        :href="route('ussd.show', { ussd: item.ussd.id })"
                        class="px-3 py-1 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50 rounded-md transition-colors"
                      >
                        View
                      </Link>
                    </div>
                  </div>
                </div>

                <!-- Requirements Progress Bar -->
                <div class="mt-3">
                  <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                    <span>Requirements Progress</span>
                    <span>{{ getMetRequiredCount(item.environmentStatus.requirements) }}/{{ getTotalRequiredCount(item.environmentStatus.requirements) }} required</span>
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="h-2 rounded-full transition-all duration-300"
                         :class="getRequirementsStatusColor(item.environmentStatus.requirements, 'bg')"
                         :style="{ width: getRequirementsPercentage(item.environmentStatus.requirements) + '%' }"></div>
                  </div>
                </div>

                <!-- Quick Status -->
                <div class="mt-3 flex items-center space-x-4 text-xs">
                  <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-gray-500">
                      {{ item.environmentStatus.session_stats?.total?.total || 0 }} total sessions
                    </span>
                  </div>
                  <div class="flex items-center space-x-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                    </svg>
                    <span class="text-gray-500">
                      {{ item.environmentStatus.session_stats?.today?.total || 0 }} today
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Empty State -->
            <div v-if="ussdsWithStatus.length === 0" class="text-center py-12">
              <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 class="mt-2 text-sm font-medium text-gray-900">No USSD services</h3>
              <p class="mt-1 text-sm text-gray-500">Get started by creating your first USSD service.</p>
              <div class="mt-6">
                <Link
                  :href="route('ussd.create')"
                  class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                >
                  <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                  </svg>
                  Create USSD Service
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  ussdsWithStatus: Array
})

// Computed properties for summary cards
const liveCount = computed(() => {
  return props.ussdsWithStatus.filter(item => item.environmentStatus.is_live).length
})

const testingCount = computed(() => {
  return props.ussdsWithStatus.filter(item => item.environmentStatus.is_testing).length
})

const readyToGoLiveCount = computed(() => {
  return props.ussdsWithStatus.filter(item => 
    item.environmentStatus.is_testing && item.environmentStatus.all_requirements_met
  ).length
})

// Helper functions
const getMetRequirementsCount = (requirements) => {
  if (!requirements || typeof requirements !== 'object') return 0
  // Count all requirements that are met (status === true)
  return Object.values(requirements).filter(req => req && req.status === true).length
}

const getTotalRequiredCount = (requirements) => {
  if (!requirements || typeof requirements !== 'object') return 0
  // Count all required requirements
  return Object.values(requirements).filter(req => req && req.required === true).length
}

const getTotalRequirementsCount = (requirements) => {
  if (!requirements || typeof requirements !== 'object') return 0
  // Count all requirements (required and optional)
  return Object.keys(requirements).length
}

const getMetRequiredCount = (requirements) => {
  if (!requirements || typeof requirements !== 'object') return 0
  // Count required requirements that are met
  return Object.values(requirements).filter(req => req && req.required === true && req.status === true).length
}

const getRequirementsPercentage = (requirements) => {
  const met = getMetRequiredCount(requirements)
  const total = getTotalRequiredCount(requirements)
  return total > 0 ? Math.round((met / total) * 100) : 0
}

const getRequirementsStatusColor = (requirements, type = 'bg') => {
  const percentage = getRequirementsPercentage(requirements)
  
  // Green: all requirements met (100%)
  if (percentage === 100) {
    return type === 'bg' ? 'bg-green-600' : 'text-green-600'
  }
  
  // Yellow: more than half met (>50%)
  if (percentage > 50) {
    return type === 'bg' ? 'bg-yellow-500' : 'text-yellow-600'
  }
  
  // Blue: default (50% or less)
  return type === 'bg' ? 'bg-blue-600' : 'text-blue-600'
}

const getRequirementsStatusDotColor = (requirements) => {
  const percentage = getRequirementsPercentage(requirements)
  
  // Green: all requirements met (100%)
  if (percentage === 100) {
    return 'bg-green-500'
  }
  
  // Yellow: more than half met (>50%)
  if (percentage > 50) {
    return 'bg-yellow-500'
  }
  
  // Blue: default (50% or less)
  return 'bg-blue-500'
}
</script>
