<template>
  <div class="api-configuration-wizard">
    <!-- Step Indicator -->
    <div class="mb-6">
      <div class="flex items-center justify-between">
        <div 
          v-for="(step, index) in steps" 
          :key="index"
          class="flex items-center"
        >
          <div 
            :class="[
              'w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium',
              currentStep > index ? 'bg-indigo-600 text-white' : 
              currentStep === index ? 'bg-indigo-100 text-indigo-600' : 
              'bg-gray-200 text-gray-500'
            ]"
          >
            {{ index + 1 }}
          </div>
          <span 
            :class="[
              'ml-2 text-sm font-medium',
              currentStep >= index ? 'text-indigo-600' : 'text-gray-500'
            ]"
          >
            {{ step.title }}
          </span>
          <div 
            v-if="index < steps.length - 1"
            :class="[
              'w-8 h-0.5 mx-4',
              currentStep > index ? 'bg-indigo-600' : 'bg-gray-200'
            ]"
          ></div>
        </div>
      </div>
    </div>

    <!-- Step Content -->
    <div class="step-content">
      <!-- Step 1: API Selection -->
      <div v-if="currentStep === 0" class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900">Choose an API Service</h3>
        <p class="text-gray-600">Select the marketplace API you want to integrate into your USSD flow.</p>
        
        <MarketplaceAPISelector
          :marketplace-apis="marketplaceApis"
          :custom-apis="customApis"
          :available-flows="availableFlows"
          :selected-api-id="selectedApi?.id"
          @api-selected="handleApiSelected"
          @configuration-changed="handleConfigurationChanged"
        />
      </div>

      <!-- Step 2: API Configuration -->
      <div v-if="currentStep === 1" class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900">Configure {{ selectedApi?.name }}</h3>
        <p class="text-gray-600">Set up how this API will work in your USSD flow.</p>
        
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <div class="flex items-center mb-3">
            <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium text-blue-900">API Details</span>
          </div>
          <div class="text-sm text-blue-800">
            <p><strong>Provider:</strong> {{ selectedApi?.provider_name }}</p>
            <p><strong>Method:</strong> {{ selectedApi?.method }}</p>
            <p><strong>Category:</strong> {{ selectedApi?.marketplace_category }}</p>
          </div>
        </div>

        <!-- Configuration Form -->
        <div v-if="selectedApi" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">What happens when the API call succeeds?</label>
            <select v-model="configuration.success_flow_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">Stay in same flow</option>
              <option value="end_session">End the session</option>
              <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">Go to {{ flow.name }}</option>
            </select>
          </div>
          
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">What happens when the API call fails?</label>
            <select v-model="configuration.error_flow_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-indigo-500 focus:border-indigo-500">
              <option value="">Stay in same flow</option>
              <option value="end_session">End the session</option>
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
            <label for="end-session" class="ml-2 text-sm text-gray-700">End session after API call (regardless of success/failure)</label>
          </div>
        </div>
        
        <div v-else class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-center">
          <p class="text-gray-500 text-sm">Please select an API in Step 1 to configure flow behavior.</p>
        </div>
      </div>

      <!-- Step 3: Test & Review -->
      <div v-if="currentStep === 2" class="space-y-4">
        <h3 class="text-lg font-semibold text-gray-900">Test & Review</h3>
        <p class="text-gray-600">Test the API connection to ensure everything is working correctly.</p>
        
        <!-- API Info Card -->
        <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
          <div class="flex items-center mb-2">
            <svg class="w-5 h-5 text-indigo-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h4 class="font-medium text-indigo-900">Selected API: {{ selectedApi?.name }}</h4>
          </div>
          <p class="text-sm text-indigo-700">{{ selectedApi?.provider_name }} • {{ selectedApi?.method }} • {{ getAuthTypeDisplayName(selectedApi?.auth_type) }}</p>
        </div>

        <!-- Authentication Details -->
        <div v-if="selectedApi?.auth_type && selectedApi?.auth_type !== 'none'" class="bg-blue-50 border border-blue-200 rounded-lg p-4">
          <h4 class="font-medium text-blue-900 mb-3">Authentication Configuration</h4>
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-blue-700">Type:</span>
              <span class="font-medium text-blue-900">{{ getAuthTypeDisplayName(selectedApi?.auth_type) }}</span>
            </div>
            <div class="mt-3">
              <div class="text-blue-700 font-medium mb-2">Authentication Status:</div>
              <div class="space-y-1">
                <div class="flex justify-between">
                  <span class="text-blue-600">Headers Configured:</span>
                  <span class="font-medium text-blue-900">
                    {{ hasHeadersConfigured(selectedApi) ? '✓ Yes' : '✗ No' }}
                  </span>
                </div>
                <div class="flex justify-between">
                  <span class="text-blue-600">Auth Config:</span>
                  <span class="font-medium text-blue-900">
                    {{ selectedApi?.auth_config && Object.keys(selectedApi.auth_config).length > 0 ? '✓ Yes' : '✗ No' }}
                  </span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Test API Button -->
        <div class="flex items-center gap-4">
          <button 
            @click="testApiConnection"
            :disabled="testing"
            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
          >
            <svg v-if="testing" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            {{ testing ? 'Testing...' : (testResult && !testResult.success ? 'Retry Test' : 'Test API Connection') }}
          </button>
          
          <button 
            v-if="testResult && !testResult.success"
            @click="clearTestResult"
            class="px-3 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Clear
          </button>
          
          <div v-if="testResult" class="w-full">
            <div class="flex items-center gap-2 mb-2">
              <svg v-if="testResult.success" class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              <svg v-else class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
              <span :class="testResult.success ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                {{ testResult.message }}
              </span>
            </div>
            
            <!-- Detailed error information -->
            <div v-if="!testResult.success && testResult.error" class="bg-red-50 border border-red-200 rounded-lg p-3">
              <div class="flex items-start justify-between">
                <div class="flex items-start flex-1">
                  <svg class="w-4 h-4 text-red-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                  </svg>
                  <div class="text-sm flex-1">
                    <div class="font-medium text-red-800 mb-1">Error Details:</div>
                    <div class="text-red-700">{{ testResult.error }}</div>
                    <div v-if="testResult.fullError && testResult.fullError !== testResult.error" class="mt-2 text-xs text-red-600 bg-red-100 p-2 rounded">
                      <div class="font-medium">Technical Details:</div>
                      <div class="font-mono">{{ testResult.fullError }}</div>
                    </div>
                  </div>
                </div>
                <button 
                  @click="copyErrorDetails"
                  class="ml-2 px-2 py-1 text-xs bg-red-100 text-red-700 hover:bg-red-200 rounded border border-red-300 flex items-center gap-1"
                  title="Copy error details to clipboard"
                >
                  <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                  </svg>
                  Copy
                </button>
              </div>
            </div>
            
            <!-- Success details -->
            <div v-if="testResult.success" class="bg-green-50 border border-green-200 rounded-lg p-3">
              <div class="flex items-start">
                <svg class="w-4 h-4 text-green-500 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div class="text-sm w-full">
                  <div class="font-medium text-green-800 mb-2">Response Details:</div>
                  <div class="space-y-2">
                    <div class="flex justify-between">
                      <span class="text-green-700 font-medium">Status Code:</span>
                      <span class="text-green-900 font-semibold">{{ testResult.statusCode || 'N/A' }}</span>
                    </div>
                    <div v-if="testResult.responseTime" class="flex justify-between">
                      <span class="text-green-700 font-medium">Response Time:</span>
                      <span class="text-green-900 font-semibold">{{ testResult.responseTime }}ms</span>
                    </div>
                    <div v-if="testResult.response" class="mt-3">
                      <button
                        @click="showFullResponseModal = true"
                        class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                      >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                        View Full Response
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation Buttons -->
    <div class="flex justify-between mt-8">
      <button 
        v-if="currentStep > 0"
        @click="previousStep"
        class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50"
      >
        Previous
      </button>
      <div v-else></div>
      
      <button 
        v-if="currentStep < steps.length - 1"
        @click="nextStep"
        :disabled="!canProceed"
        class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Next
      </button>
      <button 
        v-else
        @click="finish"
        :disabled="!canProceed"
        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
      >
        Complete Setup
      </button>
    </div>
  </div>

  <!-- Full Response Modal -->
  <div v-if="showFullResponseModal" class="fixed inset-0 z-50 overflow-y-auto" @click.self="showFullResponseModal = false">
    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
      <!-- Background overlay -->
      <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" @click="showFullResponseModal = false"></div>

      <!-- Modal panel -->
      <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
        <!-- Modal header -->
        <div class="bg-green-600 px-4 py-3 sm:px-6 flex items-center justify-between">
          <h3 class="text-lg font-medium text-white">Full API Response</h3>
          <button
            @click="showFullResponseModal = false"
            class="text-green-200 hover:text-white focus:outline-none"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Modal body -->
        <div class="bg-white px-4 py-5 sm:p-6">
          <div class="space-y-4">
            <!-- Response Summary -->
            <div class="grid grid-cols-2 gap-4 pb-4 border-b border-gray-200">
              <div>
                <span class="text-sm font-medium text-gray-500">Status Code:</span>
                <span class="ml-2 text-lg font-semibold text-gray-900">{{ testResult?.statusCode || 'N/A' }}</span>
              </div>
              <div v-if="testResult?.responseTime">
                <span class="text-sm font-medium text-gray-500">Response Time:</span>
                <span class="ml-2 text-lg font-semibold text-gray-900">{{ testResult.responseTime }}ms</span>
              </div>
            </div>

            <!-- Full Response JSON -->
            <div>
              <div class="flex items-center justify-between mb-2">
                <h4 class="text-sm font-medium text-gray-900">Response Data:</h4>
                <button
                  @click="copyResponseToClipboard"
                  class="inline-flex items-center px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                >
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                  </svg>
                  Copy
                </button>
              </div>
              <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 overflow-auto max-h-96">
                <pre class="text-xs text-gray-800 font-mono whitespace-pre-wrap">{{ JSON.stringify(testResult?.response, null, 2) }}</pre>
              </div>
            </div>
          </div>
        </div>

        <!-- Modal footer -->
        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
          <button
            @click="showFullResponseModal = false"
            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'

