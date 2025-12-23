<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Production Management - {{ ussd.name }}
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900">
            
            <!-- Status Overview -->
            <div class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Current Status</h3>
              
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Environment Status -->
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="flex items-center">
                    <div :class="getStatusIconClass()" class="flex-shrink-0">
                      <svg v-if="ussd.environment === 'live'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                      <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium text-gray-900">
                        {{ ussd.environment === 'live' ? 'Live Production' : 'Testing Mode' }}
                      </p>
                      <p class="text-sm text-gray-500">
                        {{ ussd.environment === 'live' ? 'Service is live and accessible' : 'Service is in testing mode' }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- USSD Code -->
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 text-blue-600">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                      </svg>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium text-gray-900">USSD Code</p>
                      <p class="text-sm text-gray-500 font-mono">
                        {{ ussd.live_ussd_code || ussd.testing_ussd_code || 'Not configured' }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Gateway Provider -->
                <div class="bg-gray-50 p-4 rounded-lg">
                  <div class="flex items-center">
                    <div class="flex-shrink-0 text-green-600">
                      <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd" />
                      </svg>
                    </div>
                    <div class="ml-3">
                      <p class="text-sm font-medium text-gray-900">Gateway</p>
                      <p class="text-sm text-gray-500">
                        {{ ussd.gateway_provider || 'Not configured' }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Requirements Checklist -->
            <div class="mb-8">
              <h3 class="text-lg font-medium text-gray-900 mb-4">Requirements Checklist</h3>
              
              <div class="space-y-3">
                <div class="flex items-center">
                  <div :class="getCheckIconClass(ussd.business?.registration_status === 'verified')" class="flex-shrink-0">
                    <svg v-if="ussd.business?.registration_status === 'verified'" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <span class="ml-3 text-sm text-gray-700">Business verification completed</span>
                </div>

                <div class="flex items-center">
                  <div :class="getCheckIconClass(ussd.gateway_provider)" class="flex-shrink-0">
                    <svg v-if="ussd.gateway_provider" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <span class="ml-3 text-sm text-gray-700">Gateway provider configured</span>
                </div>

                <div class="flex items-center">
                  <div :class="getCheckIconClass(ussd.gateway_credentials)" class="flex-shrink-0">
                    <svg v-if="ussd.gateway_credentials" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <span class="ml-3 text-sm text-gray-700">Gateway credentials set</span>
                </div>

                <div class="flex items-center">
                  <div :class="getCheckIconClass(ussd.webhook_url)" class="flex-shrink-0">
                    <svg v-if="ussd.webhook_url" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <span class="ml-3 text-sm text-gray-700">Webhook URL configured</span>
                </div>

                <div class="flex items-center">
                  <div :class="getCheckIconClass(ussd.business?.account_balance >= 10)" class="flex-shrink-0">
                    <svg v-if="ussd.business?.account_balance >= 10" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                    <svg v-else class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <span class="ml-3 text-sm text-gray-700">
                    Sufficient account balance (min. $10) - Current: ${{ ussd.business?.account_balance || 0 }}
                  </span>
                </div>
              </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex space-x-4">
              <button
                v-if="ussd.environment === 'testing' && canGoLive"
                @click="goLive"
                :disabled="loading"
                class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-6 py-2 rounded-md font-medium transition-colors"
              >
                <span v-if="loading">Processing...</span>
                <span v-else>Go Live</span>
              </button>

              <button
                v-if="ussd.environment === 'live'"
                @click="goTesting"
                :disabled="loading"
                class="bg-yellow-600 hover:bg-yellow-700 disabled:bg-gray-400 text-white px-6 py-2 rounded-md font-medium transition-colors"
              >
                <span v-if="loading">Processing...</span>
                <span v-else>Switch to Testing</span>
              </button>

              <button
                v-if="!canGoLive && ussd.environment === 'testing'"
                @click="configureGateway"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition-colors"
              >
                Configure Gateway
              </button>
            </div>

            <!-- Self-Service Notice -->
            <div v-if="canGoLive && ussd.environment === 'testing'" class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-blue-800">Ready to Go Live!</h3>
                  <div class="mt-2 text-sm text-blue-700">
                    <p>All requirements are met! You can now switch to production mode yourself. This will connect your USSD service to real users via AfricasTalking and start billing per session.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Warning Messages -->
            <div v-if="!canGoLive && ussd.environment === 'testing'" class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <h3 class="text-sm font-medium text-yellow-800">
                    Requirements not met
                  </h3>
                  <div class="mt-2 text-sm text-yellow-700">
                    <p>Please complete all requirements above before going live.</p>
                  </div>
                </div>
              </div>
            </div>

            <!-- Success Message -->
            <div v-if="successMessage" class="mt-6 p-4 bg-green-50 border border-green-200 rounded-md">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-green-800">
                    {{ successMessage }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Error Message -->
            <div v-if="errorMessage" class="mt-6 p-4 bg-red-50 border border-red-200 rounded-md">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm font-medium text-red-800">
                    {{ errorMessage }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
  ussd: Object
})

const loading = ref(false)
const successMessage = ref('')
const errorMessage = ref('')

const canGoLive = computed(() => {
  return props.ussd.business?.registration_status === 'verified' &&
         props.ussd.gateway_provider &&
         props.ussd.gateway_credentials &&
         props.ussd.webhook_url &&
         (props.ussd.business?.account_balance || 0) >= 10
})

const getStatusIconClass = () => {
  return props.ussd.environment === 'live' 
    ? 'text-green-600' 
    : 'text-yellow-600'
}

const getCheckIconClass = (condition) => {
  return condition 
    ? 'text-green-600' 
    : 'text-red-600'
}

const goLive = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await router.post(route('ussd.go-live', props.ussd.id))
    
    if (response.ok) {
      successMessage.value = 'USSD is now live! Your service is available to users.'
      // Refresh the page to update the status
      window.location.reload()
    } else {
      const data = await response.json()
      errorMessage.value = data.message || 'Failed to go live. Please try again.'
    }
  } catch (error) {
    errorMessage.value = 'An error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}

const goTesting = async () => {
  loading.value = true
  errorMessage.value = ''
  successMessage.value = ''

  try {
    const response = await router.post(route('ussd.go-testing', props.ussd.id))
    
    if (response.ok) {
      successMessage.value = 'USSD switched to testing mode.'
      // Refresh the page to update the status
      window.location.reload()
    } else {
      const data = await response.json()
      errorMessage.value = data.message || 'Failed to switch to testing mode. Please try again.'
    }
  } catch (error) {
    errorMessage.value = 'An error occurred. Please try again.'
  } finally {
    loading.value = false
  }
}

const configureGateway = () => {
  router.visit(route('ussd.edit', props.ussd.id))
}

onMounted(() => {
  // Clear messages after 5 seconds
  setTimeout(() => {
    successMessage.value = ''
    errorMessage.value = ''
  }, 5000)
})
</script>
