<template>
  <div class="bg-gray-50 rounded p-3 mb-2 flex flex-col md:flex-row md:items-center gap-2 w-full">
    <input 
      :value="option.option_text" 
      @input="updateOption('option_text', $event.target.value)"
      placeholder="Option Text" 
      class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
    />
    <input 
      :value="option.option_value" 
      @input="updateOption('option_value', $event.target.value)"
      placeholder="Value" 
      class="w-20 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
    />
    <select 
      :value="option.action_type" 
      @change="updateOption('action_type', $event.target.value)"
      class="w-32 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
    >
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
      <option value="external_api_call">External API Call</option>
    </select>
    
    <!-- Action-specific fields -->
    <input 
      v-if="option.action_type === 'message'" 
      :value="option.action_data?.message" 
      @input="updateActionData('message', $event.target.value)"
      placeholder="Message" 
      class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
    />
    <select 
      v-if="option.action_type === 'navigate'" 
      :value="option.next_flow_id" 
      @change="updateOption('next_flow_id', $event.target.value)"
      class="w-40 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
    >
      <option value="">Select flow</option>
      <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">{{ flow.name }}</option>
    </select>
    
    <!-- Phone number configuration for navigate actions -->
    <div v-if="option.action_type === 'navigate'" class="flex flex-col gap-2 w-full mt-2">
      <div class="flex items-center gap-2">
        <input 
          type="checkbox" 
          v-model="useRegisteredPhoneValue"
          :id="'use-phone-' + index" 
          class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        >
        <label :for="'use-phone-' + index" class="text-sm text-gray-700">Use registered phone number for this option</label>
      </div>
      
      <!-- Data Fields Button -->
      <div class="mt-1">
        <button 
          @click="showDataFieldsModal = true" 
          type="button"
          class="px-3 py-2 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 inline-flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
          <span>Configure Data Fields</span>
          <span 
            v-if="storeDataFields.length > 0" 
            class="bg-blue-500 text-white text-xs px-2 py-0.5 rounded-full"
          >
            {{ storeDataFields.length }}
          </span>
        </button>
      </div>
    </div>
    
    <!-- Input collection configuration -->
    <div v-if="['input_text', 'input_number', 'input_phone', 'input_account', 'input_pin', 'input_amount', 'input_selection'].includes(option.action_type)" class="flex flex-col gap-2 w-full">
      <div class="bg-yellow-50 border border-yellow-200 rounded p-2 mb-2">
        <p class="text-xs text-yellow-800 font-medium">After collecting input:</p>
      </div>
      
      <!-- Action type selection -->
      <select 
        :value="option.action_data?.after_input_action" 
        @change="handleAfterInputActionChange"
        class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
      >
        <option value="show_menu">Show this menu again</option>
        <option value="end_session">End the session</option>
        <option value="navigate">Go to another menu</option>
        <option value="external_api_call">Connect to marketplace</option>
        <option value="process_data">Process the data</option>
      </select>
      
      <!-- Navigation flow selection -->
      <select 
        v-if="option.action_data?.after_input_action === 'navigate'" 
        :value="option.next_flow_id" 
        @change="updateOption('next_flow_id', $event.target.value)"
        class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
      >
        <option value="">Select flow</option>
        <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">{{ flow.name }}</option>
      </select>
      
      <!-- Marketplace API selection -->
      <div v-if="option.action_data?.after_input_action === 'external_api_call'" class="flex gap-2">
        <select 
          :value="option.action_data?.api_configuration_id" 
          @change="updateActionData('api_configuration_id', $event.target.value)"
          class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
        >
          <option value="">Select API Integration</option>
          <optgroup label="Marketplace APIs">
            <option v-for="api in marketplaceApis" :key="api.id" :value="api.id">
              {{ api.name }} ({{ api.provider_name }})
            </option>
          </optgroup>
          <optgroup label="Custom APIs">
            <option v-for="api in customApis" :key="api.id" :value="api.id">
              {{ api.name }}
            </option>
          </optgroup>
        </select>
        <button 
          @click="$emit('open-api-wizard', option)" 
          type="button" 
          class="px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700"
        >
          Configure
        </button>
      </div>
      
      <!-- Process data options -->
      <select 
        v-if="option.action_data?.after_input_action === 'process_data'" 
        :value="option.action_data?.process_type" 
        @change="updateActionData('process_type', $event.target.value)"
        class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
      >
        <option value="process_registration">Process registration</option>
        <option value="process_feedback">Process feedback</option>
        <option value="process_survey">Process survey</option>
        <option value="process_contact">Process contact</option>
      </select>
      
      <!-- Input configuration -->
      <input 
        :value="option.action_data?.prompt" 
        @input="updateActionData('prompt', $event.target.value)"
        placeholder="Custom question (optional - leave blank for default)" 
        class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
      />
      <input 
        :value="option.action_data?.success_message" 
        @input="updateActionData('success_message', $event.target.value)"
        placeholder="Success message (optional)" 
        class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
      />
    </div>
    
    <!-- External API Call configuration -->
    <div v-if="option.action_type === 'external_api_call'" class="flex flex-col gap-2 w-full">
      <div class="flex gap-2">
        <select 
          :value="option.action_data?.api_configuration_id" 
          @change="handleApiSelection"
          class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
        >
          <option value="">Select API Integration</option>
          <optgroup label="Marketplace APIs">
            <option v-for="api in marketplaceApis" :key="api.id" :value="api.id">
              {{ api.name }} ({{ api.provider_name }})
            </option>
          </optgroup>
          <optgroup label="Custom APIs">
            <option v-for="api in customApis" :key="api.id" :value="api.id">
              {{ api.name }}
            </option>
          </optgroup>
        </select>
        <button 
          @click="$emit('open-api-wizard', option)" 
          type="button" 
          class="px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700"
        >
          Configure API
        </button>
      </div>
      
      <div v-if="option.action_data?.api_configuration_id" class="bg-blue-50 p-3 rounded border border-blue-200">
        <div class="text-sm text-blue-800">
          <div class="font-medium">{{ getSelectedApi(option.action_data.api_configuration_id)?.name }}</div>
          <div class="text-xs text-blue-600 mt-1">{{ getSelectedApi(option.action_data.api_configuration_id)?.description }}</div>
          <div class="flex items-center gap-4 mt-2 text-xs">
            <span class="bg-blue-100 px-2 py-1 rounded">{{ getSelectedApi(option.action_data.api_configuration_id)?.method }}</span>
            <span class="bg-green-100 px-2 py-1 rounded text-green-800">{{ getSelectedApi(option.action_data.api_configuration_id)?.provider_name }}</span>
            <span :class="getApiStatusClass(getSelectedApi(option.action_data.api_configuration_id)?.test_status)" class="px-2 py-1 rounded text-xs">
              {{ getSelectedApi(option.action_data.api_configuration_id)?.test_status }}
            </span>
          </div>
        </div>
      </div>
      
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <select 
          :value="option.action_data?.success_flow_id" 
          @change="updateActionData('success_flow_id', $event.target.value)"
          class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
        >
          <option value="">Success: Stay in same flow</option>
          <option value="end_session">Success: End session</option>
          <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">Success: Go to {{ flow.name }}</option>
        </select>
        <select 
          :value="option.action_data?.error_flow_id" 
          @change="updateActionData('error_flow_id', $event.target.value)"
          class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
        >
          <option value="">Error: Stay in same flow</option>
          <option value="end_session">Error: End session</option>
          <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">Error: Go to {{ flow.name }}</option>
        </select>
      </div>
      
      <div class="flex items-center gap-2">
        <input 
          type="checkbox" 
          :checked="option.action_data?.end_session_after_api" 
          @change="updateActionData('end_session_after_api', $event.target.checked)"
          :id="'end-session-' + index" 
          class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        >
        <label :for="'end-session-' + index" class="text-sm text-gray-700">End session after API call</label>
      </div>
      
      <div v-if="option.action_type === 'external_api_call' && option.action_data?.api_configuration_id && !option.action_data?.end_session_after_api && (option.action_data?.success_flow_id || option.next_flow_id)" class="flex items-center gap-2">
        <input 
          type="checkbox" 
          :checked="option.action_data?.continue_without_display || false" 
          @change="updateActionData('continue_without_display', $event.target.checked)"
          :id="'continue-without-display-' + index" 
          class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        >
        <label :for="'continue-without-display-' + index" class="text-sm text-gray-700">Continue to next flow without displaying API response</label>
      </div>
    </div>
    
    <button 
      @click="$emit('remove-option', index)" 
      class="text-red-500 hover:text-red-700 ml-2 px-2 py-1 text-sm whitespace-nowrap"
    >
      Remove
    </button>
  </div>

  <!-- Data Fields Modal -->
  <div 
    v-if="showDataFieldsModal" 
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
    @click.self="showDataFieldsModal = false"
  >
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-hidden flex flex-col">
      <!-- Modal Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <div>
          <h3 class="text-lg font-semibold text-gray-900">Configure Data Fields</h3>
          <p class="text-sm text-gray-500 mt-1">Store data that can be reused in subsequent flows using template variables</p>
        </div>
        <button 
          @click="showDataFieldsModal = false"
          class="text-gray-400 hover:text-gray-600"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
          </svg>
        </button>
      </div>

      <!-- Modal Body -->
      <div class="px-6 py-4 overflow-y-auto flex-1">
        <div class="bg-blue-50 border border-blue-200 rounded p-3 mb-4">
          <p class="text-sm text-blue-800">
            <strong>How it works:</strong> When a user selects this option, the data you configure here will be stored and can be accessed in subsequent flows or API calls using template variables like <code class="bg-blue-100 px-1 rounded">{session.data.field_name}</code> or <code class="bg-blue-100 px-1 rounded">{selected_item_data.field_name}</code>
          </p>
        </div>

        <div v-if="storeDataFields.length === 0" class="text-center py-8 text-gray-500">
          <svg class="w-12 h-12 mx-auto mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
          <p class="text-sm">No data fields configured.</p>
          <p class="text-xs mt-1">The option text will be stored as "selected_value" by default.</p>
        </div>

        <div v-else class="space-y-3">
          <div 
            v-for="(field, fieldIndex) in storeDataFields" 
            :key="fieldIndex" 
            class="bg-gray-50 rounded-lg p-4 border border-gray-200"
          >
            <div class="flex items-start gap-3">
              <div class="flex-1 space-y-3">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Field Name</label>
                  <input 
                    :value="field.key" 
                    @input="updateStoreDataField(fieldIndex, 'key', $event.target.value)"
                    placeholder="e.g., network, service, category" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
                  />
                  <p class="text-xs text-gray-500 mt-1">This will be the key used in template variables</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-1">Value</label>
                  <input 
                    :value="field.value" 
                    @input="updateStoreDataField(fieldIndex, 'value', $event.target.value)"
                    placeholder="Leave blank to use option text automatically" 
                    class="w-full rounded-md border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" 
                  />
                  <p class="text-xs text-gray-500 mt-1">If left blank, the option text "{{ option.option_text }}" will be used</p>
                </div>
              </div>
              <button 
                @click="removeStoreDataField(fieldIndex)" 
                type="button"
                class="mt-6 text-red-500 hover:text-red-700 p-2 hover:bg-red-50 rounded"
                title="Remove field"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50">
        <button 
          @click="addStoreDataField" 
          type="button"
          class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-md hover:bg-indigo-700 flex items-center gap-2"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
          </svg>
          Add Field
        </button>
        <button 
          @click="showDataFieldsModal = false"
          class="px-4 py-2 text-sm bg-gray-600 text-white rounded-md hover:bg-gray-700"
        >
          Done
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'

