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
    </div>
    
    <button 
      @click="$emit('remove-option', index)" 
      class="text-red-500 hover:text-red-700 ml-2 px-2 py-1 text-sm whitespace-nowrap"
    >
      Remove
    </button>
  </div>
</template>

<script setup>
import { computed } from 'vue'

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

// Computed property for the checkbox value
const useRegisteredPhoneValue = computed({
  get() {
    return !!props.option.action_data?.use_registered_phone
  },
  set(value) {
    updateActionData('use_registered_phone', value)
  }
  })

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
