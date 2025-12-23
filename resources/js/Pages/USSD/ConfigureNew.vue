<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Configure USSD Flow</h2>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-500">Service: {{ ussd.name }} ({{ ussd.pattern }})</span>
          <form :action="route('ussd.simulator', ussd.id)" method="GET" class="inline">
            <input type="hidden" name="_token" :value="$page.props.csrf_token" />
            <button 
              type="submit"
              class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded hover:bg-green-700 flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Test Simulation
            </button>
          </form>
        </div>
      </div>
    </template>

    <!-- Marketplace Modal -->
    <div v-if="showMarketplaceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Browse Marketplace APIs</h3>
            <button @click="closeMarketplaceModal" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <div class="max-h-96 overflow-y-auto">
            <div v-for="(category, categoryName) in marketplaceApisByCategory" :key="categoryName" class="mb-6">
              <h4 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">{{ categoryName }}</h4>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div v-for="api in category" :key="api.id" 
                     class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 hover:shadow-md transition-all cursor-pointer"
                     @click="selectMarketplaceApi(api)">
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <h5 class="font-medium text-gray-900">{{ api.name }}</h5>
                      <p class="text-sm text-gray-600 mt-1">{{ api.description }}</p>
                      <div class="flex items-center gap-2 mt-2">
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{{ api.method }}</span>
                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">{{ api.provider_name }}</span>
                        <span :class="getApiStatusClass(api.test_status)" class="text-xs px-2 py-1 rounded">
                          {{ api.test_status }}
                        </span>
                      </div>
                    </div>
                    <button @click.stop="selectMarketplaceApi(api)" 
                            class="ml-2 px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700">
                      Use API
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="mt-6 flex justify-end">
            <button @click="closeMarketplaceModal" 
                    class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
              Close
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- API Configuration Wizard Modal -->
    <div v-if="showAPIConfigurationWizard" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
      <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/4 xl:w-2/3 shadow-lg rounded-md bg-white">
        <div class="mt-3">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-medium text-gray-900">Configure API Integration</h3>
            <button @click="closeAPIConfigurationWizard" class="text-gray-400 hover:text-gray-600">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>
          
          <APIConfigurationWizard
            :marketplace-apis="marketplaceApis"
            :custom-apis="customApis"
            :available-flows="availableFlows"
            @completed="handleAPIConfigurationCompleted"
            @cancelled="closeAPIConfigurationWizard"
          />
        </div>
      </div>
    </div>

    <div class="py-8">
      <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Flows List -->
        <FlowList
          :flows="flows"
          :selected-flow="selectedFlow"
          :has-unsaved-changes="hasUnsavedChanges()"
          @add-flow="openAddFlowModal"
          @select-flow="selectFlow"
        />

        <!-- Flow Editor -->
        <FlowEditor
          :flow="selectedFlow"
          :available-flows="availableFlows"
          :marketplace-apis="marketplaceApis"
          :custom-apis="customApis"
          :has-unsaved-changes="hasUnsavedChanges()"
          :saving="savingFlow"
          @update-flow="updateFlow"
          @update-dynamic-config="updateDynamicConfig"
          @update-option="updateOption"
          @update-action-data="updateActionData"
          @add-option="addOption"
          @remove-option="removeOption"
          @save-flow="saveFlow"
          @cancel-edit="cancelEdit"
          @delete-flow="openDeleteFlowModal"
          @open-api-wizard="openAPIConfigurationWizard"
        />
      </div>

      <!-- Add Flow Modal -->
      <AddFlowModal
        :show="showAddFlowModal"
        :form="flowForm"
        :errors="flowErrors"
        :saving="savingFlow"
        :marketplace-apis="marketplaceApis"
        :custom-apis="customApis"
        :available-flows="availableFlows"
        @close="closeAddFlowModal"
        @save="saveNewFlow"
        @update-form="updateFlowForm"
        @update-dynamic-config="updateNewFlowDynamicConfig"
        @open-api-wizard="openAPIConfigurationWizard"
      />

      <!-- Delete Flow Modal -->
      <ConfirmationModal
        :show="showDeleteFlowModal"
        title="Delete Flow"
        message="Are you sure you want to delete this flow? This cannot be undone."
        confirm-text="Delete"
        cancel-text="Cancel"
        type="danger"
        :loading="deletingFlow"
        @confirm="deleteFlow"
        @cancel="showDeleteFlowModal = false"
      />
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import APIConfigurationWizard from '@/Components/APIConfigurationWizard.vue'
import FlowList from '@/Components/USSD/FlowList.vue'
import FlowEditor from '@/Components/USSD/FlowEditor.vue'
import AddFlowModal from '@/Components/USSD/AddFlowModal.vue'
import { csrfToken } from '@/utils/csrf.js'