const props = defineProps({
  option: {
    type: Object,
    required: true
  },
  index: {
    type: Number,
    required: true
  },
  availableFlows: {
    type: Array,
    default: () => []
  },
  marketplaceApis: {
    type: Array,
    default: () => []
  },
  customApis: {
    type: Array,
    default: () => []
  }
})

const emit = defineEmits(['update-option', 'update-action-data', 'remove-option', 'open-api-wizard'])

// Modal state
const showDataFieldsModal = ref(false)

// Computed property for the checkbox value
const useRegisteredPhoneValue = computed({
  get() {
    return !!props.option.action_data?.use_registered_phone
  },
  set(value) {
    updateActionData('use_registered_phone', value)
  }
})

// Computed property for store_data fields
const storeDataFields = computed({
  get() {
    const storeData = props.option.action_data?.store_data
    if (!storeData || typeof storeData !== 'object') {
      return []
    }
    return Object.keys(storeData).map(key => ({
      key: key || '',
      value: storeData[key] || ''
    })).filter(field => field.key !== undefined)
  }
})

const addStoreDataField = () => {
  const currentStoreData = props.option.action_data?.store_data || {}
  const newStoreData = { ...currentStoreData, '': '' }
  updateActionData('store_data', newStoreData)
}

const updateStoreDataField = (fieldIndex, fieldKey, newValue) => {
  const currentStoreData = props.option.action_data?.store_data || {}
  const fields = Object.keys(currentStoreData).map(key => ({
    key: key || '',
    value: currentStoreData[key] || ''
  }))
  
  if (fields[fieldIndex]) {
    fields[fieldIndex][fieldKey] = newValue || ''
  }
  
  // Rebuild store_data object
  const newStoreData = {}
  fields.forEach(field => {
    if (field && field.key && field.key.trim() !== '') {
      // If value is empty, use option text as default
      newStoreData[field.key] = field.value && field.value.trim() !== '' 
        ? field.value 
        : props.option.option_text || ''
    }
  })
  
  updateActionData('store_data', Object.keys(newStoreData).length > 0 ? newStoreData : undefined)
}

