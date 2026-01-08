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
          <span class="text-sm text-gray-500">Service: {{ ussd.name }} ({{ getCurrentUssdCode() }})</span>
          
          <!-- Environment Indicator (Read-only) -->
          <div class="flex items-center space-x-2">
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span :class="getEnvironmentBadgeClass()" class="px-3 py-1 text-xs font-semibold rounded-full shadow-sm">
              {{ getEnvironmentLabel() }}
            </span>
            <span class="text-xs text-gray-500">
              ({{ environment === 'production' ? 'Live' : 'Testing' }})
            </span>
          </div>
          
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
      <!-- Error Banner -->
      <div v-if="validationError" class="max-w-5xl mx-auto mb-6">
        <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-md shadow-sm">
          <div class="flex">
            <div class="flex-shrink-0">
              <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
              </svg>
            </div>
            <div class="ml-3 flex-1">
              <h3 class="text-sm font-medium text-red-800">
                Cannot Start Simulation
              </h3>
              <div class="mt-2 text-sm text-red-700">
                <p>{{ validationError }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Simulator UI -->
        <div class="flex flex-col items-center">
          <div class="bg-gray-900 rounded-3xl shadow-lg p-4 w-80 relative">
            <!-- Phone header -->
            <div class="flex items-center justify-between text-xs text-gray-300 mb-2">
              <span>MTN Nigeria</span>
              <div class="flex items-center space-x-2">
                <span :class="getEnvironmentBadgeClass()" class="px-2 py-1 rounded text-xs font-medium">
                  {{ getEnvironmentLabel() }}
                </span>
                <span>{{ new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}</span>
              </div>
            </div>
            <!-- USSD Screen -->
            <div class="bg-gray-100 rounded-xl p-4 min-h-[180px] flex flex-col justify-between">
              <div class="text-gray-900 whitespace-pre-line min-h-[100px] text-sm">
                <span v-if="!sessionStarted" class="text-xs">Enter your phone number to start simulation.</span>
                <span v-else>
                  <div v-if="currentFlowTitle" class="font-semibold mb-1 text-xs">{{ currentFlowTitle }}</div>
                  <div v-if="currentFlowDescription" class="text-xs text-gray-600 mb-1">{{ currentFlowDescription }}</div>
                  <div class="text-xs leading-relaxed">{{ menuText }}</div>
                </span>
              </div>
              <div v-if="errorMessage" class="text-xs text-red-600 mt-2">{{ errorMessage }}</div>
              <div v-if="sessionEnded" class="text-green-600 font-semibold mt-2">Session Ended</div>
            </div>
            <!-- Input/Keypad -->
            <div v-if="!sessionStarted" class="mt-4">
              <form @submit.prevent="startSession">
                <input v-model="phoneNumber" type="text" maxlength="15" placeholder="Phone Number" class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" required />
                <button type="submit" :disabled="loading || !phoneNumber" class="w-full mt-2 py-2 rounded bg-indigo-600 text-white font-semibold hover:bg-indigo-700 disabled:opacity-50">Start Simulation</button>
              </form>
            </div>
            <div v-else-if="!sessionEnded" class="mt-4">
              <div class="flex gap-2 mb-2">
                <div class="flex-1">
                  <input 
                    v-model="userInput" 
                    @keyup.enter="sendInput" 
                    type="text" 
                    :maxlength="inputMaxLength" 
                    :placeholder="inputPlaceholder" 
                    class="w-full px-3 py-2 rounded border border-gray-300 focus:ring-indigo-500 focus:border-indigo-500" 
                  />
                  <div class="text-xs text-gray-500 mt-1">
                    Type: {{ currentInputType }} | Max: {{ inputMaxLength }} chars
                  </div>
                </div>
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
            
            <!-- API Call Status -->
            <div v-if="apiCallInProgress" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <div class="flex items-center gap-2">
                <svg class="animate-spin h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span class="text-sm font-medium text-blue-800">Processing API call...</span>
              </div>
              <div class="text-xs text-blue-600 mt-1">{{ apiCallStatus }}</div>
            </div>
            
            <!-- API Call Result -->
            <div v-if="lastApiResult" class="mb-4 p-3 rounded-lg" :class="lastApiResult.success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'">
              <div class="flex items-center gap-2 mb-2">
                <svg v-if="lastApiResult.success" class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
                <svg v-else class="h-4 w-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
                <span class="text-sm font-medium" :class="lastApiResult.success ? 'text-green-800' : 'text-red-800'">
                  API Call {{ lastApiResult.success ? 'Successful' : 'Failed' }}
                </span>
                <span v-if="lastApiResult.simulated" class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">SIMULATED</span>
                <span v-else-if="environment === 'testing'" class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">TESTING</span>
                <span v-else-if="environment === 'production'" class="text-xs bg-red-100 text-red-800 px-2 py-1 rounded">LIVE</span>
              </div>
              <div class="text-xs" :class="lastApiResult.success ? 'text-green-700' : 'text-red-700'">
                {{ lastApiResult.message }}
              </div>
              <div v-if="lastApiResult.data && lastApiResult.data.transaction_id" class="text-xs text-gray-600 mt-1">
                Transaction ID: {{ lastApiResult.data.transaction_id }}
              </div>
            </div>
            
            <div class="space-y-2 max-h-[400px] overflow-y-auto">
              <div v-for="log in logs" :key="log.id" class="border-b pb-2 mb-2">
                <div class="flex items-center gap-2 text-xs">
                  <span class="font-semibold text-indigo-700">{{ log.action_type }}</span>
                  <span class="text-gray-400">{{ log.action_timestamp }}</span>
                  <span v-if="log.status === 'error'" class="text-red-600">Error</span>
                  <span v-if="log.action_type === 'api_call_success'" class="text-green-600">API Success</span>
                  <span v-if="log.action_type === 'api_call_error'" class="text-red-600">API Error</span>
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
import { ref, reactive, onMounted, computed, watch } from 'vue';
import { router, usePage, Link, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import { useToast } from 'vue-toastification';

const toast = useToast();
const page = usePage();

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
const currentFlowTitle = ref('');
const currentFlowDescription = ref('');
const userInput = ref('');
const errorMessage = ref('');
const validationError = ref('');
const loading = ref(false);
const logs = ref([]);
const showCloseModal = ref(false);
const keypadKeys = ['1','2','3','4','5','6','7','8','9','*','0','#'];
const currentInput = ref('');
const sessionHistory = ref([]);
const isSessionActive = ref(false);
// Use USSD's current environment (testing by default, production when live)
const environment = ref(props.ussd.environment?.name || 'testing')
const inputMaxLength = ref(10);
const inputPlaceholder = ref('Enter option...');
const showKeypad = ref(false);
const currentInputType = ref('menu'); // menu, text, number, phone, account, pin, amount
const apiCallInProgress = ref(false);
const apiCallStatus = ref('');
const lastApiResult = ref(null);

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
  currentFlowTitle.value = '';
  currentFlowDescription.value = '';
  environment.value = props.ussd.environment?.name || 'testing';
  userInput.value = '';
  errorMessage.value = '';
  logs.value = [];
  phoneNumber.value = '0700000000'; // Auto-populate with default number
  inputMaxLength.value = 10;
  inputPlaceholder.value = 'Enter option...';
  showKeypad.value = false;
  currentInputType.value = 'menu';
  apiCallInProgress.value = false;
  apiCallStatus.value = '';
  lastApiResult.value = null;
}

function handleCloseSimulation() {
  resetSimulator();
  showCloseModal.value = false;
}

// Environment-related functions
function getCurrentUssdCode() {
  // Use the current USSD code based on environment
  if (environment.value === 'production') {
    return props.ussd.live_ussd_code || 'Not configured'
  }
  return props.ussd.testing_ussd_code || props.ussd.pattern || 'Not configured'
}

function getEnvironmentLabel() {
  const labels = {
    testing: 'TEST',
    production: 'LIVE'
  };
  return labels[environment.value] || 'TEST';
}

function getEnvironmentBadgeClass() {
  const classes = {
    testing: 'bg-yellow-100 text-yellow-800 border border-yellow-200',
    production: 'bg-green-100 text-green-800 border border-green-200'
  };
  return classes[environment.value] || 'bg-yellow-100 text-yellow-800 border border-yellow-200';
}

// Simplified CSRF token handling using Inertia
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}

async function startSession() {
  loading.value = true;
  errorMessage.value = '';
  validationError.value = '';
  
  const csrfToken = getCsrfToken();
  
  try {
    const res = await fetch(route('ussd.simulator.start', { ussd: props.ussd.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ phone_number: phoneNumber.value, environment: environment.value }),
    });
    
    console.log('Response status:', res.status);
    
    if (!res.ok) {
      const errorData = await res.json().catch(() => ({ error: `Server error: ${res.status}` }));
      const errorMsg = errorData.error || errorData.message || `Server error: ${res.status}`;
      
      // Check if it's a validation error (400 status)
      if (res.status === 400) {
        validationError.value = errorMsg;
        toast.error(errorMsg, { timeout: 8000 });
      } else {
        errorMessage.value = errorMsg;
        toast.error(errorMsg);
      }
      return;
    }
    
    const data = await res.json();
    console.log('Response data:', data);
    
    if (data.success) {
      sessionStarted.value = true;
      sessionEnded.value = false;
      sessionId.value = data.session_id;
      menuText.value = data.menu_text;
      currentFlowTitle.value = data.flow_title || '';
      currentFlowDescription.value = data.flow_description || '';
      userInput.value = '';
      validationError.value = ''; // Clear validation error on success
      
      // Set initial input type and UI
      currentInputType.value = 'menu';
      updateInputUI('menu');
      
      fetchLogs();
    } else {
      const errorMsg = data.message || data.error || 'Failed to start session.';
      errorMessage.value = errorMsg;
      toast.error(errorMsg);
    }
  } catch (e) {
    console.error('Error:', e);
    const errorMsg = 'Network error. Please check your connection.';
    errorMessage.value = errorMsg;
    toast.error(errorMsg);
  } finally {
    loading.value = false;
  }
}

