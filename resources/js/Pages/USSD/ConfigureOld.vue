<template>
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-900">Configure USSD Flow</h2>
        <div class="flex items-center gap-4">
          <span class="text-sm text-gray-500">Service: {{ ussd.name }} ({{ ussd.pattern }})</span>
          <form :action="route('ussd.simulator', ussd.id)" method="GET" class="inline">
            <input type="hidden" name="_token" :value="$page.props.csrf_token" />
            <button 
              type="submit"
              class="px-4 py-2 bg-green-600 text-white text-sm font-semibold rounded hover:bg-green-700 flex items-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h1m4 0h1m-6 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
              Test Simulation
            </button>
          </form>
        </div>
      </div>
    </template>

  <!-- Marketplace Modal -->
  <div v-if="showMarketplaceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Browse Marketplace APIs</h3>
          <button @click="closeMarketplaceModal" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <div class="max-h-96 overflow-y-auto">
          <div v-for="(category, categoryName) in marketplaceApisByCategory" :key="categoryName" class="mb-6">
            <h4 class="text-md font-semibold text-gray-800 mb-3 border-b pb-2">{{ categoryName }}</h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
              <div v-for="api in category" :key="api.id" 
                   class="border border-gray-200 rounded-lg p-4 hover:border-indigo-300 hover:shadow-md transition-all cursor-pointer"
                   @click="selectMarketplaceApi(api)">
                <div class="flex items-start justify-between">
                  <div class="flex-1">
                    <h5 class="font-medium text-gray-900">{{ api.name }}</h5>
                    <p class="text-sm text-gray-600 mt-1">{{ api.description }}</p>
                    <div class="flex items-center gap-2 mt-2">
                      <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded">{{ api.method }}</span>
                      <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded">{{ api.provider_name }}</span>
                      <span :class="getApiStatusClass(api.test_status)" class="text-xs px-2 py-1 rounded">
                        {{ api.test_status }}
                      </span>
                    </div>
                  </div>
                  <button @click.stop="selectMarketplaceApi(api)" 
                          class="ml-2 px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700">
                    Use API
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end">
          <button @click="closeMarketplaceModal" 
                  class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
            Close
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- API Configuration Wizard Modal -->
  <div v-if="showAPIConfigurationWizard" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/4 xl:w-2/3 shadow-lg rounded-md bg-white">
      <div class="mt-3">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Configure API Integration</h3>
          <button @click="closeAPIConfigurationWizard" class="text-gray-400 hover:text-gray-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        
        <APIConfigurationWizard
          :marketplace-apis="marketplaceApis"
          :custom-apis="customApis"
          :available-flows="availableFlows"
          @completed="handleAPIConfigurationCompleted"
          @cancelled="closeAPIConfigurationWizard"
        />
      </div>
    </div>
  </div>

  <div class="py-8">
    <div class="max-w-6xl mx-auto grid grid-cols-1 md:grid-cols-4 gap-8">
        <!-- Flows List -->
        <div class="col-span-1 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
          <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Flows</h3>
            <button @click="openAddFlowModal" class="px-2 py-1 rounded bg-indigo-600 text-white text-xs font-semibold hover:bg-indigo-700">+ Add Flow</button>
          </div>
          <ul>
            <li v-for="flow in flows" :key="flow.id" :class="[selectedFlow && selectedFlow.id === flow.id ? 'bg-indigo-50' : '', 'rounded p-2 mb-2 cursor-pointer hover:bg-indigo-100']" @click="selectFlow(flow)">
              <div class="flex items-center justify-between">
                <span class="font-medium">{{ flow.name }}</span>
                <div class="flex items-center gap-2">
                  <span v-if="flow.is_root" class="text-xs text-green-600">Root</span>
                  <span v-if="selectedFlow && selectedFlow.id === flow.id && hasUnsavedChanges()" class="text-xs text-orange-600 font-semibold">*</span>
                </div>
              </div>
              <div class="text-xs text-gray-500 truncate">
                <div v-if="flow.title" class="font-medium">{{ flow.title }}</div>
                <div>{{ flow.menu_text }}</div>
              </div>
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
            
            <!-- Flow Type Selector -->
            <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
              <label class="block text-sm font-medium text-gray-700 mb-3">Flow Type</label>
              <div class="flex items-center space-x-6">
                <label class="flex items-center">
                  <input 
                    type="radio" 
                    v-model="selectedFlow.flow_type" 
                    value="static"
                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                  />
                  <span class="ml-2 text-sm text-gray-700">Static Flow</span>
                </label>
                <label class="flex items-center">
                  <input 
                    type="radio" 
                    v-model="selectedFlow.flow_type" 
                    value="dynamic"
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
              <input 
                v-model="selectedFlow.title" 
                type="text"
                class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Enter title/header (e.g., Report type of fire)"
              />
              <p class="mt-1 text-xs text-gray-500">This will appear above the menu options</p>
            </div>
            
            <!-- Dynamic Flow Configuration -->
            <div v-if="selectedFlow.flow_type === 'dynamic'" class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
              <h4 class="text-sm font-medium text-blue-900 mb-3">Dynamic Flow Configuration</h4>
              
              <!-- API Selection -->
              <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Data Source API</label>
                <div class="flex gap-2">
                  <select 
                    v-model="selectedFlow.dynamic_config.api_configuration_id" 
                    class="flex-1 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    @change="handleDynamicApiSelection"
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
                    @click="openAPIConfigurationWizard()" 
                    type="button" 
                    class="px-3 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700"
                  >
                    Configure API
                  </button>
                </div>
                <p class="mt-1 text-xs text-gray-500">Select the API that will provide the dynamic content for this flow</p>
              </div>

              <!-- API Response Configuration -->
              <div v-if="selectedFlow.dynamic_config.api_configuration_id" class="space-y-4">
                <!-- Response Path Configuration -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Options List Path</label>
                    <input 
                      v-model="selectedFlow.dynamic_config.list_path" 
                      type="text"
                      class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="e.g., data.bundles or results"
                    />
                    <p class="mt-1 text-xs text-gray-500">JSON path to the array of options (e.g., data.bundles)</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Option Label Field</label>
                    <input 
                      v-model="selectedFlow.dynamic_config.label_field" 
                      type="text"
                      class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="e.g., name, name+price, {name} - {price}"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                      <strong>Single:</strong> name<br/>
                      <strong>Multiple:</strong> name+price (separated by +)<br/>
                      <strong>Template:</strong> {name} - {price} (use {} for placeholders)
                    </p>
                  </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Option Value Field</label>
                    <input 
                      v-model="selectedFlow.dynamic_config.value_field" 
                      type="text"
                      class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                      placeholder="e.g., id or code"
                    />
                    <p class="mt-1 text-xs text-gray-500">Field name for option value</p>
                  </div>
                  <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Empty State Message</label>
                    <input 
                      v-model="selectedFlow.dynamic_config.empty_message" 
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
                      v-model="selectedFlow.dynamic_config.items_per_page" 
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
                        v-model="selectedFlow.dynamic_config.next_label" 
                        type="text"
                        class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        placeholder="Next"
                      />
                      <input 
                        v-model="selectedFlow.dynamic_config.back_label" 
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
                        v-model="selectedFlow.dynamic_config.continuation_type" 
                        value="continue"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                      />
                      <span class="ml-2 text-sm text-gray-700">Continue to next flow after selection</span>
                    </label>
                    <label class="flex items-center">
                      <input 
                        type="radio" 
                        v-model="selectedFlow.dynamic_config.continuation_type" 
                        value="end"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                      />
                      <span class="ml-2 text-sm text-gray-700">End session after selection</span>
                    </label>
                    <label class="flex items-center">
                      <input 
                        type="radio" 
                        v-model="selectedFlow.dynamic_config.continuation_type" 
                        value="api_dependent"
                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                      />
                      <span class="ml-2 text-sm text-gray-700">Let API response determine next step</span>
                    </label>
                    <label class="flex items-center">
                      <input 
                        type="radio" 
                        v-model="selectedFlow.dynamic_config.continuation_type" 
                        value="continue_without_display"
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
                <div v-if="selectedFlow.dynamic_config.continuation_type === 'continue' || selectedFlow.dynamic_config.continuation_type === 'continue_without_display'">
                  <label class="block text-sm font-medium text-gray-700 mb-2">Next Flow</label>
                  <select 
                    v-model="selectedFlow.dynamic_config.next_flow_id" 
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

            <!-- Menu Text Editor (Static Flow Only) -->
            <div v-if="selectedFlow.flow_type === 'static'" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Menu Text (Numbered Options Only)</label>
              <textarea 
                v-model="selectedFlow.menu_text" 
                rows="3" 
                class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Enter numbered options only (e.g., 1. Gas Leak&#10;2. Fuel/Petrol&#10;3. Oil)"
                @input="handleMenuTextChange"
              ></textarea>
              <p class="mt-1 text-xs text-gray-500">Enter only the numbered options. Edit this text to automatically update the options below, or edit options to update this text.</p>
            </div>
            
            <!-- Description -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Description</label>
              <input v-model="selectedFlow.description" class="mt-1 block w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500" />
            </div>
            
            <!-- Options Editor (Static Flow Only) -->
            <div v-if="selectedFlow.flow_type === 'static'" class="mb-4">
              <label class="block text-sm font-medium text-gray-700">Options (Auto-syncs with menu text)</label>
              <p class="mt-1 text-xs text-gray-500 mb-2">
                <strong>Input Collection Tip:</strong> For input options, you can specify what happens after collecting input. 
                Leave "Next Flow" empty to stay in the same flow, select "End session after input" to close the session, or choose a flow to navigate to.
              </p>
              <div v-for="(option, idx) in selectedFlow.options" :key="option.id || idx" class="bg-gray-50 rounded p-3 mb-2 flex flex-col md:flex-row md:items-center gap-2 w-full">
                <input v-model="option.option_text" placeholder="Option Text" class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" @input="handleOptionChange" />
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
                  <option value="external_api_call">External API Call</option>
                </select>
                
                <!-- Action-specific fields -->
                <input v-if="option.action_type === 'message'" v-model="option.action_data.message" placeholder="Message" class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                <select v-if="option.action_type === 'navigate'" v-model="option.next_flow_id" class="w-40 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                  <option value="">Select flow</option>
                  <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">{{ flow.name }}</option>
                </select>
                
                <!-- Phone number configuration for navigate actions -->
                <div v-if="option.action_type === 'navigate'" class="flex flex-col gap-2 w-full mt-2">
                  <div class="flex items-center gap-2">
                    {{ option.action_data }}
                    <input type="checkbox" v-model="option.action_data.use_registered_phone" :id="'use-phone-' + idx" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <label :for="'use-phone-' + idx" class="text-sm text-gray-700">Use registered phone number for this option</label>
                  </div>
                </div>
                
                <!-- Input collection configuration -->
                <div v-if="['input_text', 'input_number', 'input_phone', 'input_account', 'input_pin', 'input_amount', 'input_selection'].includes(option.action_type)" class="flex flex-col gap-2 w-full">
                  <div class="bg-yellow-50 border border-yellow-200 rounded p-2 mb-2">
                    <p class="text-xs text-yellow-800 font-medium">After collecting input:</p>
                  </div>
                  
                  <!-- Action type selection -->
                  <select v-model="option.action_data.after_input_action" @change="handleAfterInputActionChange(option)" class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="show_menu">Show this menu again</option>
                    <option value="end_session">End the session</option>
                    <option value="navigate">Go to another menu</option>
                    <option value="external_api_call">Connect to marketplace</option>
                    <option value="process_data">Process the data</option>
                  </select>
                  
                  <!-- Navigation flow selection -->
                  <select v-if="option.action_data.after_input_action === 'navigate'" v-model="option.next_flow_id" class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="">Select flow</option>
                    <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">{{ flow.name }}</option>
                  </select>
                  
                  <!-- Marketplace API selection -->
                  <div v-if="option.action_data.after_input_action === 'external_api_call'" class="flex gap-2">
                    <select v-model="option.action_data.api_configuration_id" class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
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
                    <button @click="openAPIConfigurationWizard(option)" type="button" class="px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700">
                      Configure
                    </button>
                  </div>
                  
                  <!-- Process data options -->
                  <select v-if="option.action_data.after_input_action === 'process_data'" v-model="option.action_data.process_type" class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                    <option value="process_registration">Process registration</option>
                    <option value="process_feedback">Process feedback</option>
                    <option value="process_survey">Process survey</option>
                    <option value="process_contact">Process contact</option>
                  </select>
                  
                  <!-- Input configuration -->
                  <input v-model="option.action_data.prompt" placeholder="Custom question (optional - leave blank for default)" class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                  <input v-model="option.action_data.success_message" placeholder="Success message (optional)" class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" />
                </div>
                
                <!-- External API Call configuration -->
                <div v-if="option.action_type === 'external_api_call'" class="flex flex-col gap-2 w-full">
                  <div class="flex gap-2">
                    <select v-model="option.action_data.api_configuration_id" class="flex-1 min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm" @change="handleApiSelection(option)">
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
                    <button @click="openAPIConfigurationWizard(option)" type="button" class="px-3 py-1 bg-indigo-600 text-white text-xs rounded hover:bg-indigo-700">
                      Configure API
                    </button>
                  </div>
                  
                  <div v-if="option.action_data.api_configuration_id" class="bg-blue-50 p-3 rounded border border-blue-200">
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
                    <select v-model="option.action_data.success_flow_id" class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                      <option value="">Success: Stay in same flow</option>
                      <option value="end_session">Success: End session</option>
                      <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">Success: Go to {{ flow.name }}</option>
                    </select>
                    <select v-model="option.action_data.error_flow_id" class="w-full min-w-0 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                      <option value="">Error: Stay in same flow</option>
                      <option value="end_session">Error: End session</option>
                      <option v-for="flow in availableFlows" :key="flow.id" :value="flow.id">Error: Go to {{ flow.name }}</option>
                    </select>
                  </div>
                  
                  <div class="flex items-center gap-2">
                    <input type="checkbox" v-model="option.action_data.end_session_after_api" id="end-session-{{ idx }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <label for="end-session-{{ idx }}" class="text-sm text-gray-700">End session after API call</label>
                  </div>
                  
                  <div v-if="option.action_type === 'external_api_call' && option.action_data && option.action_data.api_configuration_id && !option.action_data.end_session_after_api && (option.action_data.success_flow_id || option.next_flow_id)" class="flex items-center gap-2">
                    <input type="checkbox" v-model="option.action_data.continue_without_display" :id="'continue-without-display-' + idx" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                    <label :for="'continue-without-display-' + idx" class="text-sm text-gray-700">Continue to next flow without displaying API response</label>
                  </div>
                </div>
                
                <button @click="removeOption(idx)" class="text-red-500 hover:text-red-700 ml-2 px-2 py-1 text-sm whitespace-nowrap">Remove</button>
              </div>
              <button @click="addOption" class="mt-2 px-3 py-1 rounded bg-indigo-100 text-indigo-700 text-xs font-semibold hover:bg-indigo-200">+ Add Option</button>
            </div>
            
            <!-- Save/Cancel Buttons -->
            <div class="flex justify-end gap-2">
              <button 
                @click="cancelEdit" 
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
      <div v-if="showAddFlowModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-10 mx-auto p-5 border w-11/12 md:w-4/5 lg:w-3/4 xl:w-2/3 shadow-lg rounded-md bg-white max-w-4xl">
          <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-medium text-gray-900">Add New Flow</h3>
              <button @click="closeAddFlowModal" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
              </button>
            </div>
            
            <div class="max-h-[70vh] overflow-y-auto">
        <div class="space-y-4">
          <div v-if="flowErrors.general" class="bg-red-50 border border-red-200 rounded-md p-3">
            <p class="text-sm text-red-600">{{ flowErrors.general }}</p>
          </div>
          
          <!-- Flow Type Selector -->
          <div class="p-4 bg-gray-50 rounded-lg border border-gray-200">
            <label class="block text-sm font-medium text-gray-700 mb-3">Flow Type</label>
            <div class="flex items-center space-x-6">
              <label class="flex items-center">
                <input 
                  type="radio" 
                  v-model="flowForm.flow_type" 
                  value="static"
                  class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                />
                <span class="ml-2 text-sm text-gray-700">Static Flow</span>
              </label>
              <label class="flex items-center">
                <input 
                  type="radio" 
                  v-model="flowForm.flow_type" 
                  value="dynamic"
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
              v-model="flowForm.name" 
              type="text"
              :class="[flowErrors.name ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter flow name"
            />
            <p v-if="flowErrors.name" class="mt-1 text-sm text-red-600">{{ flowErrors.name }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700">Title/Header (Optional)</label>
            <input 
              v-model="flowForm.title" 
              type="text"
              :class="[flowErrors.title ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter title/header (e.g., Report type of fire)"
            />
            <p v-if="flowErrors.title" class="mt-1 text-sm text-red-600">{{ flowErrors.title }}</p>
            <p class="mt-1 text-xs text-gray-500">This will appear above the menu options (optional)</p>
          </div>
          <!-- Menu Text Editor (Static Flow Only) -->
          <div v-if="flowForm.flow_type === 'static'">
            <label class="block text-sm font-medium text-gray-700">Menu Text (Numbered Options Only)</label>
            <textarea 
              v-model="flowForm.menu_text" 
              rows="3"
              :class="[flowErrors.menu_text ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : 'border-gray-300 focus:border-indigo-500 focus:ring-indigo-500', 'mt-1 block w-full rounded-md shadow-sm sm:text-sm']" 
              placeholder="Enter numbered options only (e.g., 1. Gas Leak&#10;2. Fuel/Petrol&#10;3. Oil)"
            ></textarea>
            <p v-if="flowErrors.menu_text" class="mt-1 text-sm text-red-600">{{ flowErrors.menu_text }}</p>
            <p class="mt-1 text-xs text-gray-500">Enter only the numbered options. The title will be added automatically.</p>
          </div>
          <!-- Dynamic Flow Configuration -->
          <div v-if="flowForm.flow_type === 'dynamic'" class="p-4 bg-blue-50 rounded-lg border border-blue-200">
            <h4 class="text-sm font-medium text-blue-900 mb-3">Dynamic Flow Configuration</h4>
            
            <!-- API Selection -->
            <div class="mb-4">
              <label class="block text-sm font-medium text-gray-700 mb-2">Data Source API</label>
              <div class="flex gap-2">
                <select 
                  v-model="flowForm.dynamic_config.api_configuration_id" 
                  class="flex-1 rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                  @change="handleNewFlowDynamicApiSelection"
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
                  @click="openAPIConfigurationWizard()" 
                  type="button" 
                  class="px-3 py-2 bg-indigo-600 text-white text-sm rounded hover:bg-indigo-700"
                >
                  Configure API
                </button>
              </div>
              <p class="mt-1 text-xs text-gray-500">Select the API that will provide the dynamic content for this flow</p>
            </div>

            <!-- API Response Configuration -->
            <div v-if="flowForm.dynamic_config.api_configuration_id" class="space-y-4">
              <!-- Response Path Configuration -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Options List Path</label>
                  <input 
                    v-model="flowForm.dynamic_config.list_path" 
                    type="text"
                    class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="e.g., data.bundles or results"
                  />
                  <p class="mt-1 text-xs text-gray-500">JSON path to the array of options (e.g., data.bundles)</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Option Label Field</label>
                  <input 
                    v-model="flowForm.dynamic_config.label_field" 
                    type="text"
                    class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="e.g., name, name+price, {name} - {price}"
                  />
                  <p class="mt-1 text-xs text-gray-500">
                    <strong>Single:</strong> name<br/>
                    <strong>Multiple:</strong> name+price (separated by +)<br/>
                    <strong>Template:</strong> {name} - {price} (use {} for placeholders)
                  </p>
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Option Value Field</label>
                  <input 
                    v-model="flowForm.dynamic_config.value_field" 
                    type="text"
                    class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="e.g., id or code"
                  />
                  <p class="mt-1 text-xs text-gray-500">Field name for option value</p>
                </div>
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Empty State Message</label>
                  <input 
                    v-model="flowForm.dynamic_config.empty_message" 
                    type="text"
                    class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="e.g., No options available"
                  />
                  <p class="mt-1 text-xs text-gray-500">Message shown when no data is available</p>
                </div>
              </div>

              <!-- Configuration Examples -->
              <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h5 class="text-sm font-medium text-blue-900 mb-3">📋 Configuration Examples for Your API Response</h5>
                <div class="text-xs text-blue-800 space-y-2">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <strong>Simple Display:</strong><br/>
                      <code class="bg-white px-2 py-1 rounded">name</code><br/>
                      <span class="text-gray-600">→ "MTN DG 75.0MB - 1DAY"</span>
                    </div>
                    <div>
                      <strong>With Price:</strong><br/>
                      <code class="bg-white px-2 py-1 rounded">name+price</code><br/>
                      <span class="text-gray-600">→ "MTN DG 75.0MB - 1DAY - 75.0"</span>
                    </div>
                    <div>
                      <strong>Custom Template:</strong><br/>
                      <code class="bg-white px-2 py-1 rounded">{name} - N{price}</code><br/>
                      <span class="text-gray-600">→ "MTN DG 75.0MB - 1DAY - N75.0"</span>
                    </div>
                    <div>
                      <strong>Network + Category:</strong><br/>
                      <code class="bg-white px-2 py-1 rounded">{network} {category} - {name}</code><br/>
                      <span class="text-gray-600">→ "MTN DG - MTN DG 75.0MB - 1DAY"</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Configuration Examples -->
              <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                <h5 class="text-sm font-medium text-blue-900 mb-3">📋 Configuration Examples for Your API Response</h5>
                <div class="text-xs text-blue-800 space-y-2">
                  <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <strong>Simple Display:</strong><br/>
                      <code class="bg-white px-2 py-1 rounded">name</code><br/>
                      <span class="text-gray-600">→ "MTN DG 75.0MB - 1DAY"</span>
                    </div>
                    <div>
                      <strong>With Price:</strong><br/>
                      <code class="bg-white px-2 py-1 rounded">name+price</code><br/>
                      <span class="text-gray-600">→ "MTN DG 75.0MB - 1DAY - 75.0"</span>
                    </div>
                    <div>
                      <strong>Custom Template:</strong><br/>
                      <code class="bg-white px-2 py-1 rounded">{name} - N{price}</code><br/>
                      <span class="text-gray-600">→ "MTN DG 75.0MB - 1DAY - N75.0"</span>
                    </div>
                    <div>
                      <strong>Network + Category:</strong><br/>
                      <code class="bg-white px-2 py-1 rounded">{network} {category} - {name}</code><br/>
                      <span class="text-gray-600">→ "MTN DG - MTN DG 75.0MB - 1DAY"</span>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Pagination Configuration -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-gray-700 mb-2">Items Per Page</label>
                  <select 
                    v-model="flowForm.dynamic_config.items_per_page" 
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
                      v-model="flowForm.dynamic_config.next_label" 
                      type="text"
                      class="w-full rounded border-gray-300 shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                      placeholder="Next"
                    />
                    <input 
                      v-model="flowForm.dynamic_config.back_label" 
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
                      v-model="flowForm.dynamic_config.continuation_type" 
                      value="continue"
                      class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Continue to next flow after selection</span>
                  </label>
                  <label class="flex items-center">
                    <input 
                      type="radio" 
                      v-model="flowForm.dynamic_config.continuation_type" 
                      value="end"
                      class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">End session after selection</span>
                  </label>
                  <label class="flex items-center">
                    <input 
                      type="radio" 
                      v-model="flowForm.dynamic_config.continuation_type" 
                      value="api_dependent"
                      class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Let API response determine next step</span>
                  </label>
                  <label class="flex items-center">
                    <input 
                      type="radio" 
                      v-model="flowForm.dynamic_config.continuation_type" 
                      value="continue_without_display"
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
              <div v-if="flowForm.dynamic_config.continuation_type === 'continue' || flowForm.dynamic_config.continuation_type === 'continue_without_display'">
                <label class="block text-sm font-medium text-gray-700 mb-2">Next Flow</label>
                <select 
                  v-model="flowForm.dynamic_config.next_flow_id" 
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
            
            <!-- Modal Actions -->
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-200">
              <button 
                @click="closeAddFlowModal" 
                :disabled="savingFlow"
                class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              >
                Cancel
              </button>
              <button 
                @click="saveNewFlow" 
                :disabled="savingFlow"
                :class="[
                  savingFlow ? 'bg-gray-400' : 'bg-indigo-600 hover:bg-indigo-700',
                  'px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2'
                ]"
              >
                <svg v-if="savingFlow" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                {{ savingFlow ? 'Adding...' : 'Add Flow' }}
              </button>
            </div>
          </div>
        </div>
      </div>

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
    </div>
  </div>
  </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import FormModal from '@/Components/FormModal.vue'
import ConfirmationModal from '@/Components/ConfirmationModal.vue'
import APIConfigurationWizard from '@/Components/APIConfigurationWizard.vue'
import { csrfToken } from '@/utils/csrf.js'

const props = defineProps({
    ussd: Object,
    marketplaceApis: {
        type: Array,
        default: () => []
    },
    customApis: {
        type: Array,
        default: () => []
    }
})

const flows = ref(props.ussd.flows || [])
const selectedFlow = ref(null)
const originalFlow = ref(null)
const showAddFlowModal = ref(false)
const showDeleteFlowModal = ref(false)
const showMarketplaceModal = ref(false)
const showAPIConfigurationWizard = ref(false)
const savingFlow = ref(false)
const deletingFlow = ref(false)
const currentApiOption = ref(null)

// Form data
const flowForm = useForm({
    name: '',
    title: '',
    menu_text: '',
    description: '',
    flow_type: 'static',
    dynamic_config: {
        api_configuration_id: '',
        list_path: '',
        label_field: 'name',
        value_field: 'id',
        empty_message: 'No options available',
        continuation_type: 'continue',
        next_flow_id: ''
    }
})

// Validation errors
const flowErrors = ref({})

// Computed properties
const availableFlows = computed(() => {
    return flows.value.filter(flow => flow.id !== selectedFlow.value?.id)
})

const marketplaceApisByCategory = computed(() => {
    const grouped = {}
    props.marketplaceApis.forEach(api => {
        const category = api.marketplace_category || 'Other'
        if (!grouped[category]) {
            grouped[category] = []
        }
        grouped[category].push(api)
    })
    return grouped
})

// Use the CSRF token utility for refreshing tokens

// Methods
const selectFlow = (flow) => {
    // Save current changes if any
    if (selectedFlow.value && hasUnsavedChanges()) {
        if (confirm('You have unsaved changes. Do you want to save them?')) {
            saveFlow()
        }
    }
    
    // Deep clone the flow and ensure all options have proper action_data
    const clonedFlow = JSON.parse(JSON.stringify(flow))
    
    // Initialize flow_type if not set (default to static for backward compatibility)
    if (!clonedFlow.flow_type) {
        clonedFlow.flow_type = 'static'
    }
    
    // Initialize dynamic_config if not set
    if (!clonedFlow.dynamic_config) {
        clonedFlow.dynamic_config = {
            api_configuration_id: '',
            list_path: '',
            label_field: 'name',
            value_field: 'id',
            empty_message: 'No options available',
            continuation_type: 'continue',
            next_flow_id: ''
        }
    }
    
    if (clonedFlow.options) {
        clonedFlow.options.forEach(option => {
            // Preserve continue_without_display before processing action_data
            const preservedContinueWithoutDisplay = option.action_data?.continue_without_display
            
            // Handle action_data - ensure it's an object (preserve existing data)
            if (option.action_data === null || option.action_data === undefined) {
                option.action_data = {}
            } else if (typeof option.action_data === 'string') {
                // If it's a string, try to parse it as JSON
                try {
                    option.action_data = JSON.parse(option.action_data)
                } catch (e) {
                    option.action_data = {}
                }
            } else if (Array.isArray(option.action_data)) {
                // Convert array to object if it's an array (preserve data if possible)
                const arr = option.action_data
                option.action_data = {}
                // Try to preserve array data as object if it has key-value pairs
                arr.forEach((item, index) => {
                    if (typeof item === 'object' && item !== null) {
                        Object.assign(option.action_data, item)
                    } else {
                        option.action_data[index] = item
                    }
                })
            } else if (typeof option.action_data !== 'object') {
                option.action_data = {}
            }
            
            // Restore continue_without_display if it was preserved
            if (preservedContinueWithoutDisplay !== undefined && !('continue_without_display' in option.action_data)) {
                option.action_data.continue_without_display = preservedContinueWithoutDisplay
            }
            
            // Ensure continue_without_display is a boolean (preserve existing value)
            if (option.action_data) {
                if (option.action_data.continue_without_display === undefined || option.action_data.continue_without_display === null) {
                    // If not set, default to false (but don't overwrite if it exists)
                    if (!('continue_without_display' in option.action_data)) {
                        option.action_data.continue_without_display = false
                    }
                } else if (typeof option.action_data.continue_without_display !== 'boolean') {
                    // Convert string/other types to boolean
                    option.action_data.continue_without_display = option.action_data.continue_without_display === true || option.action_data.continue_without_display === 'true' || option.action_data.continue_without_display === 1
                }
                // If it's already a boolean, leave it as is
            }
            
            // Handle end_session_after_input flag for display
            if (option.action_data && option.action_data.end_session_after_input) {
                option.next_flow_id = 'end_session'
            }
        })
    }
    
    selectedFlow.value = clonedFlow
    originalFlow.value = JSON.parse(JSON.stringify(clonedFlow))
}

const openAddFlowModal = () => {
    flowForm.reset()
    flowForm.flow_type = 'static'
    flowForm.dynamic_config = {
        api_configuration_id: '',
        list_path: '',
        label_field: 'name',
        value_field: 'id',
        empty_message: 'No options available',
        continuation_type: 'continue',
        next_flow_id: '',
        items_per_page: '7',
        next_label: 'Next',
        back_label: 'Back'
    }
    showAddFlowModal.value = true
}

const openDeleteFlowModal = (flow) => {
    selectedFlow.value = flow
    showDeleteFlowModal.value = true
}

const closeAddFlowModal = () => {
    flowForm.reset()
    flowForm.flow_type = 'static'
    flowForm.dynamic_config = {
        api_configuration_id: '',
        list_path: '',
        label_field: 'name',
        value_field: 'id',
        empty_message: 'No options available',
        continuation_type: 'continue',
        next_flow_id: '',
        items_per_page: '7',
        next_label: 'Next',
        back_label: 'Back'
    }
    flowErrors.value = {}
    showAddFlowModal.value = false
}

const addOption = () => {
    if (!selectedFlow.value.options) {
        selectedFlow.value.options = []
    }
    
    const newOption = {
        option_text: '',
        option_value: '',
        action_type: 'navigate',
        action_data: {},
        next_flow_id: null
    }
    
    selectedFlow.value.options.push(newOption)
}

const removeOption = (index) => {
    selectedFlow.value.options.splice(index, 1)
}

const handleMenuTextChange = () => {
    // Parse menu text and update options
    const lines = selectedFlow.value.menu_text.split('\n').filter(line => line.trim())
    const newOptions = []
    
    lines.forEach((line, index) => {
        // Remove numbering patterns
        let optionText = line.trim().replace(/^\d+[\.\)]?\s*/, '')
        
        if (optionText) {
            const newOption = {
                option_text: optionText,
                option_value: (index + 1).toString(),
                action_type: 'navigate',
                action_data: {},
                next_flow_id: null
            }
            newOptions.push(newOption)
        }
    })
    
    // Update options if we have valid ones
    if (newOptions.length > 0) {
        selectedFlow.value.options = newOptions
    }
}

