<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Configure USSD Flow</h2>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-500">Service: {{ ussd.name }} ({{ ussd.pattern }})</span>
          <Link 
            :href="route('ussd.simulator', ussd.id)" 
            class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded hover:bg-green-700 flex items-center gap-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            Test Simulation
          </Link>
          <button @click="debugCurrentState" class="px-3 py-1 text-xs bg-blue-100 text-blue-600 rounded hover:bg-blue-200">
            Debug State
          </button>
          <button @click="refreshPage" class="px-3 py-1 text-xs bg-gray-100 text-gray-600 rounded hover:bg-gray-200">
            Refresh Token
          </button>
        </div>
      </div>
    </template>

    <div class="py-8">
      <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Flows List -->
        <div class="col-span-1 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Flows</h3>
            <button @click="openAddFlowModal" class="px-2 py-1 rounded bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">+ Add Flow</button>
          </div>
          <ul>
            <li v-for="flow in flows" :key="flow.id" :class="[selectedFlow && selectedFlow.id === flow.id ? 'bg-indigo-50' : '', 'rounded p-2 mb-2 cursor-pointer hover:bg-indigo-100']" @click="openEditFlowModal(flow)">
              <div class="flex items-center justify-between">
                <span class="font-medium">{{ flow.name }}</span>
                <div class="flex items-center gap-2">
                  <span v-if="flow.is_root" class="text-xs text-green-600">Root</span>
                  <span v-if="selectedFlow && selectedFlow.id === flow.id && hasUnsavedChanges()" class="text-xs text-orange-600 font-semibold">*</span>
                </div>
              </div>
              <div class="text-xs text-gray-500 truncate">{{ flow.menu_text }}</div>
            </li>
          </ul>
        </div>

        <!-- Flow Editor -->
        <div class="col-span-3 bg-white rounded-xl shadow-sm border border-gray-200 p-6 min-h-[400px]">
          <div v-if="selectedFlow">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Edit Flow: {{ selectedFlow.name }}</h3>
              <button @click="openDeleteFlowModal(selectedFlow)" class="px-2 py-1 rounded bg-red-100 text-red-700 text-xs font-semibold hover:bg-red-200">Delete Flow</button>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Menu Text</label>
              <textarea v-model="selectedFlow.menu_text" rows="3" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Description</label>
              <input v-model="selectedFlow.description" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
            </div>
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Options</label>
              <div v-for="(option, idx) in selectedFlow.options" :key="option.id || idx" class="bg-gray-50 rounded p-3 mb-2 flex flex-col md:flex-row md:items-center gap-2 w-full">
                <input v-model="option.option_text" placeholder="Option Text" class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                <input v-model="option.option_value" placeholder="Value" class="w-20 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                <select v-model="option.action_type" class="w-32 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
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
                </select>
                <input v-if="option.action_type === 'message'" v-model="option.action_data.message" placeholder="Message" class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                
                <!-- Input Text Configuration -->
                <div v-if="option.action_type === 'input_text'" class="flex-1 space-y-2">
                  <input v-model="option.action_data.prompt" placeholder="Enter prompt (e.g., Enter your name)" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <input v-model="option.action_data.validation" placeholder="Validation regex (optional)" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <input v-model="option.action_data.error_message" placeholder="Error message" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                </div>

                <!-- Input Number Configuration -->
                <div v-if="option.action_type === 'input_number'" class="flex-1 space-y-2">
                  <input v-model="option.action_data.prompt" placeholder="Enter prompt (e.g., Enter amount)" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <div class="flex gap-2">
                    <input v-model="option.action_data.min" type="number" placeholder="Min value" class="w-1/2 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                    <input v-model="option.action_data.max" type="number" placeholder="Max value" class="w-1/2 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  </div>
                  <input v-model="option.action_data.error_message" placeholder="Error message" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                </div>

                <!-- Input Phone Configuration -->
                <div v-if="option.action_type === 'input_phone'" class="flex-1 space-y-2">
                  <input v-model="option.action_data.prompt" placeholder="Enter prompt (e.g., Enter phone number)" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <select v-model="option.action_data.country_code" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="+234">Nigeria (+234)</option>
                    <option value="+254">Kenya (+254)</option>
                    <option value="+256">Uganda (+256)</option>
                    <option value="+255">Tanzania (+255)</option>
                    <option value="+233">Ghana (+233)</option>
                    <option value="custom">Custom</option>
                  </select>
                  <input v-if="option.action_data.country_code === 'custom'" v-model="option.action_data.custom_country_code" placeholder="Custom country code" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <input v-model="option.action_data.error_message" placeholder="Error message" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                </div>

                <!-- Input Account Configuration -->
                <div v-if="option.action_type === 'input_account'" class="flex-1 space-y-2">
                  <input v-model="option.action_data.prompt" placeholder="Enter prompt (e.g., Enter account number)" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <select v-model="option.action_data.account_type" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="bank">Bank Account</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="custom">Custom</option>
                  </select>
                  <input v-model="option.action_data.length" type="number" placeholder="Expected length" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <input v-model="option.action_data.error_message" placeholder="Error message" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                </div>

                <!-- Input PIN Configuration -->
                <div v-if="option.action_type === 'input_pin'" class="flex-1 space-y-2">
                  <input v-model="option.action_data.prompt" placeholder="Enter prompt (e.g., Enter PIN)" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <input v-model="option.action_data.length" type="number" placeholder="PIN length (e.g., 4)" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <input v-model="option.action_data.error_message" placeholder="Error message" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                </div>

                <!-- Input Amount Configuration -->
                <div v-if="option.action_type === 'input_amount'" class="flex-1 space-y-2">
                  <input v-model="option.action_data.prompt" placeholder="Enter prompt (e.g., Enter amount)" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <div class="flex gap-2">
                    <input v-model="option.action_data.min_amount" type="number" placeholder="Min amount" class="w-1/2 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                    <input v-model="option.action_data.max_amount" type="number" placeholder="Max amount" class="w-1/2 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  </div>
                  <select v-model="option.action_data.currency" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="NGN">Naira (₦)</option>
                    <option value="KES">Kenya Shilling (KSh)</option>
                    <option value="UGX">Uganda Shilling (USh)</option>
                    <option value="TZS">Tanzania Shilling (TSh)</option>
                    <option value="GHS">Ghana Cedi (₵)</option>
                    <option value="USD">US Dollar ($)</option>
                  </select>
                  <input v-model="option.action_data.error_message" placeholder="Error message" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                </div>

                <!-- Input Selection Configuration -->
                <div v-if="option.action_type === 'input_selection'" class="flex-1 space-y-2">
                  <input v-model="option.action_data.prompt" placeholder="Enter prompt (e.g., Select option)" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <textarea v-model="option.action_data.options" placeholder="Enter options (one per line)" rows="3" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"></textarea>
                  <input v-model="option.action_data.error_message" placeholder="Error message" class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                </div>
                <select v-if="option.action_type === 'navigate'" v-model="option.next_flow_id" class="w-40 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                  <option value="">Select flow</option>
                  <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">{{ flow.name }}</option>
                </select>
                <button @click="removeOptionFromForm(idx)" :disabled="savingFlow" class="text-red-500 hover:text-red-700 ml-2 px-2 py-1 text-sm whitespace-nowrap disabled:opacity-50 disabled:cursor-not-allowed">Remove</button>
              </div>
              <button @click="addOptionToForm" class="mt-2 px-3 py-1 rounded bg-indigo-100 text-indigo-700 text-xs font-semibold hover:bg-indigo-200">+ Add Option</button>
            </div>
            <div class="flex justify-end gap-2">
              <button 
                @click="cancelFlowEdit" 
                :disabled="savingFlow"
                class="px-4 py-2 rounded bg-gray-300 text-gray-700 font-semibold hover:bg-gray-400 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Cancel
              </button>
              <button 
                @click="saveFlow" 
                :disabled="savingFlow"
                :class="[
                  hasUnsavedChanges() ? 'bg-orange-600 hover:bg-orange-700' : 'bg-indigo-600 hover:bg-indigo-700',
                  'px-4 py-2 rounded text-white font-semibold disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2'
                ]"
              >
                <svg v-if="savingFlow" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ savingFlow ? 'Saving...' : (hasUnsavedChanges() ? 'Save Changes*' : 'Save Flow') }}
              </button>
            </div>
          </div>
          <div v-else class="text-gray-400 flex items-center justify-center h-full min-h-[300px]">
            <span>Select a flow to edit or add a new one.</span>
          </div>
        </div>
      </div>

      <!-- Add Flow Modal -->
      <FormModal
        :show="showAddFlowModal"
        title="Add New Flow"
        confirm-text="Add Flow"
        cancel-text="Cancel"
        type="primary"
        :loading="savingFlow"
        @confirm="saveFlow"
        @cancel="closeAddFlowModal"
      >
        <div class="space-y-4">
          <div v-if="flowErrors.general" class="bg-red-50 border border-red-200 rounded-md p-3">
            <p class="text-sm text-red-600">{{ flowErrors.general }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Flow Name</label>
            <input 
              v-model="flowForm.name" 
              type="text"
              :class="[flowErrors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter flow name"
              @input="validateFlowFormRealTime"
              @blur="validateFlowFormRealTime"
            />
            <p v-if="flowErrors.name" class="mt-1 text-sm text-red-600">{{ flowErrors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Menu Text</label>
            <textarea 
              v-model="flowForm.menu_text" 
              rows="3"
              :class="[flowErrors.menu_text ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter the menu text that users will see"
              @input="validateFlowFormRealTime"
              @blur="validateFlowFormRealTime"
            ></textarea>
            <p v-if="flowErrors.menu_text" class="mt-1 text-sm text-red-600">{{ flowErrors.menu_text }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <input 
              v-model="flowForm.description" 
              type="text"
              :class="[flowErrors.description ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter flow description"
              @input="validateFlowFormRealTime"
              @blur="validateFlowFormRealTime"
            />
            <p v-if="flowErrors.description" class="mt-1 text-sm text-red-600">{{ flowErrors.description }}</p>
          </div>
        </div>
      </FormModal>

      <!-- Edit Flow Modal -->
      <FormModal
        :show="showEditFlowModal"
        title="Edit Flow"
        confirm-text="Save Changes"
        cancel-text="Cancel"
        type="primary"
        :loading="savingFlow"
        @confirm="saveFlow"
        @cancel="closeEditFlowModal"
      >
        <div class="space-y-4">
          <div v-if="flowErrors.general" class="bg-red-50 border border-red-200 rounded-md p-3">
            <p class="text-sm text-red-600">{{ flowErrors.general }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Flow Name</label>
            <input 
              v-model="flowForm.name" 
              type="text"
              :class="[flowErrors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter flow name"
            />
            <p v-if="flowErrors.name" class="mt-1 text-sm text-red-600">{{ flowErrors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Menu Text</label>
            <textarea 
              v-model="flowForm.menu_text" 
              rows="3"
              :class="[flowErrors.menu_text ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter the menu text that users will see"
            ></textarea>
            <p v-if="flowErrors.menu_text" class="mt-1 text-sm text-red-600">{{ flowErrors.menu_text }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <input 
              v-model="flowForm.description" 
              type="text"
              :class="[flowErrors.description ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter flow description"
            />
            <p v-if="flowErrors.description" class="mt-1 text-sm text-red-600">{{ flowErrors.description }}</p>
          </div>
        </div>
      </FormModal>

      <!-- Delete Flow Modal -->
      <ConfirmationModal
        :show="showDeleteFlowModal"
        title="Delete Flow"
        message="Are you sure you want to delete this flow? This cannot be undone."
        confirm-text="Delete"
        cancel-text="Cancel"
        type="danger"
        :loading="deletingFlow"
        @confirm="deleteFlow"
        @cancel="showDeleteFlowModal = false"
      />

      <!-- Add Option Modal -->
      <FormModal
        :show="showAddOptionModal"
        title="Add New Option"
        confirm-text="Add Option"
        cancel-text="Cancel"
        type="primary"
        :loading="savingOption"
        @confirm="saveOption"
        @cancel="closeAddOptionModal"
      >
        <div class="space-y-4">
          <div v-if="optionErrors.general" class="bg-red-50 border border-red-200 rounded-md p-3">
            <p class="text-sm text-red-600">{{ optionErrors.general }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Option Text</label>
            <input 
              v-model="optionForm.option_text" 
              type="text"
              :class="[optionErrors.option_text ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter option text"
              @input="validateOptionFormRealTime"
            />
            <p v-if="optionErrors.option_text" class="mt-1 text-sm text-red-600">{{ optionErrors.option_text }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Option Value</label>
            <input 
              v-model="optionForm.option_value" 
              type="text"
              :class="[optionErrors.option_value ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter option value"
              @input="validateOptionFormRealTime"
            />
            <p v-if="optionErrors.option_value" class="mt-1 text-sm text-red-600">{{ optionErrors.option_value }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Action Type</label>
            <select 
              v-model="optionForm.action_type" 
              :class="[optionErrors.action_type ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded shadow-sm sm:text-sm']"
              @change="validateOptionFormRealTime"
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
            </select>
            <p v-if="optionErrors.action_type" class="mt-1 text-sm text-red-600">{{ optionErrors.action_type }}</p>
          </div>
          <div v-if="optionForm.action_type === 'message'">
            <label class="block text-sm font-medium text-gray-700">Message</label>
            <input 
              v-model="optionForm.action_data.message" 
              type="text"
              :class="[optionErrors.message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter message"
              @input="validateOptionFormRealTime"
            />
            <p v-if="optionErrors.message" class="mt-1 text-sm text-red-600">{{ optionErrors.message }}</p>
          </div>
          <div v-if="optionForm.action_type === 'input_text'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="optionForm.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter your name)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Validation Regex (Optional)</label>
              <input 
                v-model="optionForm.action_data.validation" 
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                placeholder="e.g., ^[a-zA-Z ]+$"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="optionForm.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="optionForm.action_type === 'input_number'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="optionForm.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter amount)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Min Value</label>
                <input 
                  v-model="optionForm.action_data.min" 
                  type="number"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                  placeholder="Minimum value"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Max Value</label>
                <input 
                  v-model="optionForm.action_data.max" 
                  type="number"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                  placeholder="Maximum value"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="optionForm.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="optionForm.action_type === 'input_phone'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="optionForm.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter phone number)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Country Code</label>
              <select 
                v-model="optionForm.action_data.country_code"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="+234">Nigeria (+234)</option>
                <option value="+254">Kenya (+254)</option>
                <option value="+256">Uganda (+256)</option>
                <option value="+255">Tanzania (+255)</option>
                <option value="+233">Ghana (+233)</option>
                <option value="custom">Custom</option>
              </select>
            </div>
            <div v-if="optionForm.action_data.country_code === 'custom'">
              <label class="block text-sm font-medium text-gray-700">Custom Country Code</label>
              <input 
                v-model="optionForm.action_data.custom_country_code" 
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                placeholder="e.g., +1"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="optionForm.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="optionForm.action_type === 'input_account'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="optionForm.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter account number)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Account Type</label>
              <select 
                v-model="optionForm.action_data.account_type"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="bank">Bank Account</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="custom">Custom</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Expected Length</label>
              <input 
                v-model="optionForm.action_data.length" 
                type="number"
                :class="[optionErrors.length ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="e.g., 10"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.length" class="mt-1 text-sm text-red-600">{{ optionErrors.length }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="optionForm.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="optionForm.action_type === 'input_pin'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="optionForm.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter PIN)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">PIN Length</label>
              <input 
                v-model="optionForm.action_data.length" 
                type="number"
                :class="[optionErrors.length ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="e.g., 4"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.length" class="mt-1 text-sm text-red-600">{{ optionErrors.length }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="optionForm.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="optionForm.action_type === 'input_amount'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="optionForm.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter amount)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Min Amount</label>
                <input 
                  v-model="optionForm.action_data.min_amount" 
                  type="number"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                  placeholder="Minimum amount"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Max Amount</label>
                <input 
                  v-model="optionForm.action_data.max_amount" 
                  type="number"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                  placeholder="Maximum amount"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Currency</label>
              <select 
                v-model="optionForm.action_data.currency"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="NGN">Naira (₦)</option>
                <option value="KES">Kenya Shilling (KSh)</option>
                <option value="UGX">Uganda Shilling (USh)</option>
                <option value="TZS">Tanzania Shilling (TSh)</option>
                <option value="GHS">Ghana Cedi (₵)</option>
                <option value="USD">US Dollar ($)</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="optionForm.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="optionForm.action_type === 'input_selection'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="optionForm.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Select option)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Options</label>
              <textarea 
                v-model="optionForm.action_data.options" 
                rows="3"
                :class="[optionErrors.options ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter options (one per line)"
                @input="validateOptionFormRealTime"
              ></textarea>
              <p v-if="optionErrors.options" class="mt-1 text-sm text-red-600">{{ optionErrors.options }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="optionForm.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
        </div>
      </FormModal>

      <!-- Edit Option Modal -->
      <FormModal
        :show="showEditOptionModal"
        title="Edit Option"
        confirm-text="Save"
        cancel-text="Cancel"
        type="primary"
        :loading="savingOption"
        @confirm="saveOption"
        @cancel="closeEditOptionModal"
      >
        <div class="space-y-4">
          <div v-if="optionErrors.general" class="bg-red-50 border border-red-200 rounded-md p-3">
            <p class="text-sm text-red-600">{{ optionErrors.general }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Option Text</label>
            <input 
              v-model="selectedOption.option_text" 
              type="text"
              :class="[optionErrors.option_text ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              @input="validateOptionFormRealTime"
              @blur="validateOptionFormRealTime"
            />
            <p v-if="optionErrors.option_text" class="mt-1 text-sm text-red-600">{{ optionErrors.option_text }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Option Value</label>
            <input 
              v-model="selectedOption.option_value" 
              type="text"
              :class="[optionErrors.option_value ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              @input="validateOptionFormRealTime"
              @blur="validateOptionFormRealTime"
            />
            <p v-if="optionErrors.option_value" class="mt-1 text-sm text-red-600">{{ optionErrors.option_value }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Action Type</label>
            <select 
              v-model="selectedOption.action_type" 
              :class="[optionErrors.action_type ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded shadow-sm sm:text-sm']"
              @change="validateOptionFormRealTime"
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
            </select>
            <p v-if="optionErrors.action_type" class="mt-1 text-sm text-red-600">{{ optionErrors.action_type }}</p>
          </div>
          <div v-if="selectedOption.action_type === 'message'">
            <label class="block text-sm font-medium text-gray-700">Message</label>
            <input 
              v-model="selectedOption.action_data.message" 
              type="text"
              :class="[optionErrors.message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              @input="validateOptionFormRealTime"
              @blur="validateOptionFormRealTime"
            />
            <p v-if="optionErrors.message" class="mt-1 text-sm text-red-600">{{ optionErrors.message }}</p>
          </div>
          <div v-if="selectedOption.action_type === 'input_text'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="selectedOption.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter your name)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Validation Regex (Optional)</label>
              <input 
                v-model="selectedOption.action_data.validation" 
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                placeholder="e.g., ^[a-zA-Z ]+$"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="selectedOption.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="selectedOption.action_type === 'input_number'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="selectedOption.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter amount)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Min Value</label>
                <input 
                  v-model="selectedOption.action_data.min" 
                  type="number"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                  placeholder="Minimum value"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Max Value</label>
                <input 
                  v-model="selectedOption.action_data.max" 
                  type="number"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                  placeholder="Maximum value"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="selectedOption.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="selectedOption.action_type === 'input_phone'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="selectedOption.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter phone number)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Country Code</label>
              <select 
                v-model="selectedOption.action_data.country_code"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="+234">Nigeria (+234)</option>
                <option value="+254">Kenya (+254)</option>
                <option value="+256">Uganda (+256)</option>
                <option value="+255">Tanzania (+255)</option>
                <option value="+233">Ghana (+233)</option>
                <option value="custom">Custom</option>
              </select>
            </div>
            <div v-if="selectedOption.action_data.country_code === 'custom'">
              <label class="block text-sm font-medium text-gray-700">Custom Country Code</label>
              <input 
                v-model="selectedOption.action_data.custom_country_code" 
                type="text"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                placeholder="e.g., +1"
              />
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="selectedOption.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="selectedOption.action_type === 'input_account'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="selectedOption.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter account number)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Account Type</label>
              <select 
                v-model="selectedOption.action_data.account_type"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="bank">Bank Account</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="custom">Custom</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Expected Length</label>
              <input 
                v-model="selectedOption.action_data.length" 
                type="number"
                :class="[optionErrors.length ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="e.g., 10"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.length" class="mt-1 text-sm text-red-600">{{ optionErrors.length }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="selectedOption.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="selectedOption.action_type === 'input_pin'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="selectedOption.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter PIN)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">PIN Length</label>
              <input 
                v-model="selectedOption.action_data.length" 
                type="number"
                :class="[optionErrors.length ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="e.g., 4"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.length" class="mt-1 text-sm text-red-600">{{ optionErrors.length }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="selectedOption.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="selectedOption.action_type === 'input_amount'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="selectedOption.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Enter amount)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div class="grid grid-cols-2 gap-4">
              <div>
                <label class="block text-sm font-medium text-gray-700">Min Amount</label>
                <input 
                  v-model="selectedOption.action_data.min_amount" 
                  type="number"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                  placeholder="Minimum amount"
                />
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700">Max Amount</label>
                <input 
                  v-model="selectedOption.action_data.max_amount" 
                  type="number"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                  placeholder="Maximum amount"
                />
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Currency</label>
              <select 
                v-model="selectedOption.action_data.currency"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
              >
                <option value="NGN">Naira (₦)</option>
                <option value="KES">Kenya Shilling (KSh)</option>
                <option value="UGX">Uganda Shilling (USh)</option>
                <option value="TZS">Tanzania Shilling (TSh)</option>
                <option value="GHS">Ghana Cedi (₵)</option>
                <option value="USD">US Dollar ($)</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="selectedOption.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
          <div v-if="selectedOption.action_type === 'input_selection'" class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700">Prompt</label>
              <input 
                v-model="selectedOption.action_data.prompt" 
                type="text"
                :class="[optionErrors.prompt ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter prompt (e.g., Select option)"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.prompt" class="mt-1 text-sm text-red-600">{{ optionErrors.prompt }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Options</label>
              <textarea 
                v-model="selectedOption.action_data.options" 
                rows="3"
                :class="[optionErrors.options ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Enter options (one per line)"
                @input="validateOptionFormRealTime"
              ></textarea>
              <p v-if="optionErrors.options" class="mt-1 text-sm text-red-600">{{ optionErrors.options }}</p>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700">Error Message</label>
              <input 
                v-model="selectedOption.action_data.error_message" 
                type="text"
                :class="[optionErrors.error_message ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
                placeholder="Error message to show if validation fails"
                @input="validateOptionFormRealTime"
              />
              <p v-if="optionErrors.error_message" class="mt-1 text-sm text-red-600">{{ optionErrors.error_message }}</p>
            </div>
          </div>
        </div>
      </FormModal>

      <!-- Delete Option Modal -->
      <ConfirmationModal
        :show="showDeleteOptionModal"
        title="Delete Option"
        message="Are you sure you want to delete this option? This cannot be undone."
        confirm-text="Delete"
        cancel-text="Cancel"
        type="danger"
        :loading="deletingOption"
        @confirm="deleteOption"
        @cancel="showDeleteOptionModal = false"
      />
    </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import FormModal from '@/Components/FormModal.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import DangerButton from '@/Components/DangerButton.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import InputError from '@/Components/InputError.vue'

const props = defineProps({
    ussd: Object,
})

const flows = ref(props.ussd.flows || [])
const selectedFlow = ref(null)
const showAddFlowModal = ref(false)
const showEditFlowModal = ref(false)
const showDeleteFlowModal = ref(false)
const showAddOptionModal = ref(false)
const showEditOptionModal = ref(false)
const showDeleteOptionModal = ref(false)
const selectedOption = ref(null)
const loading = ref(false)
const savingFlow = ref(false)
const deletingFlow = ref(false)
const savingOption = ref(false)
const deletingOption = ref(false)

// Form data
const flowForm = useForm({
    name: '',
    menu_text: '',
    description: '',
    options: []
})

const optionForm = useForm({
    option_text: '',
    option_value: '',
    action_type: 'navigate',
    action_data: null, // Will be set to {message: ''} when action_type is 'message'
    next_flow_id: null
})

// Validation errors
const flowErrors = ref({})
const optionErrors = ref({})

// Real-time validation watchers
const validateFlowFormRealTime = () => {
    // Determine if we're editing or creating
    const isEditing = selectedFlow.value && selectedFlow.value.id && !showAddFlowModal.value
    
    if (isEditing) {
        // Validate selectedFlow data for editing
        const errors = {}
        
        // Validate name
        if (!selectedFlow.value.name.trim()) {
            errors.name = 'Flow name is required'
        } else if (selectedFlow.value.name.length < 2) {
            errors.name = 'Flow name must be at least 2 characters'
        } else if (selectedFlow.value.name.length > 255) {
            errors.name = 'Flow name cannot exceed 255 characters'
        }
        
        // Validate menu_text
        if (!selectedFlow.value.menu_text.trim()) {
            errors.menu_text = 'Menu text is required'
        } else if (selectedFlow.value.menu_text.length < 5) {
            errors.menu_text = 'Menu text must be at least 5 characters'
        } else if (selectedFlow.value.menu_text.length > 1000) {
            errors.menu_text = 'Menu text cannot exceed 1000 characters'
        }
        
        // Validate description
        if (selectedFlow.value.description && selectedFlow.value.description.length > 500) {
            errors.description = 'Description cannot exceed 500 characters'
        }
        
        flowErrors.value = errors
    } else {
        // Validate flowForm data for creating
        const errors = {}
        
        // Validate name
        if (!flowForm.name.trim()) {
            errors.name = 'Flow name is required'
        } else if (flowForm.name.length < 2) {
            errors.name = 'Flow name must be at least 2 characters'
        } else if (flowForm.name.length > 255) {
            errors.name = 'Flow name cannot exceed 255 characters'
        }
        
        // Validate menu_text
        if (!flowForm.menu_text.trim()) {
            errors.menu_text = 'Menu text is required'
        } else if (flowForm.menu_text.length < 5) {
            errors.menu_text = 'Menu text must be at least 5 characters'
        } else if (flowForm.menu_text.length > 1000) {
            errors.menu_text = 'Menu text cannot exceed 1000 characters'
        }
        
        // Validate description
        if (flowForm.description && flowForm.description.length > 500) {
            errors.description = 'Description cannot exceed 500 characters'
        }
        
        flowErrors.value = errors
    }
}

const validateOptionFormRealTime = () => {
    const errors = {}
    
    // Validate option_text
    if (!optionForm.option_text.trim()) {
        errors.option_text = 'Option text is required'
    } else if (optionForm.option_text.length > 255) {
        errors.option_text = 'Option text cannot exceed 255 characters'
    }
    
    // Validate option_value
    if (!optionForm.option_value.trim()) {
        errors.option_value = 'Option value is required'
    } else if (optionForm.option_value.length > 50) {
        errors.option_value = 'Option value cannot exceed 50 characters'
    }
    
    // Validate action_type
    if (!['navigate', 'message', 'end_session', 'input_text', 'input_number', 'input_phone', 'input_account', 'input_pin', 'input_amount', 'input_selection'].includes(optionForm.action_type)) {
        errors.action_type = 'Invalid action type'
    }
    
    // Validate message for message action type
    if (optionForm.action_type === 'message') {
        if (!optionForm.action_data || !optionForm.action_data.message || !optionForm.action_data.message.trim()) {
            errors.message = 'Message is required when action type is message'
        } else if (optionForm.action_data.message.length > 500) {
            errors.message = 'Message cannot exceed 500 characters'
        }
    }
    
    optionErrors.value = errors
}

// Computed properties
const availableFlows = computed(() => {
    return flows.value.filter(flow => flow.id !== selectedFlow.value?.id)
})

// Methods
const openAddFlowModal = () => {
    flowForm.reset()
    flowForm.options = []
    showAddFlowModal.value = true
}

const openEditFlowModal = (flow) => {
    selectedFlow.value = flow
    // Store original values for cancel functionality
    selectedFlow.value.originalMenuText = flow.menu_text
    selectedFlow.value.originalDescription = flow.description || ''
    selectedFlow.value.originalOptions = flow.options ? JSON.parse(JSON.stringify(flow.options)) : []
    
    flowForm.name = flow.name
    flowForm.menu_text = flow.menu_text
    flowForm.description = flow.description || ''
    flowForm.options = flow.options ? [...flow.options] : []
    showEditFlowModal.value = true
}

const openDeleteFlowModal = (flow) => {
    selectedFlow.value = flow
    showDeleteFlowModal.value = true
}

const openAddOptionModal = (flow) => {
    selectedFlow.value = flow
    optionForm.reset()
    optionForm.action_type = 'navigate'
    optionForm.action_data = null
    optionForm.next_flow_id = null
    showAddOptionModal.value = true
}

const openEditOptionModal = (option) => {
    selectedOption.value = option
    optionForm.option_text = option.option_text
    optionForm.option_value = option.option_value
    optionForm.action_type = option.action_type
    optionForm.action_data = option.action_data || null
    optionForm.next_flow_id = option.next_flow_id
    showEditOptionModal.value = true
}

const openDeleteOptionModal = (option) => {
    selectedOption.value = option
    showDeleteOptionModal.value = true
}

const removeOptionFromForm = (index) => {
    // Remove option from the selected flow's options array
    if (selectedFlow.value && selectedFlow.value.options) {
        selectedFlow.value.options.splice(index, 1)
    }
}

const addOptionToForm = () => {
    // Add a new option to the selected flow
    if (selectedFlow.value) {
        if (!selectedFlow.value.options) {
            selectedFlow.value.options = []
        }
        
        const newOption = {
            option_text: '',
            option_value: '',
            action_type: 'navigate',
            action_data: null, // Will be set by the watcher based on action_type
            next_flow_id: null
        }
        
        selectedFlow.value.options.push(newOption)
    }
}

const getCSRFToken = () => {
    const token = document.querySelector('meta[name="csrf-token"]')
    if (!token) {
        console.error('CSRF token meta tag not found')
        throw new Error('CSRF token not found. Please refresh the page.')
    }
    const tokenValue = token.getAttribute('content')
    if (!tokenValue) {
        console.error('CSRF token value is empty')
        throw new Error('CSRF token is empty. Please refresh the page.')
    }
    console.log('CSRF Token found:', tokenValue.substring(0, 10) + '...')
    return tokenValue
}

const saveFlow = async () => {

    // Determine if we're editing an existing flow or creating a new one
    const isEditing = selectedFlow.value && selectedFlow.value.id && !showAddFlowModal.value
    
    console.log('Is editing:', isEditing)
    
    // Validate form before submission
    if (!validateFlowForm()) {
        console.log('Frontend validation failed:', flowErrors.value)
        return
    }
    
    console.log('Frontend validation passed')
    savingFlow.value = true
    try {
        // Get CSRF token
        const csrfToken = getCSRFToken()
        
        if (isEditing) {
            // Update existing flow - use the selectedFlow data
            const updateData = {
                name: selectedFlow.value.name,
                menu_text: selectedFlow.value.menu_text,
                description: selectedFlow.value.description || '',
                options: selectedFlow.value.options || []
            }
            
            console.log('Updating flow with data:', updateData)
            console.log('Flow ID:', selectedFlow.value.id)
            console.log('CSRF Token for update:', csrfToken.substring(0, 10) + '...')
            
            const response = await fetch(`/ussd/${props.ussd.id}/flows/${selectedFlow.value.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(updateData)
            })
            
            console.log('Update response status:', response.status)
            
            if (!response.ok) {
                const errorText = await response.text()
                console.error('Server error response:', errorText)
                
                if (response.status === 419) {
                    throw new Error('CSRF token mismatch. Please refresh the page and try again.')
                }
                
                throw new Error(`Server error: ${response.status} - ${errorText}`)
            }
            
            const result = await response.json()
            console.log('Update result:', result)
            
            if (result.success) {
                const index = flows.value.findIndex(f => f.id === selectedFlow.value.id)
                flows.value[index] = result.flow
                // Don't close the modal if we're in inline editing mode
                if (showEditFlowModal.value) {
                    showEditFlowModal.value = false
                }
                flowErrors.value = {}
                console.log('Flow updated successfully!')
            } else {
                // Handle backend validation errors
                if (result.errors) {
                    // Clear any existing errors first
                    flowErrors.value = {}
                    
                    // Map backend errors to frontend error format
                    Object.keys(result.errors).forEach(key => {
                        if (Array.isArray(result.errors[key])) {
                            flowErrors.value[key] = result.errors[key][0] // Take first error message
                        } else {
                            flowErrors.value[key] = result.errors[key]
                        }
                    })
                }
            }
        } else {
            // Create new flow
            console.log('Creating new flow with data:', flowForm.data())
            console.log('USSD ID:', props.ussd.id)
            
            // Prepare the data properly
            const formData = {
                name: flowForm.name,
                menu_text: flowForm.menu_text,
                description: flowForm.description || ''
            }
            
            console.log('Sending form data:', formData)
            console.log('CSRF Token for create:', csrfToken.substring(0, 10) + '...')
            
            const response = await fetch(`/ussd/${props.ussd.id}/flows`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            
            console.log('Create response status:', response.status)
            console.log('Response headers:', response.headers)
            
            if (!response.ok) {
                const errorText = await response.text()
                console.error('Server error response:', errorText)
                
                if (response.status === 419) {
                    throw new Error('CSRF token mismatch. Please refresh the page and try again.')
                }
                
                throw new Error(`Server error: ${response.status} - ${errorText}`)
            }
            
            const result = await response.json()
            console.log('Response result:', result)
            
            if (result.success) {
                flows.value.push(result.flow)
                showAddFlowModal.value = false
                flowErrors.value = {}
                console.log('Flow created successfully!')
            } else {
                console.log('Backend returned errors:', result.errors)
                // Handle backend validation errors
                if (result.errors) {
                    // Clear any existing errors first
                    flowErrors.value = {}
                    
                    // Map backend errors to frontend error format
                    Object.keys(result.errors).forEach(key => {
                        if (Array.isArray(result.errors[key])) {
                            flowErrors.value[key] = result.errors[key][0] // Take first error message
                        } else {
                            flowErrors.value[key] = result.errors[key]
                        }
                    })
                    console.log('Mapped errors:', flowErrors.value)
                }
            }
        }
    } catch (error) {
        console.error('Error saving flow:', error)
        
        // Check if it's a JSON parsing error
        if (error.message.includes('Unexpected token')) {
            flowErrors.value.general = 'Server returned an error page instead of JSON. Please check if you are logged in and try again.'
        } else if (error.message.includes('CSRF')) {
            flowErrors.value.general = error.message
        } else {
            flowErrors.value.general = 'An error occurred while saving the flow: ' + error.message
        }
    } finally {
        savingFlow.value = false
    }
}

const deleteFlow = async () => {
    deletingFlow.value = true
    try {
        const csrfToken = getCSRFToken()
        
        const response = await fetch(`/ussd/${props.ussd.id}/flows/${selectedFlow.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        
        if (!response.ok) {
            const errorText = await response.text()
            if (response.status === 419) {
                throw new Error('CSRF token mismatch. Please refresh the page and try again.')
            }
            throw new Error(`Server error: ${response.status} - ${errorText}`)
        }
        
        const result = await response.json()
        if (result.success) {
            flows.value = flows.value.filter(f => f.id !== selectedFlow.value.id)
            showDeleteFlowModal.value = false
        }
    } catch (error) {
        console.error('Error deleting flow:', error)
        flowErrors.value.general = error.message
    } finally {
        deletingFlow.value = false
    }
}

const saveOption = async () => {
    // Validate form before submission
    if (!validateOptionForm()) {
        return
    }
    
    savingOption.value = true
    try {
        const csrfToken = getCSRFToken()
        
        if (showEditOptionModal.value) {
            // Update existing option
            const response = await fetch(`/ussd/${props.ussd.id}/flows/${selectedFlow.value.id}/options/${selectedOption.value.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(optionForm.data())
            })
            
            if (!response.ok) {
                const errorText = await response.text()
                if (response.status === 419) {
                    throw new Error('CSRF token mismatch. Please refresh the page and try again.')
                }
                throw new Error(`Server error: ${response.status} - ${errorText}`)
            }
            
            const result = await response.json()
            if (result.success) {
                const flowIndex = flows.value.findIndex(f => f.id === selectedFlow.value.id)
                const optionIndex = flows.value[flowIndex].options.findIndex(o => o.id === selectedOption.value.id)
                flows.value[flowIndex].options[optionIndex] = result.option
                showEditOptionModal.value = false
                optionErrors.value = {}
            } else {
                // Handle backend validation errors
                if (result.errors) {
                    optionErrors.value = result.errors
                }
            }
        } else {
            // Create new option
            const response = await fetch(`/ussd/${props.ussd.id}/flows/${selectedFlow.value.id}/options`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(optionForm.data())
            })
            
            if (!response.ok) {
                const errorText = await response.text()
                if (response.status === 419) {
                    throw new Error('CSRF token mismatch. Please refresh the page and try again.')
                }
                throw new Error(`Server error: ${response.status} - ${errorText}`)
            }
            
            const result = await response.json()
            if (result.success) {
                const flowIndex = flows.value.findIndex(f => f.id === selectedFlow.value.id)
                flows.value[flowIndex].options.push(result.option)
                showAddOptionModal.value = false
                optionErrors.value = {}
            } else {
                // Handle backend validation errors
                if (result.errors) {
                    optionErrors.value = result.errors
                }
            }
        }
    } catch (error) {
        console.error('Error saving option:', error)
        if (error.message.includes('CSRF')) {
            optionErrors.value.general = error.message
        } else {
            optionErrors.value.general = 'An error occurred while saving the option: ' + error.message
        }
    } finally {
        savingOption.value = false
    }
}

const deleteOption = async () => {
    deletingOption.value = true
    try {
        const csrfToken = getCSRFToken()
        
        const response = await fetch(`/ussd/${props.ussd.id}/flows/${selectedFlow.value.id}/options/${selectedOption.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        })
        
        if (!response.ok) {
            const errorText = await response.text()
            if (response.status === 419) {
                throw new Error('CSRF token mismatch. Please refresh the page and try again.')
            }
            throw new Error(`Server error: ${response.status} - ${errorText}`)
        }
        
        const result = await response.json()
        if (result.success) {
            const flowIndex = flows.value.findIndex(f => f.id === selectedFlow.value.id)
            flows.value[flowIndex].options = flows.value[flowIndex].options.filter(o => o.id !== selectedOption.value.id)
            showDeleteOptionModal.value = false
        }
    } catch (error) {
        console.error('Error deleting option:', error)
        optionErrors.value.general = error.message
    } finally {
        deletingOption.value = false
    }
}

const closeAddFlowModal = () => {
    flowForm.reset()
    flowErrors.value = {}
    showAddFlowModal.value = false
}

const closeEditFlowModal = () => {
    // Check if there are unsaved changes
    if (selectedFlow.value && hasUnsavedChanges()) {
      
        if (confirm('You have unsaved changes. Are you sure you want to close without saving?')) {
            // Reset to original values
            cancelFlowEdit()
        }
        return
    }
  
    flowErrors.value = {}
    showEditFlowModal.value = false
}

const closeAddOptionModal = () => {
    optionForm.reset()
    optionErrors.value = {}
    showAddOptionModal.value = false
}

const closeEditOptionModal = () => {
    selectedOption.value = null
    optionErrors.value = {}
    showEditOptionModal.value = false
}

const validateFlowForm = () => {
    console.log('validateFlowForm called')
    
    // Determine if we're editing or creating
    const isEditing = selectedFlow.value && selectedFlow.value.id && !showAddFlowModal.value
    
    if (isEditing) {
        // Validate selectedFlow data for editing
        console.log('Validating selectedFlow:', {
            name: selectedFlow.value.name,
            menu_text: selectedFlow.value.menu_text,
            description: selectedFlow.value.description
        })
        
        flowErrors.value = {}
        
        if (!selectedFlow.value.name.trim()) {
            flowErrors.value.name = 'Flow name is required'
        } else if (selectedFlow.value.name.length < 2) {
            flowErrors.value.name = 'Flow name must be at least 2 characters'
        } else if (selectedFlow.value.name.length > 255) {
            flowErrors.value.name = 'Flow name cannot exceed 255 characters'
        }
        
        if (!selectedFlow.value.menu_text.trim()) {
            flowErrors.value.menu_text = 'Menu text is required'
        } else if (selectedFlow.value.menu_text.length < 5) {
            flowErrors.value.menu_text = 'Menu text must be at least 5 characters'
        } else if (selectedFlow.value.menu_text.length > 1000) {
            flowErrors.value.menu_text = 'Menu text cannot exceed 1000 characters'
        }
        
        if (selectedFlow.value.description && selectedFlow.value.description.length > 500) {
            flowErrors.value.description = 'Description cannot exceed 500 characters'
        }
    } else {
        
        flowErrors.value = {}
        
        if (!flowForm.name.trim()) {
            flowErrors.value.name = 'Flow name is required'
        } else if (flowForm.name.length < 2) {
            flowErrors.value.name = 'Flow name must be at least 2 characters'
        } else if (flowForm.name.length > 255) {
            flowErrors.value.name = 'Flow name cannot exceed 255 characters'
        }
        
        if (!flowForm.menu_text.trim()) {
            flowErrors.value.menu_text = 'Menu text is required'
        } else if (flowForm.menu_text.length < 5) {
            flowErrors.value.menu_text = 'Menu text must be at least 5 characters'
        } else if (flowForm.menu_text.length > 1000) {
            flowErrors.value.menu_text = 'Menu text cannot exceed 1000 characters'
        }
        
        if (flowForm.description && flowForm.description.length > 500) {
            flowErrors.value.description = 'Description cannot exceed 500 characters'
        }
    }
    
    console.log('Validation result:', flowErrors.value)
    console.log('Is valid:', Object.keys(flowErrors.value).length === 0)
    
    return Object.keys(flowErrors.value).length === 0
}

const validateOptionForm = () => {
    optionErrors.value = {}
    
    if (!optionForm.option_text.trim()) {
        optionErrors.value.option_text = 'Option text is required'
    } else if (optionForm.option_text.length > 255) {
        optionErrors.value.option_text = 'Option text cannot exceed 255 characters'
    }
    
    if (!optionForm.option_value.trim()) {
        optionErrors.value.option_value = 'Option value is required'
    } else if (optionForm.option_value.length > 50) {
        optionErrors.value.option_value = 'Option value cannot exceed 50 characters'
    }
    
    if (!['navigate', 'message', 'end_session', 'input_text', 'input_number', 'input_phone', 'input_account', 'input_pin', 'input_amount', 'input_selection'].includes(optionForm.action_type)) {
        optionErrors.value.action_type = 'Invalid action type'
    }
    
    // Validate input-specific fields
    if (optionForm.action_type === 'message') {
        if (!optionForm.action_data || !optionForm.action_data.message || !optionForm.action_data.message.trim()) {
            optionErrors.value.message = 'Message is required when action type is message'
        } else if (optionForm.action_data.message.length > 500) {
            optionErrors.value.message = 'Message cannot exceed 500 characters'
        }
    }
    
    if (optionForm.action_type.startsWith('input_')) {
        if (!optionForm.action_data || !optionForm.action_data.prompt || !optionForm.action_data.prompt.trim()) {
            optionErrors.value.prompt = 'Prompt is required for input types'
        } else if (optionForm.action_data.prompt.length > 200) {
            optionErrors.value.prompt = 'Prompt cannot exceed 200 characters'
        }
        
        if (!optionForm.action_data.error_message || !optionForm.action_data.error_message.trim()) {
            optionErrors.value.error_message = 'Error message is required for input types'
        }
        
        // Validate specific input types
        if (optionForm.action_type === 'input_number') {
            if (optionForm.action_data.min && optionForm.action_data.max && 
                parseFloat(optionForm.action_data.min) > parseFloat(optionForm.action_data.max)) {
                optionErrors.value.range = 'Minimum value cannot be greater than maximum value'
            }
        }
        
        if (optionForm.action_type === 'input_phone') {
            if (optionForm.action_data.country_code === 'custom' && 
                (!optionForm.action_data.custom_country_code || !optionForm.action_data.custom_country_code.trim())) {
                optionErrors.value.country_code = 'Custom country code is required'
            }
        }
        
        if (optionForm.action_type === 'input_account') {
            if (!optionForm.action_data.length || !optionForm.action_data.length.trim()) {
                optionErrors.value.length = 'Expected length is required for account input'
            } else if (isNaN(optionForm.action_data.length) || parseInt(optionForm.action_data.length) <= 0) {
                optionErrors.value.length = 'Length must be a positive number'
            }
        }
        
        if (optionForm.action_type === 'input_pin') {
            if (!optionForm.action_data.length || !optionForm.action_data.length.trim()) {
                optionErrors.value.length = 'PIN length is required'
            } else if (isNaN(optionForm.action_data.length) || parseInt(optionForm.action_data.length) <= 0) {
                optionErrors.value.length = 'PIN length must be a positive number'
            }
        }
        
        if (optionForm.action_type === 'input_amount') {
            if (optionForm.action_data.min_amount && optionForm.action_data.max_amount && 
                parseFloat(optionForm.action_data.min_amount) > parseFloat(optionForm.action_data.max_amount)) {
                optionErrors.value.amount_range = 'Minimum amount cannot be greater than maximum amount'
            }
        }
        
        if (optionForm.action_type === 'input_selection') {
            if (!optionForm.action_data.options || !optionForm.action_data.options.trim()) {
                optionErrors.value.options = 'Options are required for selection input'
            }
        }
    }
    
    if (optionForm.action_type === 'navigate' && optionForm.next_flow_id) {
        // Validate that the selected flow exists
        const flowExists = flows.value.find(f => f.id === parseInt(optionForm.next_flow_id))
        if (!flowExists) {
            optionErrors.value.next_flow_id = 'Selected flow does not exist'
        }
    }
    
    return Object.keys(optionErrors.value).length === 0
}

const cancelFlowEdit = () => {
    // Reset the flow to its original state
    if (selectedFlow.value) {
        selectedFlow.value.menu_text = selectedFlow.value.originalMenuText
        selectedFlow.value.description = selectedFlow.value.originalDescription
        selectedFlow.value.options = selectedFlow.value.originalOptions
    }
    closeEditFlowModal()
}

const hasUnsavedChanges = () => {
    if (!selectedFlow.value) return false
    
    // Check if menu text changed
    if (selectedFlow.value.menu_text !== selectedFlow.value.originalMenuText) {
        return true
    }
    
    // Check if description changed
    if (selectedFlow.value.description !== selectedFlow.value.originalDescription) {
        return true
    }
    
    // Check if options changed
    const originalOptions = selectedFlow.value.originalOptions || []
    const currentOptions = selectedFlow.value.options || []
    
    if (originalOptions.length !== currentOptions.length) {
        return true
    }
    
    // Check each option for changes
    for (let i = 0; i < currentOptions.length; i++) {
        const current = currentOptions[i]
        const original = originalOptions[i]
        
        if (!original || 
            current.option_text !== original.option_text ||
            current.option_value !== original.option_value ||
            current.action_type !== original.action_type ||
            JSON.stringify(current.action_data) !== JSON.stringify(original.action_data)) {
            return true
        }
    }
    
    return false
}

// Debug function to test CSRF token
const debugCSRF = async () => {
    try {
        console.log('Testing CSRF token...')
        const response = await fetch('/test-csrf', {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            }
        })
        
        if (response.ok) {
            const data = await response.json()
            console.log('CSRF Test Response:', data)
            
            // Compare with page token
            const pageToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
            console.log('Page CSRF Token:', pageToken)
            console.log('Server CSRF Token:', data.csrf_token)
            console.log('Tokens match:', pageToken === data.csrf_token)
            
            return data
        } else {
            console.error('CSRF test failed:', response.status)
        }
    } catch (error) {
        console.error('CSRF test error:', error)
    }
}

// Debug function to show current state
const debugCurrentState = () => {
    console.log('=== Current State Debug ===')
    console.log('Selected Flow:', selectedFlow.value)
    console.log('Show Add Modal:', showAddFlowModal.value)
    console.log('Show Edit Modal:', showEditFlowModal.value)
    console.log('Flow Form Data:', {
        name: flowForm.name,
        menu_text: flowForm.menu_text,
        description: flowForm.description
    })
    
    if (selectedFlow.value) {
        console.log('Selected Flow Data:', {
            id: selectedFlow.value.id,
            name: selectedFlow.value.name,
            menu_text: selectedFlow.value.menu_text,
            description: selectedFlow.value.description,
            options_count: selectedFlow.value.options?.length || 0
        })
    }
    
    const isEditing = selectedFlow.value && selectedFlow.value.id && !showAddFlowModal.value
    console.log('Is Editing Mode:', isEditing)
    console.log('========================')
}

// Call debug function on page load
onMounted(() => {
    debugCSRF()
    debugCurrentState()
})

const refreshPage = () => {
    // Implement the logic to refresh the page
    console.log('Refreshing page...')
    location.reload()
}

// Watcher to handle action_data changes when action_type changes
watch(() => selectedFlow.value?.options, (newOptions) => {
    if (newOptions) {
        newOptions.forEach(option => {
            if (option.action_type === 'message' && !option.action_data) {
                option.action_data = { message: '' }
            } else if (option.action_type === 'input_text' && !option.action_data) {
                option.action_data = { prompt: '', validation: '', error_message: '' }
            } else if (option.action_type === 'input_number' && !option.action_data) {
                option.action_data = { prompt: '', min: '', max: '', error_message: '' }
            } else if (option.action_type === 'input_phone' && !option.action_data) {
                option.action_data = { prompt: '', country_code: '+234', custom_country_code: '', error_message: '' }
            } else if (option.action_type === 'input_account' && !option.action_data) {
                option.action_data = { prompt: '', account_type: 'bank', length: '', error_message: '' }
            } else if (option.action_type === 'input_pin' && !option.action_data) {
                option.action_data = { prompt: '', length: '4', error_message: '' }
            } else if (option.action_type === 'input_amount' && !option.action_data) {
                option.action_data = { prompt: '', min_amount: '', max_amount: '', currency: 'NGN', error_message: '' }
            } else if (option.action_type === 'input_selection' && !option.action_data) {
                option.action_data = { prompt: '', options: '', error_message: '' }
            } else if (option.action_type !== 'message' && 
                       !option.action_type.startsWith('input_') && 
                       option.action_data) {
                option.action_data = null
            }
            
            // Clear next_flow_id if action type is not navigate
            if (option.action_type !== 'navigate') {
                option.next_flow_id = null
            }
        })
    }
}, { deep: true })

// Also watch optionForm action_type changes
watch(() => optionForm.action_type, (newActionType) => {
    if (newActionType === 'message' && !optionForm.action_data) {
        optionForm.action_data = { message: '' }
    } else if (newActionType === 'input_text' && !optionForm.action_data) {
        optionForm.action_data = { prompt: '', validation: '', error_message: '' }
    } else if (newActionType === 'input_number' && !optionForm.action_data) {
        optionForm.action_data = { prompt: '', min: '', max: '', error_message: '' }
    } else if (newActionType === 'input_phone' && !optionForm.action_data) {
        optionForm.action_data = { prompt: '', country_code: '+234', custom_country_code: '', error_message: '' }
    } else if (newActionType === 'input_account' && !optionForm.action_data) {
        optionForm.action_data = { prompt: '', account_type: 'bank', length: '', error_message: '' }
    } else if (newActionType === 'input_pin' && !optionForm.action_data) {
        optionForm.action_data = { prompt: '', length: '4', error_message: '' }
    } else if (newActionType === 'input_amount' && !optionForm.action_data) {
        optionForm.action_data = { prompt: '', min_amount: '', max_amount: '', currency: 'NGN', error_message: '' }
    } else if (newActionType === 'input_selection' && !optionForm.action_data) {
        optionForm.action_data = { prompt: '', options: '', error_message: '' }
    } else if (newActionType !== 'message' && 
               !newActionType.startsWith('input_') && 
               optionForm.action_data) {
        optionForm.action_data = null
    }
    
    // Clear next_flow_id if action type is not navigate
    if (newActionType !== 'navigate') {
        optionForm.next_flow_id = null
    }
})
</script>

<style scoped>
/* Ensure consistent input sizing */
.flex-1 {
  flex: 1 1 0%;
}

.w-20 {
  width: 5rem;
  min-width: 5rem;
  max-width: 5rem;
}

.w-32 {
  width: 8rem;
  min-width: 8rem;
  max-width: 8rem;
}

/* Prevent flex items from growing beyond their intended size */
input, select {
  box-sizing: border-box;
}

/* Ensure consistent spacing */
.gap-2 > * {
  margin: 0;
}
</style> 