async function sendInput() {
  if (!userInput.value) return;
  loading.value = true;
  errorMessage.value = '';
  
  // Check if this might be an API call (processing step)
  const isProcessingStep = currentFlowTitle.value.toLowerCase().includes('processing') || 
                          userInput.value === '*';
  
  if (isProcessingStep) {
    apiCallInProgress.value = true;
    apiCallStatus.value = 'Calling marketplace API...';
    lastApiResult.value = null;
  }
  
  const csrfToken = getCsrfToken();
  console.log('CSRF Token for input:', csrfToken ? 'Present' : 'Missing');
  
  try {
    const res = await fetch(route('ussd.simulator.input', { ussd: props.ussd.id }), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ session_id: sessionId.value, input: userInput.value, environment: environment.value }),
    });
    
    console.log('Input response status:', res.status);
    
    if (!res.ok) {
      const errorText = await res.text();
      console.error('Input error response:', errorText);
      errorMessage.value = `Server error: ${res.status}`;
      return;
    }
    
    const data = await res.json();
    console.log('Input response data:', data);
    
    if (data.success) {
      menuText.value = data.message;
      currentFlowTitle.value = data.flow_title || '';
      currentFlowDescription.value = data.flow_description || '';
      userInput.value = '';
      
      // Check if this was an API call result
      if (isProcessingStep) {
        apiCallInProgress.value = false;
        // The API result will be shown in the logs
        fetchLogs();
      }
      
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
      if (isProcessingStep) {
        apiCallInProgress.value = false;
      }
    }
    
    if (!isProcessingStep) {
      fetchLogs();
    }
  } catch (e) {
    console.error('Error:', e);
    errorMessage.value = 'Network error.';
    if (isProcessingStep) {
      apiCallInProgress.value = false;
    }
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
      inputMaxLength.value = 20; // Increased to ensure 11 digits can be entered
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
    
    // Extract API call results from logs
    const apiLogs = logs.value.filter(log => 
      log.action_type === 'api_call_success' || log.action_type === 'api_call_error'
    );
    
    if (apiLogs.length > 0) {
      const latestApiLog = apiLogs[apiLogs.length - 1];
      if (latestApiLog.action_type === 'api_call_success') {
        try {
          const outputData = JSON.parse(latestApiLog.output_data || '{}');
          lastApiResult.value = {
            success: true,
            message: outputData.message || 'API call successful',
            data: outputData,
            simulated: false, // Both testing and production make real API calls
            environment: environment.value
          };
        } catch (e) {
          lastApiResult.value = {
            success: true,
            message: 'API call successful',
            data: {},
            simulated: false, // Both testing and production make real API calls
            environment: environment.value
          };
        }
      } else if (latestApiLog.action_type === 'api_call_error') {
        lastApiResult.value = {
          success: false,
          message: latestApiLog.error_message || 'API call failed',
          data: {},
          simulated: environment.value === 'simulation',
          environment: environment.value
        };
      }
    }
  } catch (e) {
    // ignore
  }
}