const handleOptionChange = () => {
    // Generate menu text from options
    const validOptions = selectedFlow.value.options.filter(option => option.option_text && option.option_text.trim())
    
    if (validOptions.length > 0) {
        let menuText = ''
        validOptions.forEach((option, index) => {
            if (index > 0) menuText += '\n'
            menuText += `${index + 1}. ${option.option_text}`
        })
        selectedFlow.value.menu_text = menuText
    }
}

const hasUnsavedChanges = () => {
    if (!selectedFlow.value || !originalFlow.value) return false
    
    return JSON.stringify(selectedFlow.value) !== JSON.stringify(originalFlow.value)
}

const saveFlow = async () => {
    if (!selectedFlow.value) return
    
    console.log('Starting saveFlow - making PUT request to update flow')
    savingFlow.value = true
    flowErrors.value = {}
    
    try {
        // Ensure all options have properly initialized action_data before sending
        const optionsToSend = (selectedFlow.value.options || []).map(option => {
            const optionCopy = { ...option }
            // Ensure action_data is an object
            if (!optionCopy.action_data || typeof optionCopy.action_data !== 'object' || Array.isArray(optionCopy.action_data)) {
                optionCopy.action_data = optionCopy.action_data && !Array.isArray(optionCopy.action_data) ? { ...optionCopy.action_data } : {}
            }
            // Ensure boolean values are preserved
            if (optionCopy.action_data.continue_without_display !== undefined) {
                optionCopy.action_data.continue_without_display = Boolean(optionCopy.action_data.continue_without_display)
            }
            if (optionCopy.action_data.end_session_after_api !== undefined) {
                optionCopy.action_data.end_session_after_api = Boolean(optionCopy.action_data.end_session_after_api)
            }
            return optionCopy
        })
        
        const requestData = {
            name: selectedFlow.value.name,
            title: selectedFlow.value.title || '',
            menu_text: selectedFlow.value.menu_text,
            description: selectedFlow.value.description || '',
            options: optionsToSend,
            flow_type: selectedFlow.value.flow_type || 'static',
            dynamic_config: selectedFlow.value.dynamic_config || {}
        }
        console.log('Sending PUT request with data:', JSON.stringify(requestData, null, 2))
        
        const response = await fetch(`/ussd/${props.ussd.id}/flows/${selectedFlow.value.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.get(),
                'Accept': 'application/json'
            },
            body: JSON.stringify(requestData)
        })
        
        // Handle CSRF token mismatch
        if (response.status === 419) {
            // Refresh CSRF token and retry
            await csrfToken.refresh()
            return saveFlow() // Retry the request
        }
        
        const result = await response.json()
        console.log('Received response from server:', JSON.stringify(result, null, 2))
        
        if (result.success) {
            // Update the flow in the flows array
            const index = flows.value.findIndex(f => f.id === selectedFlow.value.id)
            flows.value[index] = result.flow
            
            // Convert the saved flow data for UI display (handle end_session_after_input flag)
            const convertedFlow = JSON.parse(JSON.stringify(result.flow))
            console.log('Converted flow options before processing:', convertedFlow.options?.map(opt => ({
                option_text: opt.option_text,
                action_type: opt.action_type,
                action_data: opt.action_data,
                continue_without_display: opt.action_data?.continue_without_display
            })))
            if (convertedFlow.options) {
                convertedFlow.options.forEach(option => {
                    // Handle action_data - ensure it's an object (preserve existing data)
                    if (option.action_data === null || option.action_data === undefined) {
                        option.action_data = {}
                    } else if (typeof option.action_data === 'string') {
                        // If it's a string, try to parse it as JSON
                        try {
                            option.action_data = JSON.parse(option.action_data)
                        } catch (e) {
                            option.action_data = {}
                        }
                    } else if (Array.isArray(option.action_data)) {
                        // Convert array to object if it's an array (preserve data if possible)
                        const arr = option.action_data
                        option.action_data = {}
                        // Try to preserve array data as object if it has key-value pairs
                        arr.forEach((item, index) => {
                            if (typeof item === 'object' && item !== null) {
                                Object.assign(option.action_data, item)
                            } else {
                                option.action_data[index] = item
                            }
                        })
                    } else if (typeof option.action_data !== 'object') {
                        option.action_data = {}
                    }
                    
                    // Ensure continue_without_display is a boolean (preserve existing value)
                    if (option.action_data) {
                        if (option.action_data.continue_without_display === undefined || option.action_data.continue_without_display === null) {
                            // If not set, default to false (but don't overwrite if it exists)
                            if (!('continue_without_display' in option.action_data)) {
                                option.action_data.continue_without_display = false
                            }
                        } else if (typeof option.action_data.continue_without_display !== 'boolean') {
                            // Convert string/other types to boolean
                            option.action_data.continue_without_display = option.action_data.continue_without_display === true || option.action_data.continue_without_display === 'true' || option.action_data.continue_without_display === 1
                        }
                        // If it's already a boolean, leave it as is
                    }
                    
                    // Handle end_session_after_input flag for display
                    if (option.action_data && option.action_data.end_session_after_input) {
                        option.next_flow_id = 'end_session'
                    }
                })
            }
            
            selectedFlow.value = convertedFlow
            originalFlow.value = JSON.parse(JSON.stringify(convertedFlow))
            
            // Debug: Log the final state
            console.log('Final selectedFlow options:', selectedFlow.value.options?.map(opt => ({
                option_text: opt.option_text,
                action_type: opt.action_type,
                action_data: opt.action_data,
                continue_without_display: opt.action_data?.continue_without_display,
                end_session_after_api: opt.action_data?.end_session_after_api,
                success_flow_id: opt.action_data?.success_flow_id
            })))
        } else {
            flowErrors.value = result.errors || {}
        }
    } catch (error) {
        flowErrors.value.general = 'An error occurred while saving the flow.'
    } finally {
        savingFlow.value = false
    }
}

const saveNewFlow = async () => {
    savingFlow.value = true
    flowErrors.value = {}
    
    try {
        const response = await fetch(`/ussd/${props.ussd.id}/flows`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken.get(),
                'Accept': 'application/json'
            },
            body: JSON.stringify(flowForm.data())
        })
        
        // Handle CSRF token mismatch
        if (response.status === 419) {
            // Refresh CSRF token and retry
            await csrfToken.refresh()
            return saveNewFlow() // Retry the request
        }
        
        const result = await response.json()
        
        if (result.success) {
            // Convert the saved flow data for UI display (handle end_session_after_input flag)
            const convertedFlow = JSON.parse(JSON.stringify(result.flow))
            if (convertedFlow.options) {
                convertedFlow.options.forEach(option => {
                    // Handle action_data - convert arrays to objects and ensure it's an object
                    if (option.action_data === null) {
                        option.action_data = {}
                    } else if (Array.isArray(option.action_data)) {
                        // Convert array to object if it's an array
                        option.action_data = {}
                    } else if (typeof option.action_data !== 'object') {
                        option.action_data = {}
                    }
                    
                    // Handle end_session_after_input flag for display
                    if (option.action_data && option.action_data.end_session_after_input) {
                        option.next_flow_id = 'end_session'
                    }
                })
            }
            
            flows.value.push(convertedFlow)
            showAddFlowModal.value = false
            flowForm.reset()
        } else {
            flowErrors.value = result.errors || {}
        }
    } catch (error) {
        flowErrors.value.general = 'An error occurred while creating the flow.'
    } finally {
        savingFlow.value = false
    }
}

const deleteFlow = async () => {
    if (!selectedFlow.value) return
    
    deletingFlow.value = true
    
    try {
        const response = await fetch(`/ussd/${props.ussd.id}/flows/${selectedFlow.value.id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken.get(),
                'Accept': 'application/json'
            }
        })
        
        // Handle CSRF token mismatch
        if (response.status === 419) {
            // Refresh CSRF token and retry
            await csrfToken.refresh()
            return deleteFlow() // Retry the request
        }
        
        const result = await response.json()
        
        if (result.success) {
            flows.value = flows.value.filter(f => f.id !== selectedFlow.value.id)
            selectedFlow.value = null
            originalFlow.value = null
            showDeleteFlowModal.value = false
        }
    } catch (error) {
        console.error('Error deleting flow:', error)
    } finally {
        deletingFlow.value = false
    }
}

