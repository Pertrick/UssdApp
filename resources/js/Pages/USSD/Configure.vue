<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Configure USSD Flow</h2>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-500">Service: {{ ussd.name }} ({{ ussd.pattern }})</span>
          <Link 
            :href="route('ussd.simulator', ussd.id)" 
            class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded hover:bg-green-700 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Test Simulation
          </Link>
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Flows List -->
        <div class="col-span-1 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Flows</h3>
            <button @click="openAddFlowModal" class="px-2 py-1 rounded bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">+ Add Flow</button>
          </div>
          <ul>
            <li v-for="flow in flows" :key="flow.id" :class="[selectedFlow && selectedFlow.id === flow.id ? 'bg-indigo-50' : '', 'rounded p-2 mb-2 cursor-pointer hover:bg-indigo-100']" @click="selectFlow(flow)">
              <div class="flex items-center justify-between">
                <span class="font-medium">{{ flow.name }}</span>
                <div class="flex items-center gap-2">
                  <span v-if="flow.is_root" class="text-xs text-green-600">Root</span>
                  <span v-if="selectedFlow && selectedFlow.id === flow.id && hasUnsavedChanges()" class="text-xs text-orange-600 font-semibold">*</span>
                </div>
              </div>
              <div class="text-xs text-gray-500 truncate">
                <div v-if="flow.title" class="font-medium">{{ flow.title }}</div>
                <div>{{ flow.menu_text }}</div>
              </div>
            </li>
          </ul>
        </div>

        <!-- Flow Editor -->
        <div class="col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 p-6 min-h-[400px]">
          <div v-if="selectedFlow">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Edit Flow: {{ selectedFlow.name }}</h3>
              <button @click="openDeleteFlowModal(selectedFlow)" class="px-2 py-1 rounded bg-red-100 text-red-700 text-xs font-semibold hover:bg-red-200">Delete Flow</button>
            </div>
            
            <!-- Title Editor -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Title/Header</label>
              <input 
                v-model="selectedFlow.title" 
                type="text"
                class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Enter title/header (e.g., Report type of fire)"
              />
              <p class="mt-1 text-xs text-gray-500">This will appear above the menu options</p>
            </div>
            
            <!-- Menu Text Editor -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Menu Text (Numbered Options Only)</label>
              <textarea 
                v-model="selectedFlow.menu_text" 
                rows="3" 
                class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Enter numbered options only (e.g., 1. Gas Leak&#10;2. Fuel/Petrol&#10;3. Oil)"
                @input="handleMenuTextChange"
              ></textarea>
              <p class="mt-1 text-xs text-gray-500">Enter only the numbered options. Edit this text to automatically update the options below, or edit options to update this text.</p>
            </div>
            
            <!-- Description -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Description</label>
              <input v-model="selectedFlow.description" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
            </div>
            
            <!-- Options Editor -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Options (Auto-syncs with menu text)</label>
              <p class="mt-1 text-xs text-gray-500 mb-2">
                <strong>Input Collection Tip:</strong> For input options, you can specify what happens after collecting input. 
                Leave "Next Flow" empty to stay in the same flow, select "End session after input" to close the session, or choose a flow to navigate to.
              </p>
              <div v-for="(option, idx) in selectedFlow.options" :key="option.id || idx" class="bg-gray-50 rounded p-3 mb-2 flex flex-col md:flex-row md:items-center gap-2 w-full">
                <input v-model="option.option_text" placeholder="Option Text" class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" @input="handleOptionChange" />
                <input v-model="option.option_value" placeholder="Value" class="w-20 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                <select v-model="option.action_type" class="w-32 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                  <option value="navigate">Navigate</option>
                  <option value="message">Message</option>
                  <option value="end_session">End Session</option>
                  <option value="input_text">Input Text</option>
                  <option value="input_number">Input Number</option>
                  <option value="input_phone">Input Phone</option>
                  <option value="input_account">Input Account</option>
                  <option value="input_pin">Input PIN</option>
                  <option value="input_amount">Input Amount</option>
                  <option value="input_selection">Input Selection</option>
                </select>
                
                <!-- Action-specific fields -->
                <input v-if="option.action_type === 'message'" v-model="option.action_data.message" placeholder="Message" class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                <select v-if="option.action_type === 'navigate'" v-model="option.next_flow_id" class="w-40 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                  <option value="">Select flow</option>
                  <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">{{ flow.name }}</option>
                </select>
                
                <!-- Input collection configuration -->
                <div v-if="['input_text', 'input_number', 'input_phone', 'input_account', 'input_pin', 'input_amount', 'input_selection'].includes(option.action_type)" class="flex flex-col gap-2 w-full">
                  <select v-model="option.next_flow_id" class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">Stay in same flow (show menu again)</option>
                    <option value="end_session">End session after input</option>
                    <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">Go to: {{ flow.name }}</option>
                  </select>
                  <input v-model="option.action_data.prompt" placeholder="Custom prompt (optional)" class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <input v-model="option.action_data.success_message" placeholder="Success message (optional)" class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                </div>
                
                <button @click="removeOption(idx)" class="text-red-500 hover:text-red-700 ml-2 px-2 py-1 text-sm whitespace-nowrap">Remove</button>
              </div>
              <button @click="addOption" class="mt-2 px-3 py-1 rounded bg-indigo-100 text-indigo-700 text-xs font-semibold hover:bg-indigo-200">+ Add Option</button>
            </div>
            
            <!-- Save/Cancel Buttons -->
            <div class="flex justify-end gap-2">
              <button 
                @click="cancelEdit" 
                :disabled="savingFlow"
                class="px-4 py-2 rounded bg-gray-300 text-gray-700 font-semibold hover:bg-gray-400 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Cancel
              </button>
              <button 
                @click="saveFlow" 
                :disabled="savingFlow"
                :class="[
                  hasUnsavedChanges() ? 'bg-orange-600 hover:bg-orange-700' : 'bg-indigo-600 hover:bg-indigo-700',
                  'px-4 py-2 rounded text-white font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2'
                ]"
              >
                <svg v-if="savingFlow" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ savingFlow ? 'Saving...' : (hasUnsavedChanges() ? 'Save Changes*' : 'Save Flow') }}
              </button>
            </div>
          </div>
          <div v-else class="text-gray-400 flex items-center justify-center h-full min-h-[300px]">
            <span>Select a flow to edit or add a new one.</span>
          </div>
        </div>
      </div>

      <!-- Add Flow Modal -->
      <FormModal
        :show="showAddFlowModal"
        title="Add New Flow"
        confirm-text="Add Flow"
        cancel-text="Cancel"
        type="primary"
        :loading="savingFlow"
        @confirm="saveNewFlow"
        @cancel="closeAddFlowModal"
      >
        <div class="space-y-4">
          <div v-if="flowErrors.general" class="bg-red-50 border border-red-200 rounded-md p-3">
            <p class="text-sm text-red-600">{{ flowErrors.general }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Flow Name</label>
            <input 
              v-model="flowForm.name" 
              type="text"
              :class="[flowErrors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter flow name"
            />
            <p v-if="flowErrors.name" class="mt-1 text-sm text-red-600">{{ flowErrors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Title/Header (Optional)</label>
            <input 
              v-model="flowForm.title" 
              type="text"
              :class="[flowErrors.title ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter title/header (e.g., Report type of fire)"
            />
            <p v-if="flowErrors.title" class="mt-1 text-sm text-red-600">{{ flowErrors.title }}</p>
            <p class="mt-1 text-xs text-gray-500">This will appear above the menu options (optional)</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Menu Text (Numbered Options Only)</label>
            <textarea 
              v-model="flowForm.menu_text" 
              rows="3"
              :class="[flowErrors.menu_text ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter numbered options only (e.g., 1. Gas Leak&#10;2. Fuel/Petrol&#10;3. Oil)"
            ></textarea>
            <p v-if="flowErrors.menu_text" class="mt-1 text-sm text-red-600">{{ flowErrors.menu_text }}</p>
            <p class="mt-1 text-xs text-gray-500">Enter only the numbered options. The title will be added automatically.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <input 
              v-model="flowForm.description" 
              type="text"
              :class="[flowErrors.description ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter flow description"
            />
            <p v-if="flowErrors.description" class="mt-1 text-sm text-red-600">{{ flowErrors.description }}</p>
          </div>
        </div>
      </FormModal>

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
import { ref, computed, onMounted, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import FormModal from '@/Components/FormModal.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'

const props = defineProps({
    ussd: Object,
})

const flows = ref(props.ussd.flows || [])
const selectedFlow = ref(null)
const originalFlow = ref(null)
const showAddFlowModal = ref(false)
const showDeleteFlowModal = ref(false)
const savingFlow = ref(false)
const deletingFlow = ref(false)

// Form data
const flowForm = useForm({
    name: '',
    title: '',
    menu_text: '',
    description: '',
})

// Validation errors
const flowErrors = ref({})

// Computed properties
const availableFlows = computed(() => {
    return flows.value.filter(flow => flow.id !== selectedFlow.value?.id)
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
    if (clonedFlow.options) {
        clonedFlow.options.forEach(option => {
            if (!option.action_data || typeof option.action_data !== 'object') {
                option.action_data = {}
            }
            
            // Handle end_session_after_input flag for display
            if (option.action_data.end_session_after_input) {
                option.next_flow_id = 'end_session'
            }
        })
    }
    
    selectedFlow.value = clonedFlow
    originalFlow.value = JSON.parse(JSON.stringify(clonedFlow))
}

const openAddFlowModal = () => {
    flowForm.reset()
    showAddFlowModal.value = true
}

const openDeleteFlowModal = (flow) => {
    selectedFlow.value = flow
    showDeleteFlowModal.value = true
}

const closeAddFlowModal = () => {
    flowForm.reset()
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

const handleMenuTextChange = () => {
    // Parse menu text and update options
    const lines = selectedFlow.value.menu_text.split('\n').filter(line => line.trim())
    const newOptions = []
    
    lines.forEach((line, index) => {
        // Remove numbering patterns
        let optionText = line.trim().replace(/^\d+[\.\)]?\s*/, '')
        
        if (optionText) {
            const newOption = {
                option_text: optionText,
                option_value: (index + 1).toString(),
                action_type: 'navigate',
                action_data: {},
                next_flow_id: null
            }
            newOptions.push(newOption)
        }
    })
    
    // Update options if we have valid ones
    if (newOptions.length > 0) {
        selectedFlow.value.options = newOptions
    }
}

const handleOptionChange = () => {
    // Generate menu text from options
    const validOptions = selectedFlow.value.options.filter(option => option.option_text && option.option_text.trim())
    
    if (validOptions.length > 0) {
        let menuText = ''
        validOptions.forEach((option, index) => {
            if (index > 0) menuText += '\n'
            menuText += `${index + 1}. ${option.option_text}`
        })
        selectedFlow.value.menu_text = menuText
    }
}

const hasUnsavedChanges = () => {
    if (!selectedFlow.value || !originalFlow.value) return false
    
    return JSON.stringify(selectedFlow.value) !== JSON.stringify(originalFlow.value)
}

const saveFlow = async () => {
    if (!selectedFlow.value) return
    
    savingFlow.value = true
    flowErrors.value = {}
    
    try {
        const response = await fetch(`/ussd/${props.ussd.id}/flows/${selectedFlow.value.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                name: selectedFlow.value.name,
                title: selectedFlow.value.title || '',
                menu_text: selectedFlow.value.menu_text,
                description: selectedFlow.value.description || '',
                options: selectedFlow.value.options || []
            })
        })
        
        const result = await response.json()
        
        if (result.success) {
            // Update the flow in the flows array
            const index = flows.value.findIndex(f => f.id === selectedFlow.value.id)
            flows.value[index] = result.flow
            
            // Convert the saved flow data for UI display (handle end_session_after_input flag)
            const convertedFlow = JSON.parse(JSON.stringify(result.flow))
            if (convertedFlow.options) {
                convertedFlow.options.forEach(option => {
                    if (!option.action_data || typeof option.action_data !== 'object') {
                        option.action_data = {}
                    }
                    
                    // Handle end_session_after_input flag for display
                    if (option.action_data.end_session_after_input) {
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            },
            body: JSON.stringify(flowForm.data())
        })
        
        const result = await response.json()
        
        if (result.success) {
            // Convert the saved flow data for UI display (handle end_session_after_input flag)
            const convertedFlow = JSON.parse(JSON.stringify(result.flow))
            if (convertedFlow.options) {
                convertedFlow.options.forEach(option => {
                    if (!option.action_data || typeof option.action_data !== 'object') {
                        option.action_data = {}
                    }
                    
                    // Handle end_session_after_input flag for display
                    if (option.action_data.end_session_after_input) {
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
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json'
            }
        })
        
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