// Environment status
const environmentStatus = computed(() => {
  if (environment.value === 'production') {
    return {
      label: 'Production Mode',
      color: 'bg-green-100 text-green-800',
      description: 'Real USSD service - live environment with real API calls'
    };
  }
  // Default to testing
  return {
    label: 'Testing Mode',
    color: 'bg-yellow-100 text-yellow-800',
    description: 'Testing environment with real API calls to sandbox/test endpoints'
  };
});

// USSD Code display
const ussdCode = computed(() => {
  if (!props.ussd) return 'Not configured';
  
  if (environment.value === 'production') {
    return props.ussd.live_ussd_code || 'Not configured';
  }
  // Default to testing
  return props.ussd.testing_ussd_code || props.ussd.pattern || 'Not configured';
});

const resetSession = () => {
  sessionId.value = '';
  isSessionActive.value = false;
  sessionHistory.value = [];
  currentInput.value = '';
};

// Auto-scroll to bottom of chat
const scrollToBottom = () => {
  const chatContainer = document.getElementById('chat-container');
  if (chatContainer) {
    chatContainer.scrollTop = chatContainer.scrollHeight;
  }
};

// Watch for flash messages from backend (e.g., validation errors on page load)
watch(() => page.props.flash, (flash) => {
  if (flash?.error) {
    validationError.value = flash.error;
    toast.error(flash.error, { timeout: 8000 });
  } else if (flash?.success) {
    toast.success(flash.success);
  } else if (flash?.info) {
    toast.info(flash.info);
  }
}, { immediate: true, deep: true });

onMounted(() => {
  // Set environment based on USSD configuration (testing by default, production when live)
  if (props.ussd && props.ussd.environment) {
    environment.value = props.ussd.environment.name || 'testing';
  } else {
    environment.value = 'testing'; // Default to testing
  }

  // Check for flash messages on mount
  const flash = page.props.flash;
  if (flash?.error) {
    validationError.value = flash.error;
    toast.error(flash.error, { timeout: 8000 });
  }
});
</script> 