const removeStoreDataField = (fieldIndex) => {
  const currentStoreData = props.option.action_data?.store_data || {}
  const fields = Object.keys(currentStoreData).map(key => ({
    key: key || '',
    value: currentStoreData[key] || ''
  }))
  
  if (fieldIndex >= 0 && fieldIndex < fields.length) {
    fields.splice(fieldIndex, 1)
  }
  
  // Rebuild store_data object
  const newStoreData = {}
  fields.forEach(field => {
    if (field && field.key && field.key.trim() !== '') {
      newStoreData[field.key] = field.value && field.value.trim() !== '' 
        ? field.value 
        : props.option.option_text || ''
    }
  })
  
  updateActionData('store_data', Object.keys(newStoreData).length > 0 ? newStoreData : undefined)
}

const updateOption = (key, value) => {
  emit('update-option', props.index, key, value)
}

const updateActionData = (key, value) => {
  emit('update-action-data', props.index, key, value)
}

const handleAfterInputActionChange = (event) => {
  const value = event.target.value
  updateActionData('after_input_action', value)
  
  // Set default values based on after input action
  switch (value) {
    case 'external_api_call':
      updateActionData('api_configuration_id', '')
      updateActionData('success_flow_id', '')
      updateActionData('error_flow_id', '')
      updateActionData('end_session_after_api', false)
      updateActionData('continue_without_display', false)
      break
    case 'navigate':
      updateOption('next_flow_id', '')
      break
    case 'process_data':
      updateActionData('process_type', 'process_registration')
      break
  }
}

const handleApiSelection = (event) => {
  const value = event.target.value
  updateActionData('api_configuration_id', value)
  
  // Set default values for API call
  if (value && !props.option.action_data?.end_session_after_api) {
    updateActionData('end_session_after_api', false)
  }
}

const getSelectedApi = (apiId) => {
  const allApis = [...props.marketplaceApis, ...props.customApis]
  return allApis.find(api => api.id == apiId)
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

</script>
