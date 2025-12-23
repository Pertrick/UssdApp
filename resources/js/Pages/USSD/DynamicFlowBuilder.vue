<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Dynamic Flow Builder
                </h2>
                <Link
                    :href="route('ussd.show', ussd.id)"
                    class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to USSD
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Flow Overview -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Flow: {{ ussd.name }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-blue-600">Total Steps</div>
                                <div class="text-2xl font-bold text-blue-900">{{ flowSteps.length }}</div>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-green-600">Active Steps</div>
                                <div class="text-2xl font-bold text-green-900">{{ activeSteps.length }}</div>
                            </div>
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <div class="text-sm font-medium text-purple-600">API Integrations</div>
                                <div class="text-2xl font-bold text-purple-900">{{ apiSteps.length }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Flow Steps -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Flow Steps</h3>
                            <button
                                @click="showAddStepModal = true"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Step
                            </button>
                        </div>

                        <!-- Steps List -->
                        <div class="space-y-4">
                            <div
                                v-for="(step, index) in sortedSteps"
                                :key="step.id"
                                class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors"
                            >
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <div class="flex-shrink-0">
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span class="text-sm font-medium text-blue-600">{{ index + 1 }}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-medium text-gray-900">{{ step.step_id }}</h4>
                                            <p class="text-sm text-gray-500">{{ getStepTypeLabel(step.type) }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span
                                            :class="[
                                                'px-2 py-1 text-xs font-medium rounded-full',
                                                step.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800'
                                            ]"
                                        >
                                            {{ step.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <button
                                            @click="editStep(step)"
                                            class="text-blue-600 hover:text-blue-900 text-sm font-medium"
                                        >
                                            Edit
                                        </button>
                                        <button
                                            @click="deleteStep(step)"
                                            class="text-red-600 hover:text-red-900 text-sm font-medium"
                                        >
                                            Delete
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Step Details -->
                                <div class="mt-3 pl-12">
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium">Next Step:</span> {{ step.next_step || 'End' }}
                                    </div>
                                    <div v-if="step.data" class="mt-2">
                                        <div class="text-sm text-gray-600">
                                            <span class="font-medium">Configuration:</span>
                                            <pre class="mt-1 text-xs bg-gray-100 p-2 rounded overflow-x-auto">{{ JSON.stringify(step.data, null, 2) }}</pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="flowSteps.length === 0" class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No flow steps</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating your first flow step.</p>
                        </div>
                    </div>
                </div>

                <!-- Flow Configurations -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">Flow Configurations</h3>
                            <button
                                @click="showAddConfigModal = true"
                                class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Config
                            </button>
                        </div>

                        <!-- Configurations List -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div
                                v-for="config in flowConfigs"
                                :key="config.id"
                                class="border border-gray-200 rounded-lg p-4"
                            >
                                <div class="flex justify-between items-start mb-2">
                                    <h4 class="text-sm font-medium text-gray-900">{{ config.key }}</h4>
                                    <button
                                        @click="deleteConfig(config)"
                                        class="text-red-600 hover:text-red-900 text-sm"
                                    >
                                        Delete
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500 mb-2">{{ config.description }}</p>
                                <div class="text-sm text-gray-600">
                                    <pre class="bg-gray-100 p-2 rounded text-xs overflow-x-auto">{{ JSON.stringify(config.value, null, 2) }}</pre>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Step Modal -->
        <Modal :show="showAddStepModal" @close="showAddStepModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add Flow Step</h3>
                
                <form @submit.prevent="addStep">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Step ID</label>
                            <input
                                v-model="newStep.step_id"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., select_network"
                                required
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Step Type</label>
                            <select
                                v-model="newStep.type"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                                <option value="">Select type</option>
                                <option value="menu">Menu</option>
                                <option value="api_call">API Call</option>
                                <option value="dynamic_menu">Dynamic Menu</option>
                                <option value="input">Input</option>
                                <option value="condition">Condition</option>
                                <option value="message">Message</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Next Step</label>
                            <input
                                v-model="newStep.next_step"
                                type="text"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder="e.g., fetch_bundles"
                            />
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Configuration (JSON)</label>
                            <textarea
                                v-model="newStep.data"
                                rows="6"
                                class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"
                                placeholder='{"title": "Select Network", "options": [{"label": "MTN", "value": "mtn"}]}'
                            ></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end space-x-3 mt-6">
                        <button
                            type="button"
                            @click="showAddStepModal = false"
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 bg-blue-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-blue-700"
                        >
                            Add Step
                        </button>
                    </div>
                </form>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';

const props = defineProps({
    ussd: Object,
    flowSteps: Array,
    flowConfigs: Array,
});

const showAddStepModal = ref(false);
const showAddConfigModal = ref(false);

const newStep = ref({
    step_id: '',
    type: '',
    next_step: '',
    data: '',
});

const sortedSteps = computed(() => {
    return [...props.flowSteps].sort((a, b) => a.sort_order - b.sort_order);
});

const activeSteps = computed(() => {
    return props.flowSteps.filter(step => step.is_active);
});

const apiSteps = computed(() => {
    return props.flowSteps.filter(step => step.type === 'api_call');
});

const getStepTypeLabel = (type) => {
    const labels = {
        'menu': 'Static Menu',
        'api_call': 'API Call',
        'dynamic_menu': 'Dynamic Menu',
        'input': 'User Input',
        'condition': 'Condition',
        'message': 'Message',
    };
    return labels[type] || type;
};

const addStep = () => {
    try {
        const data = newStep.value.data ? JSON.parse(newStep.value.data) : {};
        
        router.post(route('ussd.dynamic-flow.steps.store', props.ussd.id), {
            step_id: newStep.value.step_id,
            type: newStep.value.type,
            next_step: newStep.value.next_step,
            data: data,
        }, {
            onSuccess: () => {
                showAddStepModal.value = false;
                newStep.value = { step_id: '', type: '', next_step: '', data: '' };
            }
        });
    } catch (error) {
        alert('Invalid JSON in configuration field');
    }
};

const editStep = (step) => {
    // TODO: Implement edit functionality
    console.log('Edit step:', step);
};

const deleteStep = (step) => {
    if (confirm('Are you sure you want to delete this step?')) {
        router.delete(route('ussd.dynamic-flow.steps.destroy', [props.ussd.id, step.id]));
    }
};

const deleteConfig = (config) => {
    if (confirm('Are you sure you want to delete this configuration?')) {
        router.delete(route('ussd.dynamic-flow.configs.destroy', [props.ussd.id, config.id]));
    }
};
</script>
