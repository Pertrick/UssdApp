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
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- USSD Name -->
                            <div>
                                <InputLabel for="name" value="Service Name" class="text-sm font-medium text-gray-700" />
                                <div class="mt-1 relative">
                                    <TextInput
                                        id="name"
                                        type="text"
                                        class="block w-full pl-4 pr-10 py-3 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                        v-model="form.name"
                                        required
                                        autofocus
                                        placeholder="Enter service name (e.g., Banking Service, Payment Gateway)"
                                    />
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                        </svg>
                                    </div>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">Choose a descriptive name for your USSD service</p>
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- USSD Description -->
                            <div>
                                <InputLabel for="description" value="Description" class="text-sm font-medium text-gray-700" />
                                <div class="mt-1">
                                    <textarea
                                        id="description"
                                        class="block w-full px-4 py-3 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors resize-none"
                                        v-model="form.description"
                                        rows="4"
                                        required
                                        placeholder="Describe what this USSD service does, its features, and how users can benefit from it..."
                                    ></textarea>
                                </div>
                                <p class="mt-1 text-sm text-gray-500">Provide a clear description of your service functionality</p>
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <!-- USSD Pattern -->
                            <div>
                                <InputLabel for="pattern" value="USSD Code Pattern" class="text-sm font-medium text-gray-700" />
                                <div class="mt-1">
                                    <div class="relative rounded-lg shadow-sm">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-lg font-bold">*</span>
                                        </div>
                                        <TextInput
                                            id="pattern"
                                            type="text"
                                            class="block w-full pl-8 pr-4 py-3 border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors"
                                            v-model="form.pattern"
                                            required
                                            placeholder="e.g., 123# or 456*789#"
                                        />
                                    </div>
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
                                <InputError class="mt-2" :message="form.errors.pattern" />
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
                                        :class="{ 'opacity-50 cursor-not-allowed': form.processing }"
                                        :disabled="form.processing"
                                        class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors"
                                    >
                                        <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <svg v-else class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ form.processing ? 'Creating...' : 'Create USSD Service' }}
                                    </PrimaryButton>
                                </div>
                            </div>
                        </form>
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
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import TextInput from '@/Components/TextInput.vue'

const form = useForm({
    name: '',
    description: '',
    pattern: ''
})

const submit = () => {
    form.post(route('ussd.store'))
}
</script> 