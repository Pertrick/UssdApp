<template>
  <div class="col-span-1 bg-white rounded-xl shadow-sm border border-gray-200 p-4 h-full flex flex-col min-w-0">
    <div class="flex items-center justify-between mb-3 flex-shrink-0">
      <h3 class="text-lg font-semibold text-gray-900">Flows</h3>
      <button
        @click="$emit('add-flow')"
        class="px-2 py-1.5 rounded-lg bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700 shadow-sm"
      >
        + Add Flow
      </button>
    </div>

    <!-- Search + filter -->
    <div class="space-y-2 mb-3 flex-shrink-0 pb-2 border-b border-gray-100">
      <div class="relative">
        <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none" aria-hidden="true">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
        </span>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search flows..."
          class="w-full rounded-lg border border-gray-300 pl-8 pr-2 py-1.5 text-sm placeholder-gray-400 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
        />
      </div>
      <div>
        <label for="flow-section-filter" class="block text-xs font-medium text-gray-500 mb-1">Filter by section</label>
        <select
          id="flow-section-filter"
          v-model="sectionFilter"
          class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 bg-white"
        >
          <option value="">All sections</option>
          <option v-for="tag in sectionTags" :key="tag" :value="tag">{{ tag }}</option>
        </select>
      </div>
      <p class="text-[11px] text-gray-400 leading-tight">
        Sections come from each flow’s <strong>Section / group</strong> field. Set it when you add or edit a flow.
      </p>
    </div>

    <div class="max-h-[60vh] overflow-y-auto pr-1 flex-1 min-h-0 space-y-3">
      <template v-for="(group, sectionKey) in groupedFlows" :key="sectionKey">
        <div class="rounded-lg border border-gray-200 bg-gray-50/50 overflow-hidden">
          <!-- Section header (collapsible) -->
          <button
            type="button"
            class="w-full flex items-center justify-between px-3 py-2 text-sm font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors cursor-pointer select-none border-b border-gray-200"
            @click="toggleSection(sectionKey)"
          >
            <span class="uppercase tracking-wide">{{ sectionLabel(sectionKey) }}</span>
            <svg
              :class="['w-4 h-4 text-gray-500 transition-transform', expandedSections[sectionKey] !== false ? 'rotate-180' : '']"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
              aria-hidden="true"
            >
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <!-- Flow items in this section -->
          <ul v-show="expandedSections[sectionKey] !== false" class="p-1.5 space-y-1">
            <li
              v-for="flow in group"
              :key="flow.id"
              :class="[
                selectedFlow && selectedFlow.id === flow.id ? 'bg-indigo-100 border-indigo-200' : 'bg-white border-transparent',
                'rounded-lg border p-2 cursor-pointer hover:bg-indigo-50 transition-colors'
              ]"
              @click="$emit('select-flow', flow)"
            >
              <div class="flex items-center justify-between gap-1.5">
                <span class="font-medium text-gray-900 truncate flex-1 text-sm">{{ flow.name }}</span>
                <div class="flex items-center gap-1 flex-shrink-0">
                  <span
                    :class="chipClass(flow.section_name)"
                    class="text-[10px] px-1.5 py-0.5 rounded font-medium"
                  >
                    {{ sectionTag(flow.section_name) }}
                  </span>
                  <span v-if="flow.is_root" class="text-[10px] px-1.5 py-0.5 rounded font-medium bg-green-100 text-green-700">Root</span>
                  <span v-if="selectedFlow && selectedFlow.id === flow.id && hasUnsavedChanges" class="text-orange-600 font-semibold" title="Unsaved">*</span>
                </div>
              </div>
              <div class="text-xs text-gray-500 truncate mt-1">
                <span v-if="flow.title" class="font-medium text-gray-600">{{ flow.title }}</span>
                <span v-else class="text-gray-400">{{ flow.menu_text || '—' }}</span>
              </div>
            </li>
          </ul>
        </div>
      </template>
      <!-- Empty states -->
      <div v-if="flows.length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50/50 p-4 text-center">
        <p class="text-sm text-gray-600">No flows yet.</p>
        <p class="text-xs text-gray-500 mt-1">Click <strong>+ Add Flow</strong> to create one.</p>
      </div>
      <div v-else-if="Object.keys(groupedFlows).length === 0" class="rounded-lg border border-dashed border-gray-300 bg-gray-50/50 p-4 text-center">
        <p class="text-sm text-gray-600">No flows match your search or filter.</p>
        <p class="text-xs text-gray-500 mt-1">Clear the search box or choose “All sections”.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'