const cancelEdit = () => {
    if (hasUnsavedChanges()) {
        if (confirm('You have unsaved changes. Are you sure you want to cancel?')) {
            selectedFlow.value = originalFlow.value ? JSON.parse(JSON.stringify(originalFlow.value)) : null
        }
    }
}

// Marketplace API methods
const openMarketplaceModal = (option = null) => {
    currentApiOption.value = option
    showMarketplaceModal.value = true
}

const closeMarketplaceModal = () => {
    showMarketplaceModal.value = false
    currentApiOption.value = null
}

const openAPIConfigurationWizard = (option = null) => {
    currentApiOption.value = option
    showAPIConfigurationWizard.value = true
}

const closeAPIConfigurationWizard = () => {
    showAPIConfigurationWizard.value = false
    currentApiOption.value = null
}

const handleAPIConfigurationCompleted = (data) => {
    if (currentApiOption.value) {
        // Set the API configuration for the current option
        currentApiOption.value.action_data.api_configuration_id = data.api.id
        currentApiOption.value.action_data.success_flow_id = data.configuration.success_flow_id
        currentApiOption.value.action_data.error_flow_id = data.configuration.error_flow_id
        currentApiOption.value.action_data.end_session_after_api = data.configuration.end_session_after_api
    }
    closeAPIConfigurationWizard()
}

