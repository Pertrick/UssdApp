<template>
  <div class="col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 p-6 min-h-[400px]">
    <div v-if="flow">
      <div class="flex items-center justify-between mb-4">
        <div class="flex-1 mr-4">
          <div class="flex items-center gap-2 mb-1">
            <label class="block text-xs font-medium text-gray-500 uppercase tracking-wide">Flow Name</label>
            <span v-if="flow.is_root" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
              Root Flow
            </span>
          </div>
          <input
            :value="flow.name || ''"
            @input="updateFlow('name', $event.target.value)"
            type="text"
            :disabled="flow.is_root"
            :class="[
              errors?.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500',
              flow.is_root ? 'bg-gray-100 cursor-not-allowed' : '',
              'mt-1 block w-full rounded shadow-sm text-sm'
            ]"
            :placeholder="flow.is_root ? 'Root flow name cannot be changed' : 'Enter a unique flow name (e.g., buy_data_flow)'"
          />
          <p v-if="errors?.name" class="mt-1 text-xs text-red-600">
            {{ Array.isArray(errors.name) ? errors.name[0] : errors.name }}
          </p>
          <p v-if="flow.is_root" class="mt-1 text-xs text-gray-500">
            The root flow name cannot be edited as it serves as the entry point for your USSD service.
          </p>
        </div>
        <button 
          @click="$emit('delete-flow', flow)" 
          :disabled="flow.is_root"
          :class="[
            flow.is_root 
              ? 'px-2 py-1 rounded bg-gray-100 text-gray-400 text-xs font-semibold cursor-not-allowed' 
              : 'px-2 py-1 rounded bg-red-100 text-red-700 text-xs font-semibold hover:bg-red-200'
          ]"
        >
          Delete Flow
        </button>
      </div>
      
      <!-- Flow Type Selector -->
      <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
        <label class="block text-sm font-medium text-gray-700 mb-3">Flow Type</label>
        <div class="flex items-center space-x-6">
          <label class="flex items-center">
            <input 
              type="radio" 
              :value="flow.flow_type" 
              @change="updateFlow('flow_type', 'static')"
              :checked="flow.flow_type === 'static'"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">Static Flow</span>
          </label>
          <label class="flex items-center">
            <input 
              type="radio" 
              :value="flow.flow_type" 
              @change="updateFlow('flow_type', 'dynamic')"
              :checked="flow.flow_type === 'dynamic'"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">Dynamic Flow</span>
          </label>
        </div>
        <p class="mt-2 text-xs text-gray-500">
          <strong>Static:</strong> You manually define what users see. 
          <strong>Dynamic:</strong> Content is generated from API responses.
        </p>
      </div>

      <!-- Title Editor -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Title/Header</label>
        <textarea 
          :value="flow.title || ''" 
          @input="updateFlow('title', $event.target.value)"
          rows="2"
          :class="[
            errors?.title ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-indigo-500 focus:border-indigo-500',
            'mt-1 block w-full rounded shadow-sm resize-y'
          ]"
          placeholder="Enter title/header (e.g., Report type of fire)"
        ></textarea>
        <p v-if="errors?.title" class="mt-1 text-sm text-red-600">{{ Array.isArray(errors.title) ? errors.title[0] : errors.title }}</p>
        <p v-else class="mt-1 text-xs text-gray-500">This will appear above the menu options. Supports multiple lines.</p>
      </div>

      <!-- Section name (for grouping in flow list) -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Section / group</label>
        <input 
          :value="flow.section_name || ''" 
          @input="updateFlow('section_name', $event.target.value)"
          type="text"
          class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
          placeholder="e.g. MCD – Airtime, Plannet – Electricity (optional)"
        />
        <p class="mt-1 text-xs text-gray-500">Used to group flows in the list. Leave blank for &quot;Other&quot;.</p>
      </div>
      
      <!-- Dynamic Flow Configuration -->
      <DynamicFlowConfig
        v-if="flow.flow_type === 'dynamic'"
        :dynamic-config="flow.dynamic_config"
        :marketplace-apis="marketplaceApis"
        :custom-apis="customApis"
        :available-flows="availableFlows"
        @update-config="updateDynamicConfig"
        @open-api-wizard="$emit('open-api-wizard')"
      />

      <!-- Menu Text Display (Static Flow Only) - Read-only, auto-generated from options -->
      <div v-if="flow.flow_type === 'static'" class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Menu Text (Auto-generated from options)</label>
        <textarea 
          :value="flow.menu_text" 
          readonly
          rows="3" 
          class="mt-1 block w-full rounded border-gray-300 bg-gray-50 text-gray-600 cursor-not-allowed"
          placeholder="Menu text will be generated automatically from your options below"
        ></textarea>
        <p class="mt-1 text-xs text-gray-500">This menu text is automatically generated from your options below. Edit the options to update this text.</p>
      </div>
      
      <!-- Description -->
      <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Description</label>
        <input 
          :value="flow.description" 
          @input="updateFlow('description', $event.target.value)"
          class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
        />
      </div>
      
      <!-- Options Editor (Static Flow Only) -->
      <div v-if="flow.flow_type === 'static'" class="mb-4">
        <label class="block text-sm font-medium text-gray-700">Options</label>
        <p class="mt-1 text-xs text-gray-500 mb-2">
          Edit options below to configure your menu. The menu text above will be automatically generated from these options.
          <br><br>
          <strong>Input Collection Tip:</strong> For input options, you can specify what happens after collecting input. 
          Leave "Next Flow" empty to stay in the same flow, select "End session after input" to close the session, or choose a flow to navigate to.
        </p>
        <OptionEditor
          v-for="(option, idx) in flow.options"
          :key="option.id || idx"
          :option="option"
          :index="idx"
          :available-flows="availableFlows"
          :marketplace-apis="marketplaceApis"
          :custom-apis="customApis"
          @update-option="updateOption"
          @update-action-data="updateActionData"
          @remove-option="$emit('remove-option', idx)"
          @open-api-wizard="$emit('open-api-wizard', $event)"
        />
        <button 
          @click="$emit('add-option')" 
          class="mt-2 px-3 py-1 rounded bg-indigo-100 text-indigo-700 text-xs font-semibold hover:bg-indigo-200"
        >
          + Add Option
        </button>
      </div>
      
      <!-- Save/Cancel Buttons -->
      <div class="flex justify-end gap-2">
        <button 
          @click="$emit('cancel-edit')" 
          :disabled="saving"
          class="px-4 py-2 rounded bg-gray-300 text-gray-700 font-semibold hover:bg-gray-400 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Cancel
        </button>
        <button 
          @click="$emit('save-flow')" 
          :disabled="saving"
          :class="[
            hasUnsavedChanges ? 'bg-orange-600 hover:bg-orange-700' : 'bg-indigo-600 hover:bg-indigo-700',
            'px-4 py-2 rounded text-white font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2'
          ]"
        >
          <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          {{ saving ? 'Saving...' : (hasUnsavedChanges ? 'Save Changes*' : 'Save Flow') }}
        </button>
      </div>
    </div>
    <div v-else class="text-gray-400 flex items-center justify-center h-full min-h-[300px]">
      <span>Select a flow to edit or add a new one.</span>
    </div>
  </div>
</template>

<script setup>
import DynamicFlowConfig from './DynamicFlowConfig.vue'
import OptionEditor from './OptionEditor.vue'

defineProps({
  flow: {
    type: Object,
    default: null
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
  },
  hasUnsavedChanges: {
    type: Boolean,
    default: false
  },
  saving: {
    type: Boolean,
    default: false
  },
  errors: {
    type: Object,
    default: () => ({})
  }
})

const emit = defineEmits([
  'update-flow',
  'update-dynamic-config',
  'update-option',
  'update-action-data',
  'add-option',
  'remove-option',
  'save-flow',
  'cancel-edit',
  'delete-flow',
  'open-api-wizard'
])

const updateFlow = (key, value) => {
  emit('update-flow', key, value)
}

const updateDynamicConfig = (key, value) => {
  emit('update-dynamic-config', key, value)
}

const updateOption = (index, key, value) => {
  emit('update-option', index, key, value)
}

const updateActionData = (index, key, value) => {
  emit('update-action-data', index, key, value)
}
</script>