const props = defineProps({
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

const searchQuery = ref('')
const sectionFilter = ref('')
const expandedSections = ref({})

// Tag: "MCD – Airtime" → "MCD"; "Plannet" → "Plannet"; null/empty → "Other"
function sectionTag(sectionName) {
  if (!sectionName || !String(sectionName).trim()) return 'Other'
  const s = String(sectionName).trim()
  const idx = s.indexOf(' – ')
  return idx >= 0 ? s.slice(0, idx) : s
}// Section key for grouping: use section_name or '__other__'
function sectionKey(flow) {
  const sn = flow.section_name
  if (sn && String(sn).trim()) return String(sn).trim()
  return '__other__'
}

// Stable chip color from tag
const CHIP_CLASSES = [
  'bg-amber-100 text-amber-800',
  'bg-blue-100 text-blue-800',
  'bg-emerald-100 text-emerald-800',
  'bg-violet-100 text-violet-800',
  'bg-slate-200 text-slate-800'
]
function chipClass(sectionName) {
  const tag = sectionTag(sectionName)
  const id = tag.split('').reduce((a, c) => a + c.charCodeAt(0), 0)
  return CHIP_CLASSES[Math.abs(id) % CHIP_CLASSES.length]
}

function sectionLabel(key) {
  return key === '__other__' ? 'Other' : key
}

// Distinct section tags for filter dropdown (excluding "Other" as a tag we show in dropdown = sections that have a name)
const sectionTags = computed(() => {
  const set = new Set()
  props.flows.forEach(f => {
    const tag = sectionTag(f.section_name)
    if (tag !== 'Other') set.add(tag)
  })
  return [...set].sort((a, b) => a.localeCompare(b))
})

// Filter by search + section, then group
const groupedFlows = computed(() => {
  const q = (searchQuery.value || '').trim().toLowerCase()
  const filterTag = (sectionFilter.value || '').trim()
  let list = props.flows
  if (q) {
    list = list.filter(f => {
      const name = (f.name || '').toLowerCase()
      const title = (f.title || '').toLowerCase()
      const menu = (f.menu_text || '').toLowerCase()
      return name.includes(q) || title.includes(q) || menu.includes(q)
    })
  }
  if (filterTag) {
    list = list.filter(f => sectionTag(f.section_name) === filterTag)
  }
  const groups = {}
  list.forEach(f => {
    const key = sectionKey(f)
    if (!groups[key]) groups[key] = []
    groups[key].push(f)
  })
  // Sort sections: __other__ last, else alphabetically
  const keys = Object.keys(groups).sort((a, b) => {
    if (a === '__other__') return 1
    if (b === '__other__') return -1
    return a.localeCompare(b)
  })
  const out = {}
  keys.forEach(k => { out[k] = groups[k] })
  return out
})

function toggleSection(key) {
  const next = !(expandedSections.value[key] !== false)
  expandedSections.value = { ...expandedSections.value, [key]: next }
}

// Keep all sections expanded when groupedFlows keys change
watch(
  () => Object.keys(groupedFlows.value),
  (keys) => {
    const cur = { ...expandedSections.value }
    let changed = false
    keys.forEach(k => {
      if (cur[k] === undefined) { cur[k] = true; changed = true }
    })
    if (changed) expandedSections.value = cur
  },
  { immediate: true }
)
</script>