const props = defineProps({
    ussd: Object,
    marketplaceApis: {
        type: Array,
        default: () => []
    },
    customApis: {
        type: Array,
        default: () => []
    }
})

const flows = ref(props.ussd.flows || [])
const selectedFlow = ref(null)
const originalFlow = ref(null)
const showAddFlowModal = ref(false)
const showDeleteFlowModal = ref(false)
const showMarketplaceModal = ref(false)
const showAPIConfigurationWizard = ref(false)
const savingFlow = ref(false)
const deletingFlow = ref(false)
const currentApiOption = ref(null)

// Form data
const flowForm = useForm({
    name: '',
    title: '',
    menu_text: '',
    description: '',
    flow_type: 'static',
    dynamic_config: {
        api_configuration_id: '',
        list_path: '',
        label_field: 'name',
        value_field: 'id',
        empty_message: 'No options available',
        continuation_type: 'continue',
        next_flow_id: '',
        items_per_page: '7',
        next_label: 'Next',
        back_label: 'Back'
    }
})

// Validation errors
const flowErrors = ref({})

// Computed properties
const availableFlows = computed(() => {
    return flows.value.filter(flow => flow.id !== selectedFlow.value?.id)
})

const marketplaceApisByCategory = computed(() => {
    const grouped = {}
    props.marketplaceApis.forEach(api => {
        const category = api.marketplace_category || 'Other'
        if (!grouped[category]) {
            grouped[category] = []
        }
        grouped[category].push(api)
    })
    return grouped
})

// Methods
const selectFlow = (flow) => {
    // Save current changes if any
    if (selectedFlow.value && hasUnsavedChanges()) {
        if (confirm('You have unsaved changes. Do you want to save them?')) {
            saveFlow()
        }
    }
    
    // Deep clone the flow and ensure all options have proper action_data
    const clonedFlow = JSON.parse(JSON.stringify(flow))
    
    // Initialize flow_type if not set (default to static for backward compatibility)
    if (!clonedFlow.flow_type) {
        clonedFlow.flow_type = 'static'
    }
    
    // Initialize dynamic_config if not set
    if (!clonedFlow.dynamic_config) {
        clonedFlow.dynamic_config = {
            api_configuration_id: '',
            list_path: '',
            label_field: 'name',
            value_field: 'id',
            empty_message: 'No options available',
            continuation_type: 'continue',
            next_flow_id: '',
            items_per_page: '7',
            next_label: 'Next',
            back_label: 'Back'
        }
    }
    
    if (clonedFlow.options) {
        clonedFlow.options.forEach(option => {
            // Handle action_data - convert arrays to objects and ensure it's an object
            if (option.action_data === null) {
                option.action_data = {}
            } else if (Array.isArray(option.action_data)) {
                // Convert array to object if it's an array
                option.action_data = {}
            } else if (typeof option.action_data !== 'object') {
                option.action_data = {}
            }
            
            // Handle end_session_after_input flag for display
            if (option.action_data && option.action_data.end_session_after_input) {
                option.next_flow_id = 'end_session'
            }
        })
    }
    
    selectedFlow.value = clonedFlow
    originalFlow.value = JSON.parse(JSON.stringify(clonedFlow))
}

const openAddFlowModal = () => {
    flowForm.reset()
    flowForm.flow_type = 'static'
    flowForm.dynamic_config = {
        api_configuration_id: '',
        list_path: '',
        label_field: 'name',
        value_field: 'id',
        empty_message: 'No options available',
        continuation_type: 'continue',
        next_flow_id: '',
        items_per_page: '7',
        next_label: 'Next',
        back_label: 'Back'
    }
    showAddFlowModal.value = true
}

const openDeleteFlowModal = (flow) => {
    selectedFlow.value = flow
    showDeleteFlowModal.value = true
}

const closeAddFlowModal = () => {
    flowForm.reset()
    flowForm.flow_type = 'static'
    flowForm.dynamic_config = {
        api_configuration_id: '',
        list_path: '',
        label_field: 'name',
        value_field: 'id',
        empty_message: 'No options available',
        continuation_type: 'continue',
        next_flow_id: '',
        items_per_page: '7',
        next_label: 'Next',
        back_label: 'Back'
    }
    flowErrors.value = {}
    showAddFlowModal.value = false
}