import MarketplaceAPISelector from './MarketplaceAPISelector.vue'

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
  }
})

const emit = defineEmits(['completed', 'cancelled'])

const currentStep = ref(0)
const selectedApi = ref(null)
const configuration = ref({
  success_flow_id: '',
  error_flow_id: '',
  end_session_after_api: false
})
const testing = ref(false)
const testResult = ref(null)
const showFullResponseModal = ref(false)

const steps = [
  { title: 'Select API' },
  { title: 'Configure' },
  { title: 'Test & Review' }
]

const canProceed = computed(() => {
  switch (currentStep.value) {
    case 0:
      return selectedApi.value !== null
    case 1:
      return true // Configuration is optional
    case 2:
      return true // Can always finish
    default:
      return false
  }
})

const nextStep = () => {
  if (canProceed.value && currentStep.value < steps.length - 1) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const handleApiSelected = (api) => {
  selectedApi.value = api
  testResult.value = null
}

const handleConfigurationChanged = (data) => {
  configuration.value = data.configuration
}

const testApiConnection = async () => {
  testing.value = true
  testResult.value = null

  try {
    
    // Make secure server-side API test call
    const response = await fetch(`/integration/${selectedApi.value.id}/test`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      }
    })

    // Check response status
    if (!response.ok) {
      let errorMessage = `HTTP ${response.status} Error`
      let errorDetails = `Status: ${response.status} ${response.statusText}`
      
      try {
        const errorData = await response.json()
        errorMessage = errorData.message || errorData.error || errorMessage
        errorDetails = errorData.error || errorDetails
      } catch (parseError) {
        // Use default error message
      }

      throw new Error(`${errorMessage} (Status: ${response.status})`)
    }

    // Parse JSON response
    const data = await response.json()

    // Success result
    testResult.value = {
      success: data.success,
      message: data.message,
      statusCode: data.status_code || null,
      responseTime: data.response_time || null,
      response: data.response || null
    }

  } catch (error) {
    // Enhanced error message with more details
    let errorMessage = 'API connection failed.'
    let errorDetails = error.message

    testResult.value = {
      success: false,
      message: errorMessage,
      error: errorDetails,
      fullError: error.message
    }

  } finally {
    testing.value = false
  }
}



