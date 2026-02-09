<template>
    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit USSD Service
            </h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <!-- Back Button -->
                        <div class="mb-6">
                            <Link
                                :href="route('ussd.show', ussd.id)"
                                class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900"
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                </svg>
                                Back to USSD Details
                            </Link>
                        </div>

                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- USSD Name -->
                            <div>
                                <InputLabel for="name" value="USSD Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                    autocomplete="name"
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- USSD Description -->
                            <div>
                                <InputLabel for="description" value="Description" />
                                <textarea
                                    id="description"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model="form.description"
                                    rows="4"
                                    required
                                    placeholder="Describe what this USSD service does..."
                                ></textarea>
                                <InputError class="mt-2" :message="form.errors.description" />
                            </div>

                            <!-- USSD Pattern -->
                            <div>
                                <InputLabel for="pattern" value="USSD Pattern" />
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">*</span>
                                    </div>
                                    <TextInput
                                        id="pattern"
                                        type="text"
                                        class="pl-7 block w-full"
                                        v-model="form.pattern"
                                        required
                                        placeholder="e.g., 123# or 456*789#"
                                    />
                                </div>
                                <p class="mt-1 text-sm text-gray-500">
                                    Enter the USSD code pattern (e.g., 123#, 456*789#, etc.)
                                </p>
                                <InputError class="mt-2" :message="form.errors.pattern" />
                            </div>

                            <!-- Business Selection -->
                            <div>
                                <InputLabel for="business_id" value="Associated Business" />
                                <select
                                    id="business_id"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                    v-model="form.business_id"
                                    required
                                >
                                    <option value="">Select a business</option>
                                    <option
                                        v-for="business in businesses"
                                        :key="business.id"
                                        :value="business.id"
                                    >
                                        {{ business.business_name }}
                                        <span v-if="business.is_primary"> (Primary)</span>
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.business_id" />
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

                            <!-- Submit Button -->
                            <div class="flex items-center justify-end mt-6">
                                <Link
                                    :href="route('ussd.show', ussd.id)"
                                    class="mr-4 text-gray-600 hover:text-gray-900"
                                >
                                    Cancel
                                </Link>
                                <PrimaryButton
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Update USSD Service
                                </PrimaryButton>
                            </div>
                        </form>
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

const props = defineProps({
    ussd: {
        type: Object,
        required: true
    },
    businesses: {
        type: Array,
        required: true
    },
    otherUssds: {
        type: Array,
        default: () => []
    }
})

const initialAllocations = () => {
    const a = props.ussd.shared_code_allocations ?? props.ussd.sharedCodeAllocations
    if (Array.isArray(a) && a.length > 0) {
        return a.map((row) => ({
            option_value: String(row.option_value ?? ''),
            target_ussd_id: row.target_ussd_id ?? null,
            label: String(row.label ?? '')
        }))
    }
    return [{ option_value: '1', target_ussd_id: null, label: '' }]
}

const form = useForm({
    name: props.ussd.name,
    description: props.ussd.description,
    pattern: props.ussd.pattern,
    business_id: props.ussd.business_id,
    is_shared_gateway: Boolean(props.ussd.is_shared_gateway),
    is_single_shot: Boolean(props.ussd.is_single_shot),
    allocations: initialAllocations()
})

function addAllocation() {
    form.allocations.push({ option_value: '', target_ussd_id: null, label: '' })
}

function removeAllocation(index) {
    form.allocations.splice(index, 1)
}

const submit = () => {
    form.put(route('ussd.update', props.ussd.id))
}
</script> 