const selectMarketplaceApi = (api) => {
    if (currentApiOption.value) {
        // Set the API configuration for the current option
        currentApiOption.value.action_data.api_configuration_id = api.id
        currentApiOption.value.action_data.end_session_after_api = false
    }
    closeMarketplaceModal()
}

const handleApiSelection = (option) => {
    // Initialize action_data if it doesn't exist
    if (!option.action_data) {
        option.action_data = {}
    }
    
    // Set default values for API call
    if (option.action_data.api_configuration_id && !option.action_data.end_session_after_api) {
        option.action_data.end_session_after_api = false
    }
}

const ensureActionData = (option) => {
    // Initialize action_data if it doesn't exist or is null
    if (!option.action_data || typeof option.action_data !== 'object') {
        option.action_data = {}
    }
}

const handleAfterInputActionChange = (option) => {
    // Initialize action_data if it doesn't exist
    if (!option.action_data) {
        option.action_data = {}
    }
    
    // Set default values based on after input action
    switch (option.action_data.after_input_action) {
        case 'external_api_call':
            option.action_data.api_configuration_id = option.action_data.api_configuration_id || ''
            option.action_data.success_flow_id = option.action_data.success_flow_id || ''
            option.action_data.error_flow_id = option.action_data.error_flow_id || ''
            option.action_data.end_session_after_api = option.action_data.end_session_after_api || false
            option.action_data.continue_without_display = option.action_data.continue_without_display || false
            break
        case 'navigate':
            option.next_flow_id = option.next_flow_id || ''
            break
        case 'process_data':
            option.action_data.process_type = option.action_data.process_type || 'process_registration'
            break
    }
}

