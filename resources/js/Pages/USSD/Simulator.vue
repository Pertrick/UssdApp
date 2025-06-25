<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-4">
          <Link
            :href="route('ussd.show', ussd.id)"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to USSD
          </Link>
          <h2 class="text-2xl font-bold text-gray-900">USSD Simulator</h2>
        </div>
        <div class="flex items-center space-x-3">
          <span class="text-sm text-gray-500">Service: {{ ussd.name }} ({{ ussd.pattern }})</span>
          <button
            @click="showCloseModal = true"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-red-700 bg-white border border-red-300 rounded-md hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors"
          >
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Close Simulation
          </button>
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Simulator UI -->
        <div class="flex flex-col items-center">
          <div class="bg-gray-900 rounded-3xl shadow-lg p-4 w-80 relative">
            <!-- Phone header -->
            <div class="flex items-center justify-between text-xs text-gray-300 mb-2">
              <span>MTN Nigeria</span>
              <span>{{ new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
            </div>
            <!-- USSD Screen -->
            <div class="bg-gray-100 rounded-xl p-4 min-h-[180px] flex flex-col justify-between">
              <div class="text-gray-900 whitespace-pre-line min-h-[100px]">
                <span v-if="!sessionStarted">Enter your phone number to start simulation.</span>
                <span v-else>{{ menuText }}</span>
              </div>
              <div v-if="errorMessage" class="text-xs text-red-600 mt-2">{{ errorMessage }}</div>
              <div v-if="sessionEnded" class="text-green-600 font-semibold mt-2">Session Ended</div>
            </div>
            <!-- Input/Keypad -->
            <div v-if="!sessionStarted" class="mt-4">
              <input v-model="phoneNumber" type="text" maxlength="15" placeholder="Phone Number" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" />
              <button @click="startSession" :disabled="loading || !phoneNumber" class="w-full mt-2 py-2 rounded bg-indigo-600 text-white font-semibold hover:bg-indigo-700 disabled:opacity-50">Start Simulation</button>
            </div>
            <div v-else-if="!sessionEnded" class="mt-4">
              <div class="flex gap-2 mb-2">
                <input v-model="userInput" @keyup.enter="sendInput" type="text" maxlength="10" placeholder="Enter option..." class="flex-1 px-3 py-2 rounded border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" />
                <button @click="sendInput" :disabled="loading || !userInput" class="px-4 py-2 rounded bg-indigo-600 text-white font-semibold hover:bg-indigo-700 disabled:opacity-50">Send</button>
              </div>
              <!-- Keypad -->
              <div class="grid grid-cols-3 gap-2">
                <button v-for="key in keypadKeys" :key="key" @click="appendKey(key)" class="py-2 rounded bg-gray-200 text-lg font-bold hover:bg-gray-300">{{ key }}</button>
                <button @click="clearInput" class="py-2 rounded bg-yellow-200 text-sm font-semibold col-span-2">Clear</button>
              </div>
            </div>
            <div v-else class="mt-4">
              <button @click="resetSimulator" class="w-full py-2 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700">Restart Simulation</button>
            </div>
            <div v-if="loading" class="absolute inset-0 bg-white bg-opacity-70 flex items-center justify-center rounded-3xl">
              <svg class="animate-spin h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
            </div>
          </div>
        </div>
        <!-- Monitoring/Logs -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 overflow-auto max-h-[600px]">
          <h3 class="text-lg font-semibold text-gray-900 mb-4">Session Monitoring</h3>
          <div v-if="!sessionStarted" class="text-gray-400">No session started yet.</div>
          <div v-else>
            <div class="mb-2 text-xs text-gray-500">Session ID: <span class="font-mono">{{ sessionId }}</span></div>
            <div class="space-y-2 max-h-[500px] overflow-y-auto">
              <div v-for="log in logs" :key="log.id" class="border-b pb-2 mb-2">
                <div class="flex items-center gap-2 text-xs">
                  <span class="font-semibold text-indigo-700">{{ log.action_type }}</span>
                  <span class="text-gray-400">{{ log.action_timestamp }}</span>
                  <span v-if="log.status === 'error'" class="text-red-600">Error</span>
                </div>
                <div v-if="log.input_data" class="text-xs text-gray-700">Input: {{ log.input_data }}</div>
                <div v-if="log.output_data" class="text-xs text-gray-900">Output: <span class="whitespace-pre-line">{{ log.output_data }}</span></div>
                <div v-if="log.error_message" class="text-xs text-red-600">{{ log.error_message }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Custom Confirmation Modal -->
    <ConfirmationModal
      :show="showCloseModal"
      title="Close Simulation"
      :message="closeModalMessage"
      confirm-text="Close Simulation"
      cancel-text="Continue Simulation"
      type="warning"
      @confirm="handleCloseSimulation"
      @cancel="showCloseModal = false"
    />
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
  ussd: Object,
});

const phoneNumber = ref('');
const sessionStarted = ref(false);
const sessionEnded = ref(false);
const sessionId = ref('');
const menuText = ref('');
const userInput = ref('');
const errorMessage = ref('');
const loading = ref(false);
const logs = ref([]);
const showCloseModal = ref(false);
const keypadKeys = ['1','2','3','4','5','6','7','8','9','*','0','#'];

const closeModalMessage = computed(() => {
  if (sessionStarted.value && !sessionEnded.value) {
    return 'Are you sure you want to close the simulation? Any active session will be ended and all progress will be lost.';
  }
  return 'Are you sure you want to close the simulation?';
});

function appendKey(key) {
  userInput.value += key;
}
function clearInput() {
  userInput.value = '';
}
function resetSimulator() {
  sessionStarted.value = false;
  sessionEnded.value = false;
  sessionId.value = '';
  menuText.value = '';
  userInput.value = '';
  errorMessage.value = '';
  logs.value = [];
  phoneNumber.value = '';
}

function handleCloseSimulation() {
  resetSimulator();
  showCloseModal.value = false;
}

function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

async function startSession() {
  loading.value = true;
  errorMessage.value = '';
  try {
    const res = await fetch(route('ussd.simulator.start', { ussd: props.ussd.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ phone_number: phoneNumber.value }),
    });
    const data = await res.json();
    if (data.success) {
      sessionStarted.value = true;
      sessionEnded.value = false;
      sessionId.value = data.session_id;
      menuText.value = data.menu_text;
      userInput.value = '';
      fetchLogs();
    } else {
      errorMessage.value = data.message || 'Failed to start session.';
    }
  } catch (e) {
    errorMessage.value = 'Network error.';
  } finally {
    loading.value = false;
  }
}

async function sendInput() {
  if (!userInput.value) return;
  loading.value = true;
  errorMessage.value = '';
  try {
    const res = await fetch(route('ussd.simulator.input', { ussd: props.ussd.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': getCsrfToken(),
      },
      body: JSON.stringify({ session_id: sessionId.value, input: userInput.value }),
    });
    const data = await res.json();
    if (data.success) {
      menuText.value = data.message;
      userInput.value = '';
      if (data.session_ended) {
        sessionEnded.value = true;
      }
    } else {
      errorMessage.value = data.message || 'Invalid input.';
    }
    fetchLogs();
  } catch (e) {
    errorMessage.value = 'Network error.';
  } finally {
    loading.value = false;
  }
}

async function fetchLogs() {
  if (!sessionId.value) return;
  try {
    const res = await fetch(route('ussd.simulator.logs', { ussd: props.ussd.id }) + `?session_id=${sessionId.value}`);
    const data = await res.json();
    logs.value = data.logs || [];
  } catch (e) {
    // ignore
  }
}
</script> 