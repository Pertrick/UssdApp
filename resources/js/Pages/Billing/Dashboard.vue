<template>
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        Billing Dashboard
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <!-- Environment Status & Data View -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-medium text-gray-900">Environment & Data View</h3>
                <p class="text-sm text-gray-500 mt-1">
                  Current USSD environment and billing data view
                </p>
              </div>
              <div class="flex items-center space-x-4">
                <!-- USSD Environment Indicator -->
                <div class="flex items-center space-x-2">
                  <span class="text-sm text-gray-600">USSD Environment:</span>
                  <div class="flex items-center space-x-2 px-3 py-1 rounded-full text-sm font-medium"
                       :class="ussdEnvironment === 'live' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
                    <div class="w-2 h-2 rounded-full"
                         :class="ussdEnvironment === 'live' ? 'bg-green-500' : 'bg-blue-500'"></div>
                    <span>{{ ussdEnvironment === 'live' ? 'Production' : 'Testing' }}</span>
                  </div>
                </div>
                
                <!-- Data View Override -->
                <div class="flex items-center space-x-2">
                  <span class="text-sm text-gray-600">Viewing:</span>
                  <div class="flex items-center space-x-2">
                    <span class="text-sm font-medium" :class="isViewingOverride ? 'text-orange-600' : 'text-gray-600'">
                      {{ currentDataView }}
                    </span>
                    <button
                      v-if="!isViewingOverride"
                      @click="showOverrideModal = true"
                      class="text-xs text-blue-600 hover:text-blue-800 underline"
                    >
                      View different data
                    </button>
                    <button
                      v-else
                      @click="resetToDefaultView"
                      class="text-xs text-gray-600 hover:text-gray-800 underline"
                    >
                      Reset to default
                    </button>
                  </div>
                </div>
                
                <!-- Period Selector -->
                <div class="flex items-center space-x-2">
                  <span class="text-sm text-gray-600">Period:</span>
                  <select 
                    v-model="selectedPeriod" 
                    @change="loadBillingData"
                    class="border border-gray-300 rounded-md px-3 py-1 text-sm focus:ring-blue-500 focus:border-blue-500"
                  >
                    <option value="today">Today</option>
                    <option value="week">This Week</option>
                    <option value="month">This Month</option>
                    <option value="year">This Year</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Account Balance Card -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-medium text-gray-900">
                  {{ currentDataView === 'Production Data' ? 'Production' : 'Test' }} Account Balance
                </h3>
                <p class="text-3xl font-bold" :class="currentDataView === 'Production Data' ? 'text-green-600' : 'text-blue-600'">
                  {{ currencySymbol }}{{ formatCurrency(currentBalance) }}
                </p>
                <p class="text-sm text-gray-500 mt-1">
                  {{ currentDataView === 'Production Data' ? 'Available for live USSD sessions' : 'Available for testing and simulation' }}
                </p>
                <div v-if="currentDataView === 'Testing Data'" class="mt-2">
                  <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                    Simulated billing - no real charges
                  </span>
                </div>
              </div>
              <div class="text-right">
                <button
                  v-if="currentDataView === 'Production Data'"
                  @click="showAddFundsModal = true"
                  class="px-4 py-2 rounded-md font-medium text-white transition-colors bg-green-600 hover:bg-green-700"
                >
                  Add Funds
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Real-time Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
          <!-- Today's Sessions -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md p-3" :class="currentDataView === 'Production Data' ? 'bg-green-500' : 'bg-blue-500'">
                  <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Today's Sessions</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ currentStats.today.sessions }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Today's Cost -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md p-3" :class="currentDataView === 'Production Data' ? 'bg-green-500' : 'bg-blue-500'">
                  <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Today's Cost</p>
                  <p class="text-2xl font-semibold text-gray-900">
                    {{ currencySymbol }}{{ formatCurrency(currentStats.today.amount) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- This Month Sessions -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md p-3" :class="currentDataView === 'Production Data' ? 'bg-green-500' : 'bg-blue-500'">
                  <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">This Month</p>
                  <p class="text-2xl font-semibold text-gray-900">{{ currentStats.this_month.sessions }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- This Month Cost -->
          <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6">
              <div class="flex items-center">
                <div class="flex-shrink-0 rounded-md p-3" :class="currentDataView === 'Production Data' ? 'bg-green-500' : 'bg-blue-500'">
                  <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                </div>
                <div class="ml-4">
                  <p class="text-sm font-medium text-gray-500">Month Cost</p>
                  <p class="text-2xl font-semibold text-gray-900">
                    {{ currencySymbol }}{{ formatCurrency(currentStats.this_month.amount) }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Sessions Table -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900">
                Recent Sessions
                <span class="text-sm text-gray-500 ml-2">
                  ({{ currentDataView === 'Production Data' ? 'Live Production' : 'Testing/Simulation' }})
                </span>
              </h3>
            </div>

            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Session ID
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Phone Number
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      USSD Service
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Amount
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Status
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                      Date
                    </th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  <tr v-for="session in filteredSessions" :key="session.id">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                      {{ session.session_id }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ session.phone_number }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ session.ussd.name }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                      {{ currencySymbol }}{{ formatCurrency(session.billing_amount) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                      <span :class="getStatusClass(session.billing_status)" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                        {{ session.billing_status }}
                      </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                      {{ formatDate(session.billed_at) }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- Pagination -->
            <div v-if="filteredSessions.length === 0" class="text-center py-8">
              <p class="text-gray-500">No sessions found for this period and environment.</p>
            </div>
          </div>
        </div>

        <!-- Data View Override Modal -->
        <Modal :show="showOverrideModal" @close="showOverrideModal = false">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">View Different Data</h3>
            
            <div class="space-y-4">
              <p class="text-sm text-gray-600">
                Your USSD is currently in <strong>{{ ussdEnvironment === 'live' ? 'Production' : 'Testing' }}</strong> mode. 
                You can view data from a different environment if needed.
              </p>
              
              <div class="space-y-3">
                <div
                  @click="selectDataView('live')"
                  class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-green-300 hover:bg-green-50 transition-colors"
                  :class="{ 'border-green-500 bg-green-50': dataViewOverride === 'live' }"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                          <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                        </div>
                      </div>
                      <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">Production Data</p>
                        <p class="text-sm text-gray-500">View live production billing and session data</p>
                      </div>
                    </div>
                    <div v-if="dataViewOverride === 'live'" class="flex-shrink-0">
                      <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                </div>

                <div
                  @click="selectDataView('simulated')"
                  class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-blue-300 hover:bg-blue-50 transition-colors"
                  :class="{ 'border-blue-500 bg-blue-50': dataViewOverride === 'simulated' }"
                >
                  <div class="flex items-center justify-between">
                    <div class="flex items-center">
                      <div class="flex-shrink-0">
                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                          <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                          </svg>
                        </div>
                      </div>
                      <div class="ml-3">
                        <p class="text-sm font-medium text-gray-900">Testing Data</p>
                        <p class="text-sm text-gray-500">View simulated testing billing and session data</p>
                      </div>
                    </div>
                    <div v-if="dataViewOverride === 'simulated'" class="flex-shrink-0">
                      <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                      </svg>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="flex justify-end space-x-3 mt-6">
              <button
                @click="showOverrideModal = false"
                class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md font-medium"
              >
                Cancel
              </button>
              <button
                @click="applyDataViewOverride"
                :disabled="!dataViewOverride"
                class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md font-medium"
              >
                Apply View
              </button>
            </div>
          </div>
        </Modal>

        <!-- Add Funds Modal -->
        <Modal :show="showAddFundsModal" @close="showAddFundsModal = false">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
              Add {{ currentDataView === 'Production Data' ? 'Funds' : 'Test Funds' }}
            </h3>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">
                  Amount ({{ currency }})
                </label>
                <input
                  v-model="paymentAmount"
                  type="number"
                  step="0.01"
                  min="1"
                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                  :placeholder="`Enter ${isProductionMode ? 'payment' : 'test'} amount`"
                />
              </div>
              
                              <div class="p-4 rounded-md" :class="currentDataView === 'Production Data' ? 'bg-green-50' : 'bg-blue-50'">
                  <p class="text-sm" :class="currentDataView === 'Production Data' ? 'text-green-700' : 'text-blue-700'">
                    <strong>{{ currentDataView === 'Production Data' ? 'Pricing:' : 'Test Funds:' }}</strong> 
                    {{ currentDataView === 'Production Data' ? `${currencySymbol}${formatCurrency(sessionPrice)} per USSD session` : 'These funds are for testing and simulation only' }}
                  </p>
                  <p class="text-sm mt-1" :class="currentDataView === 'Production Data' ? 'text-green-600' : 'text-blue-600'">
                    {{ currentDataView === 'Production Data' ? `Estimated sessions: ${Math.floor(paymentAmount / sessionPrice)} sessions` : 'No real charges will be made. Use these funds to test your USSD flows.' }}
                  </p>
                </div>

                              <!-- Payment Method Selection (only for production) -->
                <div v-if="currentDataView === 'Production Data' && paymentStep === 2" class="space-y-3">
                <h4 class="text-md font-medium text-gray-900">Select Payment Method</h4>
                
                <div class="space-y-3">
                  <div
                    v-for="(gateway, key) in availableGateways"
                    :key="key"
                    @click="selectGateway(key)"
                    class="border border-gray-200 rounded-lg p-4 cursor-pointer hover:border-green-300 hover:bg-green-50 transition-colors"
                    :class="{ 'border-green-500 bg-green-50': selectedGateway === key }"
                  >
                    <div class="flex items-center justify-between">
                      <div class="flex items-center">
                        <div class="flex-shrink-0">
                          <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center">
                            <span class="text-sm font-medium text-gray-600">{{ gateway.icon }}</span>
                          </div>
                        </div>
                        <div class="ml-3">
                          <p class="text-sm font-medium text-gray-900">{{ gateway.name }}</p>
                          <p class="text-sm text-gray-500">{{ gateway.description }}</p>
                        </div>
                      </div>
                      <div v-if="selectedGateway === key" class="flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

                              <!-- Payment Processing (only for production) -->
                <div v-if="currentDataView === 'Production Data' && paymentStep === 3" class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-green-600 mx-auto"></div>
                <p class="mt-4 text-sm text-gray-600">Processing your payment...</p>
                <p class="text-xs text-gray-500">Please wait while we redirect you to the payment gateway.</p>
              </div>

              <div class="flex justify-end space-x-3">
                <button
                  @click="showAddFundsModal = false"
                  class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md font-medium"
                >
                  Cancel
                </button>
                <button
                  v-if="currentDataView === 'Production Data' && paymentStep === 1"
                  @click="selectPaymentMethod"
                  :disabled="!paymentAmount || paymentAmount <= 0"
                  class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md font-medium"
                >
                  Continue
                </button>
                <button
                  v-else-if="currentDataView === 'Production Data' && paymentStep === 2"
                  @click="processPayment"
                  :disabled="!selectedGateway"
                  class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md font-medium"
                >
                  Pay {{ currencySymbol }}{{ formatCurrency(paymentAmount) }}
                </button>
              </div>
            </div>
          </div>
        </Modal>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { router, usePage } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  billingStats: Object,
  recentSessions: Array,
  sessionPrice: Number,
  availableGateways: Object,
  billingFilter: String,
  testBalance: Number,
  currency: String,
  currencySymbol: String,
})

// Safe defaults to avoid runtime errors when backend shape changes
const defaultTodayStats = {
  sessions: 0,
  amount: 0,
  live_sessions: 0,
  live_amount: 0,
  simulated_sessions: 0,
  simulated_amount: 0,
}

const defaultPeriodStats = {
  sessions: 0,
  amount: 0,
  live_sessions: 0,
  live_amount: 0,
  simulated_sessions: 0,
  simulated_amount: 0,
}

const showAddFundsModal = ref(false)
const showOverrideModal = ref(false)
const paymentAmount = ref(0)
const selectedGateway = ref('')
const paymentStep = ref(1)
const selectedPeriod = ref('month')

// Smart defaults with override functionality
const ussdEnvironment = ref(props.ussdEnvironment || 'testing') // Current USSD environment
const dataViewOverride = ref(props.billingFilter) // User's data view override
const isViewingOverride = ref(false) // Whether user is viewing different data than USSD environment

// Computed properties for smart defaults with override
const currentDataView = computed(() => {
  // If user has overridden the view, use that
  if (dataViewOverride.value && dataViewOverride.value !== 'default') {
    isViewingOverride.value = true
    return dataViewOverride.value === 'live' ? 'Production Data' : 'Testing Data'
  }
  
  // Otherwise, default to USSD environment
  isViewingOverride.value = false
  return ussdEnvironment.value === 'live' ? 'Production Data' : 'Testing Data'
})

const currentBalance = computed(() => {
  const viewMode = dataViewOverride.value || ussdEnvironment.value
  const stats = props.billingStats || {}
  const accountBalance = stats.account_balance ?? 0
  const testBalance = stats.test_balance ?? 0
  return viewMode === 'live' ? accountBalance : testBalance
})

const currentStats = computed(() => {
  const viewMode = dataViewOverride.value || ussdEnvironment.value
  const stats = props.billingStats || {}
  const today = stats.today || defaultTodayStats
  const thisMonth = stats.this_month || defaultPeriodStats
  
  if (viewMode === 'live') {
    return {
      today: {
        sessions: today.live_sessions ?? 0,
        amount: today.live_amount ?? 0,
      },
      this_month: {
        sessions: thisMonth.live_sessions ?? 0,
        amount: thisMonth.live_amount ?? 0,
      }
    }
  } else {
    return {
      today: {
        sessions: today.simulated_sessions ?? 0,
        amount: today.simulated_amount ?? 0,
      },
      this_month: {
        sessions: thisMonth.simulated_sessions ?? 0,
        amount: thisMonth.simulated_amount ?? 0,
      }
    }
  }
})

const filteredSessions = computed(() => {
  const viewMode = dataViewOverride.value || ussdEnvironment.value
  
  if (viewMode === 'live') {
    return props.recentSessions.filter(session => session.billing_status !== 'simulated')
  } else {
    return props.recentSessions.filter(session => session.billing_status === 'simulated')
  }
})

const formatCurrency = (amount) => {
  return parseFloat(amount || 0).toFixed(2)
}

const formatDate = (date) => {
  return new Date(date).toLocaleDateString()
}

const getStatusClass = (status) => {
  switch (status) {
    case 'charged':
      return 'bg-green-100 text-green-800'
    case 'pending':
      return 'bg-yellow-100 text-yellow-800'
    case 'failed':
      return 'bg-red-100 text-red-800'
    case 'simulated':
      return 'bg-blue-100 text-blue-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const selectDataView = (view) => {
  dataViewOverride.value = view
}

const applyDataViewOverride = () => {
  showOverrideModal.value = false
  loadBillingData()
}

const resetToDefaultView = () => {
  dataViewOverride.value = null
  loadBillingData()
}

const loadBillingData = () => {
  const filter = dataViewOverride.value || ussdEnvironment.value
  router.get(route('billing.dashboard'), { 
    period: selectedPeriod.value,
    billing_filter: filter
  }, {
    preserveState: true,
    preserveScroll: true
  })
}

const selectPaymentMethod = () => {
  paymentStep.value = 2
}

const selectGateway = (gateway) => {
  selectedGateway.value = gateway
}

const processPayment = async () => {
  try {
    paymentStep.value = 3
    
    // Use regular fetch instead of Inertia router for API call
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
    
    const response = await fetch(route('payment.initialize'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify({
        amount: paymentAmount.value,
        gateway: selectedGateway.value
      })
    })
    
    const data = await response.json()
    
    if (data.success && data.gateway_data) {
      // Redirect to payment gateway
      if (data.gateway_data.checkout_url) {
        window.location.href = data.gateway_data.checkout_url
      } else if (data.gateway_data.payment_url) {
        window.location.href = data.gateway_data.payment_url
      } else if (data.gateway_data.authorization_url) {
        window.location.href = data.gateway_data.authorization_url
      } else if (data.gateway_data.approval_url) {
        window.location.href = data.gateway_data.approval_url
      } else {
        // Manual payment - show bank details
        showBankDetails(data.gateway_data)
      }
    } else {
      alert(data.error || 'Payment initialization failed. Please try again.')
      paymentStep.value = 2
    }
  } catch (error) {
    console.error('Payment failed:', error)
    alert('Payment failed. Please try again.')
    paymentStep.value = 2
  }
}

const addTestFunds = async () => {
  try {
    const response = await router.post(route('billing.add-test-funds'), {
      amount: paymentAmount.value
    })
    
    if (response.ok) {
      showAddFundsModal.value = false
      paymentAmount.value = 0
      loadBillingData() // Refresh data
    } else {
      alert('Failed to add test funds. Please try again.')
    }
  } catch (error) {
    console.error('Failed to add test funds:', error)
    alert('Failed to add test funds. Please try again.')
  }
}

const showBankDetails = (bankData) => {
  // Show bank transfer details modal
  alert(`Please transfer ${currencySymbol}${paymentAmount.value} to:\nBank: ${bankData.bank_details.bank_name}\nAccount: ${bankData.bank_details.account_number}\nReference: ${bankData.bank_details.reference}`)
  showAddFundsModal.value = false
}

const page = usePage()

// Watch for flash messages and show alerts
watch(() => page.props.flash, (flash) => {
  if (flash?.success) {
    // Show success alert
    alert(flash.success)
    // Clear the flash message after showing
    router.reload({ only: [] })
  } else if (flash?.error) {
    // Show error alert
    alert(flash.error)
    // Clear the flash message after showing
    router.reload({ only: [] })
  } else if (flash?.info) {
    // Show info alert
    alert(flash.info)
    // Clear the flash message after showing
    router.reload({ only: [] })
  }
}, { immediate: true, deep: true })

// Also check on mount in case page loaded with flash message
onMounted(() => {
  const flash = page.props.flash
  if (flash?.success) {
    alert(flash.success)
  } else if (flash?.error) {
    alert(flash.error)
  } else if (flash?.info) {
    alert(flash.info)
  }
})
</script>

<style scoped>
.toggle-checkbox:checked {
  transform: translateX(100%);
}

.toggle-label {
  transition: background-color 0.2s ease-in-out;
}
</style>
