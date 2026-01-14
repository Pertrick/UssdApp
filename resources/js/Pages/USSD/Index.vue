<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                My USSD Services
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Header with Create Button -->
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">USSD Services</h3>
                            <Link
                                :href="route('ussd.create')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Create New USSD
                            </Link>
                        </div>

                        <!-- Success Message -->
                        <div v-if="$page.props.flash && $page.props.flash.success" class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
                            {{ $page.props.flash.success }}
                        </div>

                        <!-- USSD List -->
                        <div v-if="filteredUssds.length > 0" class="space-y-4">
                            <!-- Production USSDs Section -->
                            <div v-if="productionUssds.length > 0" class="mb-6">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <span class="w-2 h-2 bg-green-500 rounded-full mr-2"></span>
                                    Production Services ({{ productionUssds.length }})
                                </h4>
                                <div class="space-y-4">
                                    <div
                                        v-for="ussd in productionUssds"
                                        :key="ussd.id"
                                        class="border-2 border-green-200 bg-green-50 rounded-lg p-6 hover:shadow-md transition-shadow"
                                    >
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-3 mb-2">
                                                    <h4 class="text-lg font-semibold text-gray-900">{{ ussd.name }}</h4>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-600 text-white">
                                                        Production
                                                    </span>
                                                    <span
                                                        :class="[
                                                            'px-2 py-1 text-xs font-medium rounded-full',
                                                            ussd.is_active
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-red-100 text-red-800'
                                                        ]"
                                                    >
                                                        {{ ussd.is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </div>
                                                <p class="text-gray-600 mb-3">{{ ussd.description }}</p>
                                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                                    <span class="font-medium">USSD Code: {{ getCurrentUssdCode(ussd) }}</span>
                                                    <span>Business: {{ ussd.business?.business_name }}</span>
                                                    <span>Created: {{ formatDate(ussd.created_at) }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <Link
                                                    :href="route('ussd.show', ussd.id)"
                                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    :href="route('ussd.edit', ussd.id)"
                                                    class="text-green-600 hover:text-green-800 text-sm font-medium"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    @click="toggleStatus(ussd)"
                                                    :class="[
                                                        'text-sm font-medium',
                                                        ussd.is_active
                                                            ? 'text-orange-600 hover:text-orange-800'
                                                            : 'text-green-600 hover:text-green-800'
                                                    ]"
                                                >
                                                    {{ ussd.is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                                <button
                                                    @click="deleteUSSD(ussd)"
                                                    class="text-red-600 hover:text-red-800 text-sm font-medium"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Testing USSDs Section -->
                            <div v-if="testingUssds.length > 0">
                                <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">
                                    <span class="w-2 h-2 bg-yellow-500 rounded-full mr-2"></span>
                                    Testing Services ({{ testingUssds.length }})
                                </h4>
                                <div class="space-y-4">
                                    <div
                                        v-for="ussd in testingUssds"
                                        :key="ussd.id"
                                        class="border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow"
                                    >
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-3 mb-2">
                                                    <h4 class="text-lg font-semibold text-gray-900">{{ ussd.name }}</h4>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                                        Testing
                                                    </span>
                                                    <span
                                                        :class="[
                                                            'px-2 py-1 text-xs font-medium rounded-full',
                                                            ussd.is_active
                                                                ? 'bg-green-100 text-green-800'
                                                                : 'bg-red-100 text-red-800'
                                                        ]"
                                                    >
                                                        {{ ussd.is_active ? 'Active' : 'Inactive' }}
                                                    </span>
                                                </div>
                                                <p class="text-gray-600 mb-3">{{ ussd.description }}</p>
                                                <div class="flex items-center space-x-4 text-sm text-gray-500">
                                                    <span class="font-medium">USSD Code: {{ getCurrentUssdCode(ussd) }}</span>
                                                    <span>Business: {{ ussd.business?.business_name }}</span>
                                                    <span>Created: {{ formatDate(ussd.created_at) }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <Link
                                                    :href="route('ussd.show', ussd.id)"
                                                    class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                                                >
                                                    View
                                                </Link>
                                                <Link
                                                    :href="route('ussd.edit', ussd.id)"
                                                    class="text-green-600 hover:text-green-800 text-sm font-medium"
                                                >
                                                    Edit
                                                </Link>
                                                <button
                                                    @click="toggleStatus(ussd)"
                                                    :class="[
                                                        'text-sm font-medium',
                                                        ussd.is_active
                                                            ? 'text-orange-600 hover:text-orange-800'
                                                            : 'text-green-600 hover:text-green-800'
                                                    ]"
                                                >
                                                    {{ ussd.is_active ? 'Deactivate' : 'Activate' }}
                                                </button>
                                                <button
                                                    @click="deleteUSSD(ussd)"
                                                    class="text-red-600 hover:text-red-800 text-sm font-medium"
                                                >
                                                    Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Empty State -->
                        <div v-if="filteredUssds.length === 0" class="text-center py-12">
                            <div class="text-gray-400 mb-4">
                                <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No USSD Services Yet</h3>
                            <p class="text-gray-500 mb-6">Get started by creating your first USSD service.</p>
                            <Link
                                :href="route('ussd.create')"
                                class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Create Your First USSD
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'

const props = defineProps({
    ussds: {
        type: Array,
        required: true
    }
})

// Separate USSDs by environment
const productionUssds = computed(() => {
    return props.ussds.filter(ussd => ussd.environment?.name === 'production')
})

const testingUssds = computed(() => {
    return props.ussds.filter(ussd => ussd.environment?.name !== 'production')
})

// Show production if any exist, otherwise show only testing
const filteredUssds = computed(() => {
    if (productionUssds.value.length > 0) {
        return [...productionUssds.value, ...testingUssds.value]
    }
    return testingUssds.value
})

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    })
}

const getCurrentUssdCode = (ussd) => {
    // Pattern is used for all environments (testing and production)
    return ussd.pattern || 'Not configured'
}

const toggleStatus = (ussd) => {
    router.patch(route('ussd.toggle-status', ussd.id))
}

const deleteUSSD = (ussd) => {
    if (confirm('Are you sure you want to delete this USSD service? This action cannot be undone.')) {
        router.delete(route('ussd.destroy', ussd.id))
    }
}
</script> 