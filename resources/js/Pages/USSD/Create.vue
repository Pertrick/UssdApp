<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">Create New USSD Service</h2>
                    <p class="text-sm text-gray-600 mt-1">Set up a new USSD service for your business</p>
                </div>
                <Link
                    :href="route('ussd.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Back to Services
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="max-w-3xl mx-auto">
                <!-- Main Form Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Form Header -->
                    <div class="px-6 py-4 bg-gradient-to-r from-indigo-50 to-blue-50 border-b border-gray-200">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-indigo-600 rounded-lg flex items-center justify-center">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                </div>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-semibold text-gray-900">Service Information</h3>
                                <p class="text-sm text-gray-600">Provide the basic details for your USSD service</p>
                            </div>
                        </div>
                    </div>

                    <!-- Form Content -->
                    <div class="p-6">
                        <Form @submit="submit" :validation-schema="schema" v-slot="{ errors, meta }" class="space-y-6">
                            <!-- USSD Name -->
                            <div>
                                <InputLabel for="name" value="Service Name" class="text-sm font-medium text-gray-700" />
                                <div class="mt-1 relative">
                                    <Field
                                        name="name"
                                        v-slot="{ field, meta }"
                                    >
                                        <TextInput
                                            id="name"
                                            type="text"
                                            :class="[
                                                'block w-full pl-4 pr-10 py-3 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors',
                                                meta.touched && meta.valid ? 'border-green-300 focus:border-green-500 focus:ring-green-500' : '',
                                                meta.touched && !meta.valid ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''
                                            ]"
                                            v-bind="field"
                                            required
                                            autofocus
                                            placeholder="Enter service name (e.g., Banking Service, Payment Gateway)"
                                        />
                                    </Field>
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg v-if="meta.touched && meta.valid" class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <svg v-else-if="meta.touched && !meta.valid" class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                        <svg v-else class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </div>
                                </div>
                                <div class="mt-1 flex items-center justify-between">
                                    <p class="text-sm text-gray-500">Choose a descriptive name for your USSD service</p>
                                    <span class="text-xs text-gray-400">{{ form.name.length }}/100</span>
                                </div>
                                <ErrorMessage name="name" class="mt-2 text-sm text-red-600" />
                            </div>

                            <!-- USSD Description -->
                            <div>
                                <InputLabel for="description" value="Description" class="text-sm font-medium text-gray-700" />
                                <div class="mt-1">
                                    <Field
                                        name="description"
                                        v-slot="{ field, meta }"
                                    >
                                        <textarea
                                            id="description"
                                            :class="[
                                                'block w-full px-4 py-3 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none',
                                                meta.touched && meta.valid ? 'border-green-300 focus:border-green-500 focus:ring-green-500' : '',
                                                meta.touched && !meta.valid ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''
                                            ]"
                                            v-bind="field"
                                            rows="4"
                                            required
                                            placeholder="Describe what this USSD service does, its features, and how users can benefit from it..."
                                        ></textarea>
                                    </Field>
                                </div>
                                <div class="mt-1 flex items-center justify-between">
                                    <p class="text-sm text-gray-500">Provide a clear description of your service functionality</p>
                                    <span class="text-xs text-gray-400">{{ form.description.length }}/500</span>
                                </div>
                                <ErrorMessage name="description" class="mt-2 text-sm text-red-600" />
                            </div>

                            <!-- USSD Pattern -->
                            <div>
                                <InputLabel for="pattern" value="USSD Code Pattern" class="text-sm font-medium text-gray-700" />
                                <div class="mt-1">
                                    <div class="relative rounded-lg shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-lg font-bold">*</span>
                                        </div>
                                        <Field
                                            name="pattern"
                                            v-slot="{ field, meta }"
                                        >
                                            <TextInput
                                                id="pattern"
                                                type="text"
                                                :class="[
                                                    'block w-full pl-8 pr-4 py-3 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors',
                                                    meta.touched && meta.valid ? 'border-green-300 focus:border-green-500 focus:ring-green-500' : '',
                                                    meta.touched && !meta.valid ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : ''
                                                ]"
                                                v-bind="field"
                                                required
                                                placeholder="e.g., 123# or 456*789#"
                                            />
                                        </Field>
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                            <svg v-if="meta.touched && meta.valid" class="h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            <svg v-else-if="meta.touched && !meta.valid" class="h-5 w-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                            </svg>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-1 flex items-center justify-between">
                                    <span class="text-xs text-gray-400">{{ form.pattern.length }}/20</span>
                                </div>
                                <div class="mt-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                    <div class="flex">
                                        <div class="flex-shrink-0">
                                            <svg class="h-5 w-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div class="ml-3">
                                            <h4 class="text-sm font-medium text-blue-800">USSD Pattern Guidelines</h4>
                                            <div class="mt-1 text-sm text-blue-700">
                                                <ul class="list-disc list-inside space-y-1">
                                                    <li>Use numbers and special characters (*, #)</li>
                                                    <li>Common formats: 123#, 456*789#, 1*2*3#</li>
                                                    <li>Keep it simple and memorable for users</li>
                                                    <li>Ensure it's unique and not already in use</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <ErrorMessage name="pattern" class="mt-2 text-sm text-red-600" />
                            </div>

                            <!-- Single-shot -->
                            <div class="border-t border-gray-200 pt-6">
                                <label class="flex items-start p-4 rounded-lg border-2 cursor-pointer transition-colors"
                                    :class="form.is_single_shot ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                                    <input type="checkbox" v-model="form.is_single_shot" class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500 rounded" />
                                    <div class="ml-3">
                                        <span class="font-medium text-gray-900">Single-shot</span>
                                        <p class="text-sm text-gray-500 mt-0.5">User dials the full path in one go (e.g. *737*1*2*3#). No interaction, immediate result.</p>
                                    </div>
                                </label>
                            </div>

                            <!-- USSD Type -->
                            <div class="border-t border-gray-200 pt-6">
                                <InputLabel value="USSD Type" />
                                <div class="mt-2 space-y-3">
                                    <label class="flex items-start p-4 rounded-lg border-2 cursor-pointer transition-colors"
                                        :class="!form.is_shared_gateway ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                                        <input type="radio" v-model="form.is_shared_gateway" :value="false" class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500" />
                                        <div class="ml-3">
                                            <span class="font-medium text-gray-900">Normal</span>
                                            <p class="text-sm text-gray-500 mt-0.5">Dedicated code. Users dial this pattern and go straight to your service.</p>
                                        </div>
                                    </label>
                                    <label class="flex items-start p-4 rounded-lg border-2 cursor-pointer transition-colors"
                                        :class="form.is_shared_gateway ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:border-gray-300'">
                                        <input type="radio" v-model="form.is_shared_gateway" :value="true" class="mt-1 h-4 w-4 text-indigo-600 focus:ring-indigo-500" />
                                        <div class="ml-3">
                                            <span class="font-medium text-gray-900">Shared gateway</span>
                                            <p class="text-sm text-gray-500 mt-0.5">One code, multiple services. Users see a menu (e.g. 1. MCD, 2. PlanetF) and choose which service to open.</p>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Allocations (only when Shared gateway) -->
                            <div v-if="form.is_shared_gateway" class="space-y-4 rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                                <div class="flex items-center justify-between">
                                    <InputLabel value="Allocations (option → business)" class="mb-0" />
                                    <button
                                        type="button"
                                        @click="addAllocation"
                                        class="text-sm font-medium text-indigo-600 hover:text-indigo-700"
                                    >
                                        + Add option
                                    </button>
                                </div>
                                <p class="text-xs text-gray-500">
                                    Option value is the digit users press (e.g. 1, 2). Label is shown in the menu. Target is the USSD service that opens.
                                </p>
                                <div
                                    v-for="(row, index) in form.allocations"
                                    :key="index"
                                    class="flex flex-wrap items-end gap-3 rounded border border-gray-200 bg-white p-3"
                                >
                                    <div class="w-16">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Option</label>
                                        <input
                                            v-model="row.option_value"
                                            type="text"
                                            maxlength="20"
                                            class="block w-full rounded border-gray-300 shadow-sm text-sm"
                                            placeholder="1"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-[140px]">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Label</label>
                                        <input
                                            v-model="row.label"
                                            type="text"
                                            maxlength="100"
                                            class="block w-full rounded border-gray-300 shadow-sm text-sm"
                                            placeholder="MCD"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-[180px]">
                                        <label class="block text-xs font-medium text-gray-500 mb-1">Target USSD</label>
                                        <select
                                            v-model="row.target_ussd_id"
                                            class="block w-full rounded border-gray-300 shadow-sm text-sm"
                                        >
                                            <option :value="null">Select service</option>
                                            <option
                                                v-for="u in otherUssds"
                                                :key="u.id"
                                                :value="u.id"
                                            >
                                                {{ u.name }} ({{ u.pattern }})
                                            </option>
                                        </select>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeAllocation(index)"
                                        class="rounded p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600"
                                        title="Remove"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                                <p v-if="form.allocations.length === 0" class="text-sm text-gray-500">
                                    Add at least one option (e.g. 1 → MCD, 2 → PlanetF).
                                </p>
                            </div>

                            <!-- Submit Section -->
                            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                                <div class="flex items-center space-x-4">
                                    <Link
                                        :href="route('ussd.index')"
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                    >
                                        Cancel
                                    </Link>
                                </div>
                                <div class="flex items-center space-x-3">
                                    <PrimaryButton
                                        :class="[
                                            'inline-flex items-center px-6 py-3 text-sm font-medium rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 transition-colors',
                                            meta.valid && !form.processing 
                                                ? 'bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500' 
                                                : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                                        ]"
                                        :disabled="!meta.valid || form.processing"
                                        type="submit"
                                    >
                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else-if="meta.valid" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                        {{ form.processing ? 'Creating USSD Service...' : (meta.valid ? 'Create USSD Service' : 'Please fill all required fields') }}
                                    </PrimaryButton>
                                </div>
                            </div>

                            <!-- Processing Overlay -->
                            <div v-if="form.processing" class="fixed inset-0 bg-gray-900 bg-opacity-50 flex items-center justify-center z-50">
                                <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
                                    <div class="flex items-center">
                                        <svg class="animate-spin h-8 w-8 text-indigo-600 mr-4" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900">Creating USSD Service</h3>
                                            <p class="text-sm text-gray-500 mt-1">Please wait while we process your request...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Server validation errors -->
                            <div v-if="Object.keys(form.errors).length > 0" class="mt-4 p-4 bg-red-50 rounded-lg border border-red-200">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-red-800">Please fix the following errors:</h4>
                                        <div class="mt-2 text-sm text-red-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li v-for="(error, field) in form.errors" :key="field">
                                                    {{ Array.isArray(error) ? error[0] : error }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Validation Summary -->
                            <div v-if="meta.touched && !meta.valid" class="mt-4 p-4 bg-red-50 rounded-lg border border-red-200">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <h4 class="text-sm font-medium text-red-800">Please fix the following errors:</h4>
                                        <div class="mt-2 text-sm text-red-700">
                                            <ul class="list-disc list-inside space-y-1">
                                                <li v-for="(error, field) in errors" :key="field">
                                                    {{ error }}
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Form>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="mt-8 bg-blue-50 rounded-xl border border-blue-200 p-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h4 class="text-sm font-medium text-blue-800">Need Help?</h4>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>Creating a USSD service is straightforward. Make sure to:</p>
                                <ul class="mt-2 list-disc list-inside space-y-1">
                                    <li>Choose a unique and memorable USSD code</li>
                                    <li>Provide a clear description of your service</li>
                                    <li>Test your service thoroughly before going live</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { useForm, Link } from '@inertiajs/vue3'
import { Form, Field, ErrorMessage } from 'vee-validate'
import * as yup from 'yup'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'

const { otherUssds = [] } = defineProps({
    otherUssds: {
        type: Array,
        default: () => []
    }
})

const form = useForm({
    name: '',
    description: '',
    pattern: '',
    is_shared_gateway: false,
    is_single_shot: false,
    allocations: [{ option_value: '', target_ussd_id: null, label: '' }]
})

function addAllocation() {
    form.allocations.push({ option_value: '', target_ussd_id: null, label: '' })
}

function removeAllocation(index) {
    form.allocations.splice(index, 1)
}

// Validation schema using Yup
const schema = yup.object({
    name: yup
        .string()
        .required('Service name is required')
        .min(3, 'Service name must be at least 3 characters')
        .max(100, 'Service name cannot exceed 100 characters')
        .matches(/^[a-zA-Z0-9\s\-_]+$/, 'Service name can only contain letters, numbers, spaces, hyphens, and underscores'),
    description: yup
        .string()
        .required('Description is required')
        .min(10, 'Description must be at least 10 characters')
        .max(500, 'Description cannot exceed 500 characters'),
    pattern: yup
        .string()
        .required('USSD pattern is required')
        .min(3, 'USSD pattern must be at least 3 characters')
        .max(20, 'USSD pattern cannot exceed 20 characters')
        .matches(/^[\d*#]+$/, 'USSD pattern can only contain numbers, asterisks (*), and hash (#)')
})

const submit = (values) => {
    form.name = values.name
    form.description = values.description
    form.pattern = values.pattern
    form.is_shared_gateway = form.is_shared_gateway
    form.is_single_shot = form.is_single_shot
    // Only send allocations when shared gateway; otherwise send empty array
    form.allocations = form.is_shared_gateway
        ? form.allocations.filter(r => r.option_value?.trim() && r.target_ussd_id && r.label?.trim())
        : []
    form.post(route('ussd.store'), {
        preserveScroll: true,
        onError: (errors) => {
            // Ensure errors are visible
            console.error('Form validation errors:', errors)
        }
    })
}
</script> 