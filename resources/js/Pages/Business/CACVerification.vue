<script setup>
import { ref, onMounted } from 'vue';
import { useBusinessRegistration } from '@/stores/useBusinessRegistration';
import { useForm } from '@inertiajs/vue3';
import { Form, Field, ErrorMessage } from 'vee-validate';
import { cacVerificationSchema } from '@/validation/schemas';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios';

const businessStore = useBusinessRegistration();
const fileInput = ref(null);
const isLoading = ref(false);

const form = useForm({
    cacNumber: '',
    businessType: '',
    registrationDate: '',
    cacDocument: null
});

onMounted(() => {
    businessStore.initializeFromLocalStorage();

    // Redirect if user hasn't completed previous steps
    if (!businessStore.canProceedToStep(2)) {
        window.location = route('business.register');
        return;
    }

    // If user has already completed this step, redirect to director info
    if (businessStore.maxStepReached > 2) {
        window.location = route('business.director-info');
        return;
    }

    // Load stored CAC data if available
    const { cacData } = businessStore;
    if (cacData) {
        form.cacNumber = cacData.cacNumber;
        form.businessType = cacData.businessType;
        form.registrationDate = cacData.registrationDate;
    }
});

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.cacDocument = file;
        businessStore.setCacDocument(file);
    }
};

const handleSubmit = (values, actions) => {
    // Set loading state
    isLoading.value = true;
    
    // Create FormData for file upload
    const formData = new FormData();
    formData.append('cacNumber', values.cacNumber);
    formData.append('businessType', values.businessType);
    formData.append('registrationDate', values.registrationDate);
    
    if (form.cacDocument) {
        formData.append('cacDocument', form.cacDocument);
    }

    // Use axios for file upload instead of Inertia
    axios.post(route('business.store-cac-info'), formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        const data = response.data.data;
        businessStore.setCacData({
            cacNumber: values.cacNumber,
            businessType: values.businessType,
            registrationDate: values.registrationDate,
            tempCacDocumentPath: data.tempCacDocumentPath
        });
        businessStore.setCurrentStep(3);
        window.location = route('business.director-info');
    })
    .catch(error => {
        if (error.response && error.response.data.errors) {
            actions.setErrors(error.response.data.errors);
        } else {
            actions.setErrors({ general: 'An error occurred. Please try again.' });
        }
    })
    .finally(() => {
        // Reset loading state
        isLoading.value = false;
    });
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
        <!-- Header Section -->
        <div class="relative overflow-hidden bg-white shadow-sm">
            <div class="absolute inset-0 bg-gradient-to-r from-green-600 to-emerald-600 opacity-5"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-lg mb-6">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-3">CAC Verification</h1>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        Provide your business registration details and upload your CAC certificate
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-4xl mx-auto">
                <!-- Progress Indicator -->
                <div class="mb-8">
                    <div class="flex items-center justify-center space-x-4">
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="ml-2 text-sm font-medium text-green-600">Registration</span>
                        </div>
                        <div class="w-12 h-0.5 bg-green-600"></div>
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">
                                2
                            </div>
                            <span class="ml-2 text-sm font-medium text-green-600">CAC Verification</span>
                        </div>
                        <div class="w-12 h-0.5 bg-gray-300"></div>
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-500 rounded-full text-sm font-medium">
                                3
                            </div>
                            <span class="ml-2 text-sm font-medium text-gray-500">Director Info</span>
                        </div>
                    </div>
                </div>

                <!-- CAC Form -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <Form @submit="handleSubmit" :validation-schema="cacVerificationSchema" v-slot="{ errors, isSubmitting }">
                        <div class="p-8">
                            <div class="mb-8">
                                <div class="flex items-center mb-6">
                                    <div class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-xl mr-4">
                                        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-900">CAC Information</h2>
                                        <p class="text-sm text-gray-600">Business registration details</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label for="cacNumber" class="block text-sm font-medium text-gray-700">
                                            CAC Registration Number <span class="text-red-500">*</span>
                                        </label>
                                        <Field 
                                            name="cacNumber" 
                                            type="text" 
                                            v-model="form.cacNumber"
                                            placeholder="Enter CAC number"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.cacNumber }"
                                        />
                                        <ErrorMessage name="cacNumber" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.cacNumber }}
                                        </ErrorMessage>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="businessType" class="block text-sm font-medium text-gray-700">
                                            Business Type <span class="text-red-500">*</span>
                                        </label>
                                        <Field 
                                            name="businessType" 
                                            as="select" 
                                            v-model="form.businessType"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.businessType }"
                                        >
                                            <option value="">Select Business Type</option>
                                            <option value="sole_proprietorship">Sole Proprietorship</option>
                                            <option value="partnership">Partnership</option>
                                            <option value="limited_liability">Limited Liability</option>
                                        </Field>
                                        <ErrorMessage name="businessType" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.businessType }}
                                        </ErrorMessage>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label for="registrationDate" class="block text-sm font-medium text-gray-700">
                                        Registration Date <span class="text-red-500">*</span>
                                    </label>
                                    <Field 
                                        name="registrationDate" 
                                        type="date" 
                                        v-model="form.registrationDate"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                        :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.registrationDate }"
                                    />
                                    <ErrorMessage name="registrationDate" class="text-sm text-red-600 flex items-center mt-1">
                                        <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ errors.registrationDate }}
                                    </ErrorMessage>
                                </div>

                                <div class="space-y-2">
                                    <label for="cacDocument" class="block text-sm font-medium text-gray-700">
                                        CAC Document <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <Field
                                            name="cacDocument"
                                            v-slot="{ errorMessage, handleChange }"
                                        >
                                            <input
                                                id="cacDocument"
                                                ref="fileInput"
                                                type="file"
                                                @change="(event) => { handleFileChange(event); handleChange(event); }"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 bg-gray-50 focus:bg-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100"
                                                accept=".pdf,.jpg,.jpeg,.png"
                                                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errorMessage }"
                                            />
                                        </Field>
                                    </div>
                                    <ErrorMessage name="cacDocument" class="text-sm text-red-600 flex items-center mt-1">
                                        <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ errors.cacDocument }}
                                    </ErrorMessage>
                                    <p class="mt-2 text-sm text-gray-500">Upload CAC certificate (PDF, JPG, PNG, max 5MB)</p>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex justify-between pt-6 border-t border-gray-200">
                                    <button
                                        type="button"
                                        :disabled="isSubmitting"
                                        @click="window.location = route('business.register')"
                                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Back
                                    </button>
                                    <button 
                                        type="submit" 
                                        :disabled="isSubmitting"
                                        class="px-8 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white rounded-xl font-semibold hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center shadow-lg min-w-[140px]"
                                    >
                                        <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ isSubmitting ? 'Processing...' : 'Continue' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </Form>
                </div>
            </div>
        </div>
    </div>
</template>