const addOption = () => {
    if (!selectedFlow.value.options) {
        selectedFlow.value.options = []
    }
    
    const newOption = {
        option_text: '',
        option_value: '',
        action_type: 'navigate',
        action_data: {},
        next_flow_id: null
    }
    
    selectedFlow.value.options.push(newOption)
}

const removeOption = (index) => {
    selectedFlow.value.options.splice(index, 1)
}

const hasUnsavedChanges = () => {
    if (!selectedFlow.value || !originalFlow.value) return false
    
    return JSON.stringify(selectedFlow.value) !== JSON.stringify(originalFlow.value)
}

const saveFlow = async () => {
    if (!selectedFlow.value) return
    
    console.log('Starting saveFlow - making PUT request to update flow')
    savingFlow.value = true
    flowErrors.value = {}
    
    try {
        const requestData = {
            name: selectedFlow.value.name,
            title: selectedFlow.value.title || '',
            menu_text: selectedFlow.value.menu_text,
            description: selectedFlow.value.description || '',
            options: selectedFlow.value.options || [],
            flow_type: selectedFlow.value.flow_type || 'static',
            dynamic_config: selectedFlow.value.dynamic_config || {}
        }
        console.log('Sending PUT request with data:', requestData)
        
        const response = await fetch(`/ussd/${props.ussd.id}/flows/${selectedFlow.value.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.get(),
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        
        // Handle CSRF token mismatch
        if (response.status === 419) {
            // Refresh CSRF token and retry
            await csrfToken.refresh()
            return saveFlow() // Retry the request
        }
        
        const result = await response.json()
        
        if (result.success) {
            // Update the flow in the flows array
            const index = flows.value.findIndex(f => f.id === selectedFlow.value.id)
            flows.value[index] = result.flow
            
            // Convert the saved flow data for UI display (handle end_session_after_input flag)
            const convertedFlow = JSON.parse(JSON.stringify(result.flow))
            if (convertedFlow.options) {
                convertedFlow.options.forEach(option => {
                    // Handle action_data - convert arrays to objects and ensure it's an object
                    if (option.action_data === null) {
                        option.action_data = {}
                    } else if (Array.isArray(option.action_data)) {
                        // Convert array to object if it's an array
                        option.action_data = {}
                    } else if (typeof option.action_data !== 'object') {
                        option.action_data = {}
                    }
                    
                    // Handle end_session_after_input flag for display
                    if (option.action_data && option.action_data.end_session_after_input) {
                        option.next_flow_id = 'end_session'
                    }
                })
            }
            
            selectedFlow.value = convertedFlow
            originalFlow.value = JSON.parse(JSON.stringify(convertedFlow))
        } else {
            flowErrors.value = result.errors || {}
        }
    } catch (error) {
        flowErrors.value.general = 'An error occurred while saving the flow.'
    } finally {
        savingFlow.value = false
    }
}

const saveNewFlow = async () => {
    savingFlow.value = true
    flowErrors.value = {}
    
    try {
        const response = await fetch(`/ussd/${props.ussd.id}/flows`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.get(),
                'Accept': 'application/json'
            },
            body: JSON.stringify(flowForm.data())
        })
        
        // Handle CSRF token mismatch
        if (response.status === 419) {
            // Refresh CSRF token and retry
            await csrfToken.refresh()
            return saveNewFlow() // Retry the request
        }
        
        const result = await response.json()
        
        if (result.success) {
            // Convert the saved flow data for UI display (handle end_session_after_input flag)
            const convertedFlow = JSON.parse(JSON.stringify(result.flow))
            if (convertedFlow.options) {
                convertedFlow.options.forEach(option => {
                    // Handle action_data - convert arrays to objects and ensure it's an object
                    if (option.action_data === null) {
                        option.action_data = {}
                    } else if (Array.isArray(option.action_data)) {
                        // Convert array to object if it's an array
                        option.action_data = {}
                    } else if (typeof option.action_data !== 'object') {
                        option.action_data = {}
                    }
                    
                    // Handle end_session_after_input flag for display
                    if (option.action_data && option.action_data.end_session_after_input) {
                        option.next_flow_id = 'end_session'
                    }
                })
            }
            
            flows.value.push(convertedFlow)
            showAddFlowModal.value = false
            flowForm.reset()
        } else {
            flowErrors.value = result.errors || {}
        }
    } catch (error) {
        flowErrors.value.general = 'An error occurred while creating the flow.'
    } finally {
        savingFlow.value = false
    }
}

const deleteFlow = async () => {
    if (!selectedFlow.value) return
    
    deletingFlow.value = true
    
    try {
        const response = await fetch(`/ussd/${props.ussd.id}/flows/${selectedFlow.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.get(),
                'Accept': 'application/json'
            }
        })
        
        // Handle CSRF token mismatch
        if (response.status === 419) {
            // Refresh CSRF token and retry
            await csrfToken.refresh()
            return deleteFlow() // Retry the request
        }
        
        const result = await response.json()
        
        if (result.success) {
            flows.value = flows.value.filter(f => f.id !== selectedFlow.value.id)
            selectedFlow.value = null
            originalFlow.value = null
            showDeleteFlowModal.value = false
        }
    } catch (error) {
        console.error('Error deleting flow:', error)
    } finally {
        deletingFlow.value = false
    }
}