const handleDynamicApiSelection = () => {
    // Initialize dynamic_config if it doesn't exist
    if (!selectedFlow.value.dynamic_config) {
        selectedFlow.value.dynamic_config = {
            api_configuration_id: '',
            list_path: '',
            label_field: 'name',
            value_field: 'id',
            empty_message: 'No options available',
            continuation_type: 'continue',
            next_flow_id: ''
        }
    }
    
    // Set default values based on selected API
    const selectedApi = getSelectedApi(selectedFlow.value.dynamic_config.api_configuration_id)
    if (selectedApi) {
        // Set smart defaults based on API metadata if available
        if (selectedApi.marketplace_metadata) {
            const metadata = selectedApi.marketplace_metadata
            if (metadata.default_list_path) {
                selectedFlow.value.dynamic_config.list_path = metadata.default_list_path
            }
            if (metadata.default_label_field) {
                selectedFlow.value.dynamic_config.label_field = metadata.default_label_field
            }
            if (metadata.default_value_field) {
                selectedFlow.value.dynamic_config.value_field = metadata.default_value_field
            }
        }
    }
}

const handleNewFlowDynamicApiSelection = () => {
    // Set default values based on selected API for new flow form
    const selectedApi = getSelectedApi(flowForm.dynamic_config.api_configuration_id)
    if (selectedApi) {
        // Set smart defaults based on API metadata if available
        if (selectedApi.marketplace_metadata) {
            const metadata = selectedApi.marketplace_metadata
            if (metadata.default_list_path) {
                flowForm.dynamic_config.list_path = metadata.default_list_path
            }
            if (metadata.default_label_field) {
                flowForm.dynamic_config.label_field = metadata.default_label_field
            }
            if (metadata.default_value_field) {
                flowForm.dynamic_config.value_field = metadata.default_value_field
            }
        }
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

// Auto-select first flow on mount
onMounted(() => {
    if (flows.value.length > 0 && !selectedFlow.value) {
        selectFlow(flows.value[0])
    }
})
</script>

<style scoped>
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

input, select {
  box-sizing: border-box;
}

.gap-2 > * {
  margin: 0;
}
</style> 