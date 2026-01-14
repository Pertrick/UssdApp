<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Environment Management
        </h2>
        <div class="flex items-center space-x-4">
          <!-- Global Environment Indicator -->
          <div class="flex items-center space-x-2">
            <div class="flex items-center space-x-2 px-3 py-1 rounded-full text-sm font-medium"
                 :class="environmentStatus.is_live ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'">
              <div class="w-2 h-2 rounded-full"
                   :class="environmentStatus.is_live ? 'bg-green-500' : 'bg-blue-500'"></div>
              <span>{{ environmentStatus.is_live ? 'Production' : 'Testing' }}</span>
            </div>
          </div>
        </div>
      </div>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Back Button -->
        <div class="mb-6">
          <Link
            :href="route('environment.overview')"
            class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Back to Environment Management Overview
          </Link>
        </div>
        
        <!-- Environment Status Overview -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <div>
                <h3 class="text-lg font-medium text-gray-900">Current Environment</h3>
                <p class="text-sm text-gray-500 mt-1">
                  Manage your USSD service environment and production settings
                </p>
              </div>
              <div class="text-right">
                <p class="text-sm text-gray-500">USSD Code</p>
                <p class="text-lg font-semibold text-gray-900 font-mono">{{ getCurrentUssdCode() }}</p>
              </div>
            </div>

            <!-- Environment Status Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
              <!-- Current Environment -->
              <div class="bg-gray-50 p-4 rounded-lg">
                <div class="flex items-center justify-between">
                  <div>
                    <p class="text-sm font-medium text-gray-500">Current Environment</p>
                    <p class="text-lg font-semibold" 
                       :class="environmentStatus.is_live ? 'text-green-600' : 'text-blue-600'">
                      {{ environmentStatus.is_live ? 'Production' : 'Testing' }}
                    </p>
                  </div>
                  <div class="text-right">
                    <div class="w-3 h-3 rounded-full"
                         :class="environmentStatus.is_live ? 'bg-green-500' : 'bg-blue-500'"></div>
                  </div>
                </div>
              </div>

              <!-- Session Stats -->
              <div class="bg-gray-50 p-4 rounded-lg">
                <div>
                  <p class="text-sm font-medium text-gray-500">Today's Sessions</p>
                  <p class="text-lg font-semibold text-gray-900">
                    {{ environmentStatus.session_stats?.today?.total || 0 }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ environmentStatus.session_stats?.today?.live || 0 }} live, 
                    {{ environmentStatus.session_stats?.today?.testing || 0 }} testing
                  </p>
                </div>
              </div>

              <!-- Total Sessions -->
              <div class="bg-gray-50 p-4 rounded-lg">
                <div>
                  <p class="text-sm font-medium text-gray-500">Total Sessions</p>
                  <p class="text-lg font-semibold text-gray-900">
                    {{ (environmentStatus.session_stats?.total?.live || 0) + (environmentStatus.session_stats?.total?.testing || 0) }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ environmentStatus.session_stats?.total?.live || 0 }} live, 
                    {{ environmentStatus.session_stats?.total?.testing || 0 }} testing
                  </p>
                </div>
              </div>
            </div>

            <!-- Environment Actions -->
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm text-gray-600">
                  Last updated: {{ formatDate(environmentStatus.last_environment_change?.timestamp) }}
                </p>
              </div>
              <div class="flex items-center space-x-3">
                <!-- Switch to Testing Button -->
                <button
                  v-if="environmentStatus.is_live"
                  @click="switchToTesting"
                  :disabled="isLoading"
                  class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md font-medium transition-colors"
                >
                  <span v-if="isLoading" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                      <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                      <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Switching...
                  </span>
                  <span v-else>Switch to Testing</span>
                </button>

                <!-- Go Live Button -->
                <button
                  v-if="environmentStatus.is_testing"
                  @click="showGoLiveModal = true"
                  :disabled="!environmentStatus.all_requirements_met || isLoading"
                  class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md font-medium transition-colors"
                >
                  Go Live
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Requirements Checklist -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
          <div class="p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900">Production Requirements</h3>
              <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                  <span class="text-sm text-gray-500">Progress:</span>
                  <span class="text-sm font-medium text-gray-900">
                    {{ requirementsMetCount }} / {{ totalRequirementsCount }}
                  </span>
                </div>
                <div class="flex items-center space-x-2">
                  <span class="text-sm text-gray-500">Status:</span>
                  <span class="text-sm font-medium"
                        :class="environmentStatus.all_requirements_met ? 'text-green-600' : 'text-red-600'">
                    {{ environmentStatus.all_requirements_met ? 'All Requirements Met' : 'Requirements Pending' }}
                  </span>
                </div>
              </div>
            </div>

            <div class="space-y-4">
              <div v-for="(requirement, key) in environmentStatus.requirements" :key="key"
                   class="flex items-start space-x-3 p-4 rounded-lg border"
                   :class="requirement.status ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200'">
                
                <!-- Status Icon -->
                <div class="flex-shrink-0 mt-1">
                  <svg v-if="requirement.status" class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                  <svg v-else class="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                  </svg>
                </div>

                <!-- Requirement Details -->
                <div class="flex-1">
                  <div class="flex items-center justify-between">
                    <h4 class="text-sm font-medium text-gray-900">{{ requirement.title }}</h4>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium"
                          :class="getPriorityClass(requirement.priority)">
                      {{ requirement.priority }}
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 mt-1">{{ requirement.description }}</p>
                  <p class="text-sm mt-2"
                     :class="requirement.status ? 'text-green-600' : 'text-red-600'">
                    {{ requirement.details }}
                  </p>
                  
                  <!-- Action Button -->
                  <div v-if="!requirement.status" class="mt-3">
                    <button
                      v-if="key === 'production_balance'"
                      @click="goToBilling"
                      class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
                    >
                      <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                      </svg>
                      Add Funds
                    </button>
                    
                    <button
                      v-else-if="key === 'testing_completed'"
                      @click="goToSimulator"
                      class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
                    >
                      <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                      </svg>
                      Start Testing
                    </button>
                    
                    <button
                      v-else-if="key === 'pattern'"
                      @click="showGatewayModal = true"
                      class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
                    >
                      <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      Configure Gateway
                    </button>
                    
                    <button
                      v-else-if="key === 'gateway_configuration'"
                      @click="showGatewayModal = true"
                      class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
                    >
                      <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      Configure Gateway
                    </button>
                    
                    <button
                      v-else-if="key === 'webhook_url'"
                      @click="showWebhookModal = true"
                      class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
                    >
                      <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                      </svg>
                      Configure Webhook
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Configuration Section -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Configuration</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Gateway Configuration -->
              <div class="space-y-4">
                <h4 class="text-md font-medium text-gray-900">Gateway Provider</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                  <p class="text-sm text-gray-600">
                    <strong>Provider:</strong> {{ environmentStatus.gateway_provider || 'Not configured' }}
                  </p>
                  <p class="text-sm text-gray-600 mt-1">
                    <strong>Credentials:</strong> {{ environmentStatus.has_credentials ? 'Configured' : 'Not configured' }}
                  </p>
                  <button 
                    @click="showGatewayModal = true"
                    class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium"
                  >
                    Configure Gateway
                  </button>
                </div>
              </div>

              <!-- Webhook Configuration -->
              <div class="space-y-4">
                <h4 class="text-md font-medium text-gray-900">Webhook URL</h4>
                <div class="bg-gray-50 p-4 rounded-lg">
                  <p class="text-sm text-gray-600">
                    <strong>Status:</strong> {{ environmentStatus.has_webhook ? 'Configured' : 'Not configured' }}
                  </p>
                  <p class="text-sm text-gray-600 mt-1 break-all">
                    <strong>URL:</strong> {{ environmentStatus.webhook_url || 'Not set' }}
                  </p>
                  <button 
                    @click="showWebhookModal = true"
                    class="mt-2 text-sm text-blue-600 hover:text-blue-800 font-medium"
                  >
                    Configure Webhook
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Gateway Configuration Modal -->
    <Modal :show="showGatewayModal" @close="showGatewayModal = false">
      <div class="p-6">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-gray-900">Configure Gateway</h3>
            <p class="text-sm text-gray-500">Set up your USSD gateway provider</p>
          </div>
        </div>

        <form @submit.prevent="saveGateway">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Gateway Provider</label>
              <select 
                v-model="gatewayForm.gateway_provider"
                required
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">Select Provider</option>
                <option value="africastalking">AfricasTalking</option>
                <option value="hubtel">Hubtel</option>
                <option value="twilio">Twilio</option>
              </select>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">API Key</label>
              <input 
                type="text"
                v-model="gatewayForm.api_key"
                required
                minlength="10"
                placeholder="Enter your API key"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
              <input 
                type="text"
                v-model="gatewayForm.username"
                required
                minlength="3"
                placeholder="Enter your username"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
              />
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded p-3">
              <p class="text-sm text-blue-800">
                <strong>Note:</strong> The USSD code (pattern) is configured in the USSD service settings, not here. 
                Update the pattern field when moving to production.
              </p>
            </div>
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <button
              type="button"
              @click="showGatewayModal = false"
              class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="isSavingGateway"
              class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md font-medium"
            >
              <span v-if="isSavingGateway" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving...
              </span>
              <span v-else>Save Configuration</span>
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Webhook Configuration Modal -->
    <Modal :show="showWebhookModal" @close="showWebhookModal = false">
      <div class="p-6">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-gray-900">Configure Webhook</h3>
            <p class="text-sm text-gray-500">Set up webhook URL for receiving USSD requests</p>
          </div>
        </div>

        <form @submit.prevent="saveWebhook">
          <div class="space-y-4">
            <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
              <div class="flex">
                <div class="flex-shrink-0">
                  <svg class="w-5 h-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                  </svg>
                </div>
                <div class="ml-3">
                  <p class="text-sm text-blue-700">
                    This URL will receive USSD requests from your gateway provider. 
                    Make sure it's publicly accessible and uses HTTPS in production.
                  </p>
                </div>
              </div>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Webhook URL</label>
              <input 
                type="url"
                v-model="webhookForm.webhook_url"
                required
                placeholder="https://yourdomain.com/api/ussd/gateway"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
              />
              <p class="mt-1 text-xs text-gray-500">
                Example: https://yourdomain.com/api/ussd/gateway
              </p>
            </div>

            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Callback URL (Optional)</label>
              <input 
                type="url"
                v-model="webhookForm.callback_url"
                placeholder="https://yourdomain.com/api/ussd/callback"
                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500"
              />
              <p class="mt-1 text-xs text-gray-500">
                Optional: Separate callback URL if different from webhook URL
              </p>
            </div>
          </div>

          <div class="flex justify-end space-x-3 mt-6">
            <button
              type="button"
              @click="showWebhookModal = false"
              class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md font-medium"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="isSavingWebhook"
              class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md font-medium"
            >
              <span v-if="isSavingWebhook" class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Saving...
              </span>
              <span v-else>Save Configuration</span>
            </button>
          </div>
        </form>
      </div>
    </Modal>

    <!-- Go Live Confirmation Modal -->
    <Modal :show="showGoLiveModal" @close="showGoLiveModal = false">
      <div class="p-6">
        <div class="flex items-center mb-4">
          <div class="flex-shrink-0">
            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-lg font-medium text-gray-900">Go Live Confirmation</h3>
            <p class="text-sm text-gray-500">Switch your USSD service to production mode</p>
          </div>
        </div>

        <div class="space-y-4">
          <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h4 class="text-sm font-medium text-yellow-800">Important Notice</h4>
                <p class="text-sm text-yellow-700 mt-1">
                  Going live will make your USSD service available to real users. This action will:
                </p>
                <ul class="text-sm text-yellow-700 mt-2 list-disc list-inside space-y-1">
                  <li>Start charging real money for sessions</li>
                  <li>Make your service publicly accessible</li>
                  <li>Require proper monitoring and support</li>
                </ul>
              </div>
            </div>
          </div>

          <div class="bg-green-50 border border-green-200 rounded-md p-4">
            <div class="flex">
              <div class="flex-shrink-0">
                <svg class="w-5 h-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                </svg>
              </div>
              <div class="ml-3">
                <h4 class="text-sm font-medium text-green-800">Requirements Met</h4>
                <p class="text-sm text-green-700 mt-1">
                  All required configurations are complete. Your service is ready to go live.
                </p>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end space-x-3 mt-6">
          <button
            @click="showGoLiveModal = false"
            class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md font-medium"
          >
            Cancel
          </button>
          <button
            @click="goLive"
            :disabled="isLoading"
            class="bg-green-600 hover:bg-green-700 disabled:bg-gray-400 text-white px-4 py-2 rounded-md font-medium"
          >
            <span v-if="isLoading" class="flex items-center">
              <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Going Live...
            </span>
            <span v-else>Go Live</span>
          </button>
        </div>
      </div>
    </Modal>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { router, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Modal from '@/Components/Modal.vue'

const props = defineProps({
  ussd: Object,
  environmentStatus: Object
})

// Computed properties for requirements count
const requirementsMetCount = computed(() => {
  if (!props.environmentStatus?.requirements) return 0
  return Object.values(props.environmentStatus.requirements).filter(req => req.status).length
})

const totalRequirementsCount = computed(() => {
  if (!props.environmentStatus?.requirements) return 0
  return Object.keys(props.environmentStatus.requirements).length
})

const showGoLiveModal = ref(false)
const showGatewayModal = ref(false)
const showWebhookModal = ref(false)
const isLoading = ref(false)
const isSavingGateway = ref(false)
const isSavingWebhook = ref(false)

const gatewayForm = ref({
  gateway_provider: props.ussd?.gateway_provider || '',
  api_key: '',
  username: '',
})

const webhookForm = ref({
  webhook_url: props.ussd?.webhook_url || '',
  callback_url: props.ussd?.callback_url || '',
})

const formatDate = (date) => {
  if (!date) return 'Never'
  return new Date(date).toLocaleString()
}

const getCurrentUssdCode = () => {
  // Pattern is used for all environments (testing and production)
  return props.ussd.pattern || 'Not configured'
  
}

const getPriorityClass = (priority) => {
  switch (priority) {
    case 'critical':
      return 'bg-red-100 text-red-800'
    case 'high':
      return 'bg-orange-100 text-orange-800'
    case 'medium':
      return 'bg-yellow-100 text-yellow-800'
    default:
      return 'bg-gray-100 text-gray-800'
  }
}

const switchToTesting = async () => {
  if (!confirm('Are you sure you want to switch to testing mode? This will disable your live service.')) {
    return
  }

  isLoading.value = true
  try {
    const response = await fetch(route('ussd.go-testing', props.ussd.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
    })
    
    const data = await response.json()
    
    if (data.success) {
      // Refresh the page to get updated status
      router.reload()
    } else {
      alert(data.message || 'Failed to switch to testing mode. Please try again.')
    }
  } catch (error) {
    console.error('Error switching to testing:', error)
    alert('An error occurred while switching to testing mode.')
  } finally {
    isLoading.value = false
  }
}

const goLive = async () => {
  isLoading.value = true
  try {
    const response = await fetch(route('ussd.go-live', props.ussd.id), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
    })
    
    const data = await response.json()
    
    if (data.success) {
      showGoLiveModal.value = false
      // Refresh the page to get updated status
      router.reload()
    } else {
      if (data.requirements) {
        alert(`Cannot go live: ${data.message}\n\nPlease check the requirements checklist.`)
      } else {
        alert(data.message || 'Failed to go live. Please try again.')
      }
    }
  } catch (error) {
    console.error('Error going live:', error)
    alert('An error occurred while going live.')
  } finally {
    isLoading.value = false
  }
}

