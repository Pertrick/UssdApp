<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                USSD Service Details
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Back Button -->
                        <div class="mb-6">
                            <Link
                                :href="route('ussd.index')"
                                class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Back to USSD Services
                            </Link>
                        </div>

                        <!-- USSD Header -->
                        <div class="border-b border-gray-200 pb-6 mb-6">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h1 class="text-3xl font-bold text-gray-900 mb-2">{{ ussd.name }}</h1>
                                    <div class="flex items-center space-x-4">
                                        <span
                                            :class="[
                                                'px-3 py-1 text-sm font-medium rounded-full',
                                                ussd.is_active
                                                    ? 'bg-green-100 text-green-800'
                                                    : 'bg-red-100 text-red-800'
                                            ]"
                                        >
                                            {{ ussd.is_active ? 'Active' : 'Inactive' }}
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            Created {{ formatDate(ussd.created_at) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex space-x-3">
                                    <Link
                                        :href="route('ussd.edit', ussd.id)"
                                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Edit
                                    </Link>
                                    <Link
                                        :href="route('ussd.dynamic-flow.builder', ussd.id)"
                                        class="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Dynamic Flow Builder
                                    </Link>
                                    <form :action="route('ussd.simulator', ussd.id)" method="GET" class="inline">
                                        <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                        <button
                                            type="submit"
                                            class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                        >
                                            Test Simulator
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- USSD Details -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Basic Information -->
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Description</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ ussd.description }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">USSD Code</dt>
                                            <dd class="mt-1 text-sm text-gray-900 font-mono bg-gray-100 px-2 py-1 rounded">
                                                {{ getCurrentUssdCode() }}
                                            </dd>
                                        </div>
                                        <div v-if="ussd.environment">
                                            <dt class="text-sm font-medium text-gray-500">Environment</dt>
                                            <dd class="mt-1">
                                                <span :class="[
                                                    'px-2 py-1 text-xs font-medium rounded-full',
                                                    ussd.environment.name === 'production' 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : ussd.environment.name === 'testing'
                                                        ? 'bg-blue-100 text-blue-800'
                                                        : 'bg-gray-100 text-gray-800'
                                                ]">
                                                    {{ ussd.environment.name === 'production' ? 'Production' : 'Testing' }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Associated Business</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ ussd.business?.business_name }}</dd>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status & Actions -->
                            <div class="space-y-6">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Status & Actions</h3>
                                    <div class="space-y-4">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Current Status</dt>
                                            <dd class="mt-1">
                                                <span
                                                    :class="[
                                                        'px-3 py-1 text-sm font-medium rounded-full',
                                                        ussd.is_active
                                                            ? 'bg-green-100 text-green-800'
                                                            : 'bg-red-100 text-red-800'
                                                    ]"
                                                >
                                                    {{ ussd.is_active ? 'Active' : 'Inactive' }}
                                                </span>
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                                            <dd class="mt-1 text-sm text-gray-900">{{ formatDate(ussd.updated_at) }}</dd>
                                        </div>
                                        <div class="pt-4">
                                            <button
                                                @click="toggleStatus"
                                                :class="[
                                                    'w-full px-4 py-2 text-sm font-medium rounded-md border transition-colors',
                                                    ussd.is_active
                                                        ? 'border-orange-300 text-orange-700 bg-orange-50 hover:bg-orange-100'
                                                        : 'border-green-300 text-green-700 bg-green-50 hover:bg-green-100'
                                                ]"
                                            >
                                                {{ ussd.is_active ? 'Deactivate Service' : 'Activate Service' }}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <Link
                                    :href="route('ussd.configure', ussd.id)"
                                    class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors"
                                >
                                    <div class="flex-shrink-0">
                                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Configure</h4>
                                        <p class="text-sm text-gray-500">Set up USSD flow and responses</p>
                                    </div>
                                </Link>

                                <form :action="route('ussd.simulator', ussd.id)" method="GET" class="w-full">
                                    <input type="hidden" name="_token" :value="$page.props.csrf_token" />
                                    <button
                                        type="submit"
                                        class="w-full flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-left"
                                    >
                                        <div class="flex-shrink-0">
                                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                        </div>
                                        <div class="ml-4">
                                            <h4 class="text-sm font-medium text-gray-900">Test Simulator</h4>
                                            <p class="text-sm text-gray-500">Test your USSD service</p>
                                        </div>
                                    </button>
                                </form>

                                <button
                                    @click="deleteUSSD"
                                    class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-red-50 transition-colors text-left"
                                >
                                    <div class="flex-shrink-0">
                                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-sm font-medium text-gray-900">Delete</h4>
                                        <p class="text-sm text-gray-500">Remove this USSD service</p>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    ussd: {
        type: Object,
        required: true
    }
})

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    })
}

const getCurrentUssdCode = () => {
    // Use the current USSD code based on environment
    if (props.ussd.environment?.name === 'production') {
        return props.ussd.live_ussd_code || 'Not configured'
    } else if (props.ussd.environment?.name === 'testing') {
        return props.ussd.testing_ussd_code || 'Not configured'
    }
    // Fallback to testing code, then live code, then pattern, then 'Not configured'
    return props.ussd.testing_ussd_code || props.ussd.live_ussd_code || props.ussd.pattern || 'Not configured'
}

const toggleStatus = () => {
    router.patch(route('ussd.toggle-status', props.ussd.id))
}

const deleteUSSD = () => {
    if (confirm('Are you sure you want to delete this USSD service? This action cannot be undone.')) {
        router.delete(route('ussd.destroy', props.ussd.id))
    }
}
</script> 