const cancelEdit = () => {
    if (hasUnsavedChanges()) {
        if (confirm('You have unsaved changes. Are you sure you want to cancel?')) {
            selectedFlow.value = originalFlow.value ? JSON.parse(JSON.stringify(originalFlow.value)) : null
        }
    }
}

// Component event handlers
const updateFlow = (key, value) => {
    if (selectedFlow.value) {
        selectedFlow.value[key] = value
    }
}

const updateDynamicConfig = (key, value) => {
    if (selectedFlow.value && selectedFlow.value.dynamic_config) {
        selectedFlow.value.dynamic_config[key] = value
    }
}

const updateOption = (index, key, value) => {
    if (selectedFlow.value && selectedFlow.value.options && selectedFlow.value.options[index]) {
        selectedFlow.value.options[index][key] = value
    }
}

const updateActionData = (index, key, value) => {
    if (selectedFlow.value && selectedFlow.value.options && selectedFlow.value.options[index]) {
        if (!selectedFlow.value.options[index].action_data) {
            selectedFlow.value.options[index].action_data = {}
        }
        selectedFlow.value.options[index].action_data[key] = value
    }
}

const updateFlowForm = (key, value) => {
    flowForm[key] = value
}

const updateNewFlowDynamicConfig = (key, value) => {
    if (flowForm.dynamic_config) {
        flowForm.dynamic_config[key] = value
    }
}

// Marketplace API methods
const openMarketplaceModal = (option = null) => {
    currentApiOption.value = option
    showMarketplaceModal.value = true
}

const closeMarketplaceModal = () => {
    showMarketplaceModal.value = false
    currentApiOption.value = null
}

const openAPIConfigurationWizard = (option = null) => {
    currentApiOption.value = option
    showAPIConfigurationWizard.value = true
}

const closeAPIConfigurationWizard = () => {
    showAPIConfigurationWizard.value = false
    currentApiOption.value = null
}

const handleAPIConfigurationCompleted = (data) => {
    if (currentApiOption.value) {
        // Set the API configuration for the current option
        currentApiOption.value.action_data.api_configuration_id = data.api.id
        currentApiOption.value.action_data.success_flow_id = data.configuration.success_flow_id
        currentApiOption.value.action_data.error_flow_id = data.configuration.error_flow_id
        currentApiOption.value.action_data.end_session_after_api = data.configuration.end_session_after_api
    }
    closeAPIConfigurationWizard()
}

const selectMarketplaceApi = (api) => {
    if (currentApiOption.value) {
        // Set the API configuration for the current option
        currentApiOption.value.action_data.api_configuration_id = api.id
        currentApiOption.value.action_data.end_session_after_api = false
    }
    closeMarketplaceModal()
}

const getApiStatusClass = (status) => {
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

// Auto-select first flow on mount
onMounted(() => {
    if (flows.value.length > 0 && !selectedFlow.value) {
        selectFlow(flows.value[0])
    }
})
</script>

<style scoped>
.flex-1 {
  flex: 1 1 0%;
}

.w-20 {
  width: 5rem;
  min-width: 5rem;
  max-width: 5rem;
}

.w-32 {
  width: 8rem;
  min-width: 8rem;
  max-width: 8rem;
}

input, select {
  box-sizing: border-box;
}

.gap-2 > * {
  margin: 0;
}
</style>


