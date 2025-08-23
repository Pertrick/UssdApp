<template>
  <Head title="USSD Simulator" />

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
                <input 
                  v-model="userInput" 
                  @keyup.enter="sendInput" 
                  type="text" 
                  :maxlength="inputMaxLength" 
                  :placeholder="inputPlaceholder" 
                  class="flex-1 px-3 py-2 rounded border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" 
                />
                <button @click="sendInput" :disabled="loading || !userInput" class="px-4 py-2 rounded bg-indigo-600 text-white font-semibold hover:bg-indigo-700 disabled:opacity-50">Send</button>
              </div>
              <!-- Keypad - only show for numeric input or when collecting numeric data -->
              <div v-if="shouldShowKeypad || showKeypad" class="grid grid-cols-3 gap-2">
                <button v-for="key in keypadKeys" :key="key" @click="appendKey(key)" class="py-2 rounded bg-gray-200 text-lg font-bold hover:bg-gray-300">{{ key }}</button>
                <button @click="clearInput" class="py-2 rounded bg-yellow-200 text-sm font-semibold col-span-2">Clear</button>
              </div>
              <!-- Text input helper buttons -->
              <div v-else class="flex gap-2 mt-2">
                <button @click="clearInput" class="flex-1 py-2 rounded bg-yellow-200 text-sm font-semibold">Clear</button>
                <button @click="showKeypad = true" class="flex-1 py-2 rounded bg-blue-200 text-sm font-semibold">Show Keypad</button>
              </div>
              <!-- Hide keypad button when manually shown -->
              <div v-if="showKeypad && !shouldShowKeypad" class="mt-2">
                <button @click="showKeypad = false" class="w-full py-2 rounded bg-gray-200 text-sm font-semibold">Hide Keypad</button>
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
import { router, usePage, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';

const props = defineProps({
  ussd: Object,
  session: Object,
  logs: Array,
});

const phoneNumber = ref('0700000000');
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
const currentInput = ref('');
const sessionHistory = ref([]);
const isSessionActive = ref(false);
const environment = ref('simulation'); // simulation, sandbox, live
const inputMaxLength = ref(10);
const inputPlaceholder = ref('Enter option...');
const showKeypad = ref(false);
const currentInputType = ref('menu'); // menu, text, number, phone, account, pin, amount

const closeModalMessage = computed(() => {
  if (sessionStarted.value && !sessionEnded.value) {
    return 'Are you sure you want to close the simulation? Any active session will be ended and all progress will be lost.';
  }
  return 'Are you sure you want to close the simulation?';
});

const shouldShowKeypad = computed(() => {
  return ['input_number', 'input_phone', 'input_account', 'input_pin', 'input_amount', 'input_selection'].includes(currentInputType.value);
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
  phoneNumber.value = '0700000000'; // Auto-populate with default number
  inputMaxLength.value = 10;
  inputPlaceholder.value = 'Enter option...';
  showKeypad.value = false;
  currentInputType.value = 'menu';
}

function handleCloseSimulation() {
  resetSimulator();
  showCloseModal.value = false;
}

function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  if (!meta) {
    console.error('CSRF token meta tag not found');
    return '';
  }
  const token = meta.getAttribute('content');
  if (!token) {
    console.error('CSRF token content is empty');
    return '';
  }
  return token;
}

async function startSession() {
  loading.value = true;
  errorMessage.value = '';
  
  const csrfToken = getCsrfToken();
  if (!csrfToken) {
    errorMessage.value = 'CSRF token not available. Please refresh the page.';
    loading.value = false;
    return;
  }
  
  try {
    const res = await fetch(route('ussd.simulator.start', { ussd: props.ussd.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
      },
      body: JSON.stringify({ phone_number: phoneNumber.value, environment: environment.value }),
    });
    
    if (!res.ok) {
      console.error('Response status:', res.status);
      if (res.status === 419) {
        errorMessage.value = 'CSRF token mismatch. Please refresh the page.';
      } else {
        errorMessage.value = `Server error: ${res.status}`;
      }
      return;
    }
    
    const data = await res.json();
    if (data.success) {
      sessionStarted.value = true;
      sessionEnded.value = false;
      sessionId.value = data.session_id;
      menuText.value = data.menu_text;
      userInput.value = '';
      
      // Set initial input type and UI
      currentInputType.value = 'menu';
      updateInputUI('menu');
      
      fetchLogs();
    } else {
      errorMessage.value = data.message || 'Failed to start session.';
    }
  } catch (e) {
    console.error('Network error:', e);
    errorMessage.value = 'Network error. Please check your connection.';
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
      body: JSON.stringify({ session_id: sessionId.value, input: userInput.value, environment: environment.value }),
    });
    const data = await res.json();
    if (data.success) {
      menuText.value = data.message;
      userInput.value = '';
      
      // Update input type and UI based on response
      if (data.input_type) {
        currentInputType.value = data.input_type;
        updateInputUI(data.input_type);
      } else {
        currentInputType.value = 'menu';
        updateInputUI('menu');
      }
      
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

function updateInputUI(inputType) {
  switch (inputType) {
    case 'input_text':
      inputMaxLength.value = 50;
      inputPlaceholder.value = 'Enter text...';
      break;
    case 'input_number':
      inputMaxLength.value = 10;
      inputPlaceholder.value = 'Enter number...';
      break;
    case 'input_phone':
      inputMaxLength.value = 15;
      inputPlaceholder.value = 'Enter phone number...';
      break;
    case 'input_account':
      inputMaxLength.value = 20;
      inputPlaceholder.value = 'Enter account number...';
      break;
    case 'input_pin':
      inputMaxLength.value = 6;
      inputPlaceholder.value = 'Enter PIN...';
      break;
    case 'input_amount':
      inputMaxLength.value = 10;
      inputPlaceholder.value = 'Enter amount...';
      break;
    case 'input_selection':
      inputMaxLength.value = 5;
      inputPlaceholder.value = 'Enter selection...';
      break;
    default:
      inputMaxLength.value = 10;
      inputPlaceholder.value = 'Enter option...';
      break;
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

// Environment status
const environmentStatus = computed(() => {
  switch (environment.value) {
    case 'simulation':
      return {
        label: 'Simulation Mode',
        color: 'bg-blue-100 text-blue-800',
        description: 'Testing flows locally - no real costs'
      };
    case 'sandbox':
      return {
        label: 'Sandbox Mode',
        color: 'bg-yellow-100 text-yellow-800',
        description: 'Testing with Africastalking sandbox'
      };
    case 'live':
      return {
        label: 'Live Mode',
        color: 'bg-green-100 text-green-800',
        description: 'Real USSD service - live environment'
      };
    default:
      return {
        label: 'Unknown',
        color: 'bg-gray-100 text-gray-800',
        description: 'Environment not configured'
      };
  }
});

// USSD Code display
const ussdCode = computed(() => {
  if (!props.ussd) return '*123#';
  
  switch (environment.value) {
    case 'simulation':
      return props.ussd.testing_ussd_code || '*123#';
    case 'sandbox':
      return props.ussd.testing_ussd_code || '*123#';
    case 'live':
      return props.ussd.live_ussd_code || '*456#';
    default:
      return '*123#';
  }
});

const resetSession = () => {
  sessionId.value = '';
  isSessionActive.value = false;
  sessionHistory.value = [];
  currentInput.value = '';
};

const switchEnvironment = (newEnv) => {
  environment.value = newEnv;
  resetSession();
};

// Auto-scroll to bottom of chat
const scrollToBottom = () => {
  const chatContainer = document.getElementById('chat-container');
  if (chatContainer) {
    chatContainer.scrollTop = chatContainer.scrollHeight;
  }
};

onMounted(() => {
  // Set environment based on USSD configuration
  if (props.ussd) {
    if (props.ussd.environment === 'live') {
      environment.value = 'live';
    } else if (props.ussd.environment === 'sandbox') {
      environment.value = 'sandbox';
    } else {
      environment.value = 'simulation';
    }
  }
});
</script> 