const saveGateway = async () => {
  isSavingGateway.value = true
  try {
    const response = await fetch(route('ussd.configure-gateway', props.ussd.id), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify(gatewayForm.value)
    })
    
    const data = await response.json()
    
    if (data.success) {
      // Update form with returned data (credentials will be decrypted from server)
      if (data.ussd) {
        gatewayForm.value.gateway_provider = data.ussd.gateway_provider || gatewayForm.value.gateway_provider
        
        // Update credentials if returned (they should be decrypted)
        if (data.ussd.gateway_credentials && typeof data.ussd.gateway_credentials === 'object') {
          // Don't update api_key and username in form for security (they're already saved)
          // But we could optionally keep them if user wants to see them
        }
      }
      
      showGatewayModal.value = false
      // Refresh the page to get updated status
      router.reload()
    } else {
      alert(data.message || 'Failed to configure gateway. Please try again.')
    }
  } catch (error) {
    console.error('Error configuring gateway:', error)
    alert('An error occurred while configuring gateway.')
  } finally {
    isSavingGateway.value = false
  }
}

const saveWebhook = async () => {
  isSavingWebhook.value = true
  try {
    const response = await fetch(route('ussd.configure-webhook', props.ussd.id), {
      method: 'PUT',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify(webhookForm.value)
    })
    
    const data = await response.json()
    
    if (data.success) {
      showWebhookModal.value = false
      // Refresh the page to get updated status
      router.reload()
    } else {
      alert(data.message || 'Failed to configure webhook. Please try again.')
    }
  } catch (error) {
    console.error('Error configuring webhook:', error)
    alert('An error occurred while configuring webhook.')
  } finally {
    isSavingWebhook.value = false
  }
}

const goToBilling = () => {
  router.visit(route('billing.dashboard'))
}

const goToSimulator = () => {
  router.visit(route('ussd.simulator', props.ussd.id))
}

onMounted(() => {
  // Initialize forms with current values
  if (props.ussd) {
    gatewayForm.value.gateway_provider = props.ussd.gateway_provider || ''
  
    if (props.ussd.gateway_credentials && typeof props.ussd.gateway_credentials === 'object') {
      gatewayForm.value.api_key = props.ussd.gateway_credentials.api_key || ''
      gatewayForm.value.username = props.ussd.gateway_credentials.username || ''
    }
    
    webhookForm.value.webhook_url = props.ussd.webhook_url || ''
    webhookForm.value.callback_url = props.ussd.callback_url || ''
  }
  
  // Auto-refresh environment status every 30 seconds
  setInterval(() => {
    // You could implement a lightweight status check here
  }, 30000)
})
</script>
