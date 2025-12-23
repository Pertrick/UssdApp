<template>
  <div class="marketplace-api-selector">
    <!-- Search and Filter -->
    <div class="mb-4">
      <div class="flex gap-2 mb-3">
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Search APIs..." 
          class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500"
        />
        <select v-model="selectedCategory" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
          <option value="">All Categories</option>
          <option value="airtime">Airtime</option>
          <option value="banking">Banking</option>
          <option value="payment">Payment</option>
          <option value="utility">Utility</option>
        </select>
      </div>
    </div>

    <!-- API Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div 
        v-for="api in filteredApis" 
        :key="api.id"
        @click="selectApi(api)"
        :class="[
          'api-card cursor-pointer border-2 rounded-lg p-4 transition-all duration-200 hover:shadow-md',
          selectedApiId === api.id ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300'
        ]"
      >
        <!-- API Header -->
        <div class="flex items-center justify-between mb-3">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
              <span class="text-indigo-600 font-semibold text-sm">{{ api.provider_name?.charAt(0) || 'A' }}</span>
            </div>
            <div>
              <h3 class="font-semibold text-gray-900 text-sm">{{ api.name }}</h3>
              <p class="text-xs text-gray-500">{{ api.provider_name }}</p>
            </div>
          </div>
          <div class="flex items-center gap-1">
            <span :class="getStatusBadgeClass(api.test_status)" class="px-2 py-1 rounded-full text-xs font-medium">
              {{ api.test_status }}
            </span>
          </div>
        </div>

        <!-- API Description -->
        <p class="text-sm text-gray-600 mb-3 line-clamp-2">{{ api.description || 'No description available' }}</p>

        <!-- API Details -->
        <div class="flex items-center justify-between text-xs text-gray-500">
          <span class="flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m-9 0h10m-10 0a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V6a2 2 0 00-2-2"></path>
            </svg>
            {{ api.method }}
          </span>
          <span class="flex items-center gap-1">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
            </svg>
            {{ api.marketplace_category }}
          </span>
        </div>

        <!-- Selection Indicator -->
        <div v-if="selectedApiId === api.id" class="mt-3 flex items-center justify-center">
          <div class="flex items-center gap-2 text-indigo-600">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span class="text-sm font-medium">Selected</span>
          </div>
        </div>
      </div>
    </div>

    <!-- No Results -->
    <div v-if="filteredApis.length === 0" class="text-center py-8">
      <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
      </svg>
      <p class="text-gray-500">No APIs found matching your search criteria.</p>
    </div>

    <!-- Selected API Configuration -->
    <div v-if="selectedApi" class="mt-6 p-4 bg-gray-50 rounded-lg border">
      <h4 class="font-semibold text-gray-900 mb-3">Configure {{ selectedApi.name }}</h4>
      
      <!-- API Configuration Form -->
      <div class="space-y-3">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Success Flow</label>
          <select v-model="configuration.success_flow_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Stay in same flow</option>
            <option value="end_session">End session</option>
            <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">Go to {{ flow.name }}</option>
          </select>
        </div>
        
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Error Flow</label>
          <select v-model="configuration.error_flow_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Stay in same flow</option>
            <option value="end_session">End session</option>
            <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">Go to {{ flow.name }}</option>
          </select>
        </div>
        
        <div class="flex items-center">
          <input 
            type="checkbox" 
            v-model="configuration.end_session_after_api" 
            id="end-session" 
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
          >
          <label for="end-session" class="ml-2 text-sm text-gray-700">End session after API call</label>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
  marketplaceApis: {
    type: Array,
    default: () => []
  },
  customApis: {
    type: Array,
    default: () => []
  },
  availableFlows: {
    type: Array,
    default: () => []
  },
  selectedApiId: {
    type: [String, Number],
    default: null
  }
})

const emit = defineEmits(['api-selected', 'configuration-changed'])

const searchQuery = ref('')
const selectedCategory = ref('')
const selectedApi = ref(null)
const configuration = ref({
  success_flow_id: '',
  error_flow_id: '',
  end_session_after_api: false
})

// Combine all APIs
const allApis = computed(() => [...props.marketplaceApis, ...props.customApis])

// Filter APIs based on search and category
const filteredApis = computed(() => {
  let filtered = allApis.value

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase()
    filtered = filtered.filter(api => 
      api.name.toLowerCase().includes(query) ||
      api.provider_name?.toLowerCase().includes(query) ||
      api.description?.toLowerCase().includes(query)
    )
  }

  if (selectedCategory.value) {
    filtered = filtered.filter(api => api.marketplace_category === selectedCategory.value)
  }

  return filtered
})

// Watch for selected API changes
watch(() => props.selectedApiId, (newId) => {
  if (newId) {
    selectedApi.value = allApis.value.find(api => api.id == newId)
  } else {
    selectedApi.value = null
  }
}, { immediate: true })

// Watch for configuration changes
watch(configuration, (newConfig) => {
  if (selectedApi.value) {
    emit('configuration-changed', {
      api: selectedApi.value,
      configuration: newConfig
    })
  }
}, { deep: true })

const selectApi = (api) => {
  selectedApi.value = api
  emit('api-selected', api)
}

const getStatusBadgeClass = (status) => {
  switch (status) {
    case 'success':
      return 'bg-green-100 text-green-800'
    case 'failed':
      return 'bg-red-100 text-red-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
</style>