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
    }
})

const form = useForm({
    name: props.ussd.name,
    description: props.ussd.description,
    pattern: props.ussd.pattern,
    business_id: props.ussd.business_id
})

const submit = () => {
    form.put(route('ussd.update', props.ussd.id))
}
</script> 