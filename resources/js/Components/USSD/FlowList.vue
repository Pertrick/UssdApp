<template>
  <div class="col-span-1 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Flows</h3>
      <button 
        @click="$emit('add-flow')" 
        class="px-2 py-1 rounded bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700"
      >
        + Add Flow
      </button>
    </div>
    <ul>
      <li 
        v-for="flow in flows" 
        :key="flow.id" 
        :class="[
          selectedFlow && selectedFlow.id === flow.id ? 'bg-indigo-50' : '', 
          'rounded p-2 mb-2 cursor-pointer hover:bg-indigo-100'
        ]" 
        @click="$emit('select-flow', flow)"
      >
        <div class="flex items-center justify-between">
          <span class="font-medium">{{ flow.name }}</span>
          <div class="flex items-center gap-2">
            <span v-if="flow.is_root" class="text-xs text-green-600">Root</span>
            <span v-if="selectedFlow && selectedFlow.id === flow.id && hasUnsavedChanges" class="text-xs text-orange-600 font-semibold">*</span>
          </div>
        </div>
        <div class="text-xs text-gray-500 truncate">
          <div v-if="flow.title" class="font-medium">{{ flow.title }}</div>
          <div>{{ flow.menu_text }}</div>
        </div>
      </li>
    </ul>
  </div>
</template>

<script setup>
defineProps({
  flows: {
    type: Array,
    required: true
  },
  selectedFlow: {
    type: Object,
    default: null
  },
  hasUnsavedChanges: {
    type: Boolean,
    default: false
  }
})

defineEmits(['add-flow', 'select-flow'])
</script>


