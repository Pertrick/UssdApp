<template>
  <div class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
    <h4 class="text-sm font-medium text-blue-900 mb-3">Dynamic Flow Configuration</h4>
    
    <!-- API Selection -->
    <div class="mb-4">
      <label class="block text-sm font-medium text-gray-700 mb-2">Data Source API</label>
      <div class="flex gap-2">
        <select 
          :value="dynamicConfig.api_configuration_id" 
          @input="updateConfig('api_configuration_id', $event.target.value)"
          class="flex-1 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
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
          @click="$emit('open-api-wizard')" 
          type="button" 
          class="px-3 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700"
        >
          Configure API
        </button>
      </div>
      <p class="mt-1 text-xs text-gray-500">Select the API that will provide the dynamic content for this flow</p>
    </div>

    <!-- API Response Configuration -->
    <div v-if="dynamicConfig.api_configuration_id" class="space-y-4">
      <!-- Response Path Configuration -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Options List Path</label>
          <input 
            :value="dynamicConfig.list_path" 
            @input="updateConfig('list_path', $event.target.value)"
            type="text"
            class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g., data.bundles or results"
          />
          <p class="mt-1 text-xs text-gray-500">JSON path to the array/object of options (e.g., data.bundles or data for object keys)</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Option Label Field</label>
          <input 
            :value="dynamicConfig.label_field" 
            @input="updateConfig('label_field', $event.target.value)"
            type="text"
            class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g., name, name+price, {name} - {price}"
          />
          <p class="mt-1 text-xs text-gray-500">
            <strong>Single:</strong> name<br/>
            <strong>Multiple:</strong> name+price (separated by +)<br/>
            <strong>Template:</strong> {name} - {price} (use {} for placeholders)<br/>
            <strong>Object Key:</strong> {key} or key (for object keys like currency codes)
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Option Value Field</label>
          <input 
            :value="dynamicConfig.value_field" 
            @input="updateConfig('value_field', $event.target.value)"
            type="text"
            class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g., id or code"
          />
          <p class="mt-1 text-xs text-gray-500">Field name for option value</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Empty State Message</label>
          <input 
            :value="dynamicConfig.empty_message" 
            @input="updateConfig('empty_message', $event.target.value)"
            type="text"
            class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="e.g., No options available"
          />
          <p class="mt-1 text-xs text-gray-500">Message shown when no data is available</p>
        </div>
      </div>

      <!-- Pagination Configuration -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Items Per Page</label>
          <select 
            :value="dynamicConfig.items_per_page" 
            @change="updateConfig('items_per_page', $event.target.value)"
            class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
          >
            <option value="5">5 items (Recommended for mobile)</option>
            <option value="7">7 items (Standard USSD)</option>
            <option value="10">10 items (Large screens)</option>
            <option value="15">15 items (Maximum recommended)</option>
          </select>
          <p class="mt-1 text-xs text-gray-500">Number of options to show per screen. System will automatically add "Next" and "Back" buttons when needed.</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Navigation Labels</label>
          <div class="space-y-2">
            <input 
              :value="dynamicConfig.next_label" 
              @input="updateConfig('next_label', $event.target.value)"
              type="text"
              class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              placeholder="Next"
            />
            <input 
              :value="dynamicConfig.back_label" 
              @input="updateConfig('back_label', $event.target.value)"
              type="text"
              class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
              placeholder="Back"
            />
          </div>
          <p class="mt-1 text-xs text-gray-500">Custom labels for navigation buttons (optional)</p>
        </div>
      </div>

      <!-- Flow Continuation Logic -->
      <div class="p-3 bg-yellow-50 border border-yellow-200 rounded">
        <h5 class="text-sm font-medium text-yellow-800 mb-2">Flow Continuation</h5>
        <div class="space-y-2">
          <label class="flex items-center">
            <input 
              type="radio" 
              :value="dynamicConfig.continuation_type" 
              @change="updateConfig('continuation_type', 'continue')"
              :checked="dynamicConfig.continuation_type === 'continue'"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">Continue to next flow after selection</span>
          </label>
          <label class="flex items-center">
            <input 
              type="radio" 
              :value="dynamicConfig.continuation_type" 
              @change="updateConfig('continuation_type', 'end')"
              :checked="dynamicConfig.continuation_type === 'end'"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">End session after selection</span>
          </label>
          <label class="flex items-center">
            <input 
              type="radio" 
              :value="dynamicConfig.continuation_type" 
              @change="updateConfig('continuation_type', 'api_dependent')"
              :checked="dynamicConfig.continuation_type === 'api_dependent'"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">Let API response determine next step</span>
          </label>
          <label class="flex items-center">
            <input 
              type="radio" 
              :value="dynamicConfig.continuation_type" 
              @change="updateConfig('continuation_type', 'continue_without_display')"
              :checked="dynamicConfig.continuation_type === 'continue_without_display'"
              class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
            />
            <span class="ml-2 text-sm text-gray-700">Continue to next flow without displaying options</span>
          </label>
        </div>
        <p class="mt-2 text-xs text-yellow-700">
          <strong>Continue without display:</strong> API is called, response is stored, and user is automatically navigated to the next flow. Useful for validation flows where you don't need to show a menu.
        </p>
      </div>

      <!-- Next Flow Selection (for continue options) -->
      <div v-if="dynamicConfig.continuation_type === 'continue' || dynamicConfig.continuation_type === 'continue_without_display'">
        <label class="block text-sm font-medium text-gray-700 mb-2">Next Flow</label>
        <select 
          :value="dynamicConfig.next_flow_id" 
          @change="updateConfig('next_flow_id', $event.target.value)"
          class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
        >
          <option value="">Select next flow</option>
          <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">
            {{ flow.name }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  dynamicConfig: {
    type: Object,
    required: true
  },
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

const emit = defineEmits(['update-config', 'open-api-wizard'])

const updateConfig = (key, value) => {
  emit('update-config', key, value)
}
</script>
