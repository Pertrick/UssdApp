<template>
  <div v-if="show" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/4 xl:w-2/3 shadow-lg rounded-md bg-white max-w-4xl">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Add New Flow</h3>
          <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div class="max-h-[70vh] overflow-y-auto">
          <div class="space-y-4">
            <div v-if="errors.general" class="bg-red-50 border border-red-200 rounded-md p-3">
              <p class="text-sm text-red-600">{{ errors.general }}</p>
            </div>
            
            <!-- Flow Type Selector -->
            <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
              <label class="block text-sm font-medium text-gray-700 mb-3">Flow Type</label>
              <div class="flex items-center space-x-6">
                <label class="flex items-center">
                  <input 
                    type="radio" 
                    :value="form.flow_type" 
                    @change="updateForm('flow_type', 'static')"
                    :checked="form.flow_type === 'static'"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                  />
                  <span class="ml-2 text-sm text-gray-700">Static Flow</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="radio" 
                    :value="form.flow_type" 
                    @change="updateForm('flow_type', 'dynamic')"
                    :checked="form.flow_type === 'dynamic'"
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
            
            <div>
              <label class="block text-sm font-medium text-gray-700">Flow Name</label>
              <input 
                :value="form.name" 
                @input="updateForm('name', $event.target.value)"
                type="text"
                :class="[errors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter flow name"
              />
              <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Title/Header (Optional)</label>
              <input 
                :value="form.title" 
                @input="updateForm('title', $event.target.value)"
                type="text"
                :class="[errors.title ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter title/header (e.g., Report type of fire)"
              />
              <p v-if="errors.title" class="mt-1 text-sm text-red-600">{{ errors.title }}</p>
              <p class="mt-1 text-xs text-gray-500">This will appear above the menu options (optional)</p>
            </div>
            
            <!-- Dynamic Flow Configuration -->
            <DynamicFlowConfig
              v-if="form.flow_type === 'dynamic'"
              :dynamic-config="form.dynamic_config"
              :marketplace-apis="marketplaceApis"
              :custom-apis="customApis"
              :available-flows="availableFlows"
              @update-config="updateDynamicConfig"
              @open-api-wizard="$emit('open-api-wizard')"
            />

            <div>
              <label class="block text-sm font-medium text-gray-700">Description</label>
              <input 
                :value="form.description" 
                @input="updateForm('description', $event.target.value)"
                type="text"
                :class="[errors.description ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter flow description"
              />
              <p v-if="errors.description" class="mt-1 text-sm text-red-600">{{ errors.description }}</p>
            </div>
          </div>
        </div>
        
        <!-- Modal Actions -->
        <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
          <button 
            @click="$emit('close')" 
            :disabled="saving"
            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Cancel
          </button>
          <button 
            @click="$emit('save')" 
            :disabled="saving"
            :class="[
              saving ? 'bg-gray-400' : 'bg-indigo-600 hover:bg-indigo-700',
              'px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2'
            ]"
          >
            <svg v-if="saving" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ saving ? 'Adding...' : 'Add Flow' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import DynamicFlowConfig from './DynamicFlowConfig.vue'

defineProps({
  show: {
    type: Boolean,
    default: false
  },
  form: {
    type: Object,
    required: true
  },
  errors: {
    type: Object,
    default: () => ({})
  },
  saving: {
    type: Boolean,
    default: false
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

const emit = defineEmits(['close', 'save', 'update-form', 'update-dynamic-config', 'open-api-wizard'])

const updateForm = (key, value) => {
  emit('update-form', key, value)
}

const updateDynamicConfig = (key, value) => {
  emit('update-dynamic-config', key, value)
}
</script>