const getFlowName = (flowId) => {
  if (!flowId) return 'Stay in same flow'
  if (flowId === 'end_session') return 'End session'
  
  const flow = props.availableFlows.find(f => f.id == flowId)
  return flow ? flow.name : 'Unknown flow'
}


// Get authentication type display name
const getAuthTypeDisplayName = (authType) => {
  const authTypes = {
    'api_key': 'API Key',
    'bearer_token': 'Bearer Token',
    'basic': 'Basic Authentication',
    'oauth': 'OAuth 2.0',
    'custom': 'Custom Headers',
    'none': 'No Authentication'
  }
  return authTypes[authType] || 'Unknown'
}


const clearTestResult = () => {
  testResult.value = null
}

// Check if headers are configured (supports both [{key, value}] array and {key: value} object formats)
const hasHeadersConfigured = (api) => {
  if (!api || !api.headers) return false
  
  // Check if headers is an array format [{key, value}]
  if (Array.isArray(api.headers)) {
    return api.headers.length > 0 && api.headers.some(h => h && h.key && h.value)
  }
  
  // Check if headers is an object format {key: value}
  if (typeof api.headers === 'object') {
    return Object.keys(api.headers).length > 0
  }
  
  return false
}

const copyErrorDetails = async () => {
  if (!testResult.value || testResult.value.success) return
  
  const errorText = `API Test Error:
${testResult.value.message}

Error Details: ${testResult.value.error}

Technical Details: ${testResult.value.fullError || 'N/A'}

API Configuration:
- Endpoint: ${selectedApi.value?.endpoint_url}
- Method: ${selectedApi.value?.method}
- Auth Type: ${selectedApi.value?.auth_type}
- Note: Authentication details are handled securely on the server-side`

  try {
    await navigator.clipboard.writeText(errorText)
  } catch (err) {
    // Silent fail
  }
}

const copyResponseToClipboard = async () => {
  if (!testResult.value || !testResult.value.response) return
  
  const responseText = JSON.stringify(testResult.value.response, null, 2)
  
  try {
    await navigator.clipboard.writeText(responseText)
  } catch (err) {
    // Silent fail
  }
}

const finish = () => {
  emit('completed', {
    api: selectedApi.value,
    configuration: configuration.value
  })
}
</script>

