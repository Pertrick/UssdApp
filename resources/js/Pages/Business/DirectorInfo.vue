<script setup>
import { ref, onMounted } from 'vue';
import { useBusinessRegistration } from '@/stores/useBusinessRegistration';
import { useForm } from '@inertiajs/vue3';
import { Form, Field, ErrorMessage } from 'vee-validate';
import { directorInfoSchema } from '@/validation/schemas';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import axios from 'axios';

const businessStore = useBusinessRegistration();
const fileInput = ref(null);
const isLoading = ref(false);

const form = useForm({
    directorName: '',
    directorPhone: '',
    directorEmail: '',
    idType: '',
    idNumber: '',
    idDocument: null,
    cacData: null
});

onMounted(() => {
    businessStore.initializeFromLocalStorage();

    // Redirect if user hasn't completed previous steps
    if (!businessStore.canProceedToStep(3)) {
        window.location = route('business.cac-info');
        return;
    }

    // Load stored director data if available
    const { directorData } = businessStore;
    if (directorData) {
        form.directorName = directorData.directorName;
        form.directorPhone = directorData.directorPhone;
        form.directorEmail = directorData.directorEmail;
        form.idType = directorData.idType;
        form.idNumber = directorData.idNumber;
    }

    // Add CAC data from store
    form.cacData = JSON.stringify(businessStore.cacData);
});

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.idDocument = file;
        businessStore.setDirectorDocument(file);
    }
};

const handleSubmit = (values, actions) => {
    // Create FormData for file upload
    const formData = new FormData();
    formData.append('directorName', values.directorName);
    formData.append('directorPhone', values.directorPhone);
    formData.append('directorEmail', values.directorEmail);
    formData.append('idType', values.idType);
    formData.append('idNumber', values.idNumber);
    formData.append('cacData', form.cacData);
    
    if (form.idDocument) {
        formData.append('idDocument', form.idDocument);
    }

    // Use axios for file upload instead of Inertia
    axios.post(route('business.store-director-info'), formData, {
        headers: {
            'Content-Type': 'multipart/form-data',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => {
        businessStore.clearRegistrationData(); // Clear all stored data
        window.location = route('dashboard');
    })
    .catch(error => {
        if (error.response && error.response.data.errors) {
            actions.setErrors(error.response.data.errors);
        } else {
            actions.setErrors({ general: 'An error occurred. Please try again.' });
        }
    });
};
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
        <!-- Header Section -->
        <div class="relative overflow-hidden bg-white shadow-sm">
            <div class="absolute inset-0 bg-gradient-to-r from-purple-600 to-indigo-600 opacity-5"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-lg mb-6">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-3">Director Information</h1>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        Provide details about the business director and upload identification documents
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
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                            </div>
                            <span class="ml-2 text-sm font-medium text-green-600">CAC Verification</span>
                        </div>
                        <div class="w-12 h-0.5 bg-green-600"></div>
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-green-600 text-white rounded-full text-sm font-medium">
                                3
                            </div>
                            <span class="ml-2 text-sm font-medium text-green-600">Director Info</span>
                        </div>
                    </div>
                </div>

                <!-- Director Form -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
                    <Form @submit="handleSubmit" :validation-schema="directorInfoSchema" v-slot="{ errors, isSubmitting }">
                        <div class="p-8">
                            <div class="mb-8">
                                <div class="flex items-center mb-6">
                                    <div class="flex items-center justify-center w-10 h-10 bg-purple-100 rounded-xl mr-4">
                                        <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-900">Director Details</h2>
                                        <p class="text-sm text-gray-600">Personal and identification information</p>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Personal Information Section -->
                                <div class="mb-8">
                                    <div class="flex items-center mb-6">
                                        <div class="flex items-center justify-center w-8 h-8 bg-blue-100 rounded-xl mr-3">
                                            <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900">Personal Information</h3>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div class="space-y-2">
                                            <label for="directorName" class="block text-sm font-medium text-gray-700">
                                                Director's Name <span class="text-red-500">*</span>
                                            </label>
                                            <Field 
                                                name="directorName" 
                                                type="text" 
                                                v-model="form.directorName"
                                                placeholder="Enter director's full name"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.directorName }"
                                            />
                                            <ErrorMessage name="directorName" class="text-sm text-red-600 flex items-center mt-1">
                                                <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{ errors.directorName }}
                                            </ErrorMessage>
                                        </div>

                                        <div class="space-y-2">
                                            <label for="directorPhone" class="block text-sm font-medium text-gray-700">
                                                Director's Phone <span class="text-red-500">*</span>
                                            </label>
                                            <Field 
                                                name="directorPhone" 
                                                type="tel" 
                                                v-model="form.directorPhone"
                                                placeholder="+234 801 234 5678"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.directorPhone }"
                                            />
                                            <ErrorMessage name="directorPhone" class="text-sm text-red-600 flex items-center mt-1">
                                                <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{ errors.directorPhone }}
                                            </ErrorMessage>
                                        </div>
                                    </div>

                                    <div class="mt-6 space-y-2">
                                        <label for="directorEmail" class="block text-sm font-medium text-gray-700">
                                            Director's Email <span class="text-red-500">*</span>
                                        </label>
                                        <Field 
                                            name="directorEmail" 
                                            type="email" 
                                            v-model="form.directorEmail"
                                            placeholder="director@example.com"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.directorEmail }"
                                        />
                                        <ErrorMessage name="directorEmail" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.directorEmail }}
                                        </ErrorMessage>
                                    </div>
                                </div>

                                <!-- Identification Section -->
                                <div class="mb-8">
                                    <div class="flex items-center mb-6">
                                        <div class="flex items-center justify-center w-8 h-8 bg-green-100 rounded-xl mr-3">
                                            <svg class="h-4 w-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V4a2 2 0 114 0v2m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900">Identification</h3>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                        <div class="space-y-2">
                                            <label for="idType" class="block text-sm font-medium text-gray-700">
                                                ID Type <span class="text-red-500">*</span>
                                            </label>
                                            <Field 
                                                name="idType" 
                                                as="select" 
                                                v-model="form.idType"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.idType }"
                                            >
                                                <option value="">Select ID Type</option>
                                                <option value="national_id">National ID</option>
                                                <option value="drivers_license">Driver's License</option>
                                                <option value="international_passport">International Passport</option>
                                            </Field>
                                            <ErrorMessage name="idType" class="text-sm text-red-600 flex items-center mt-1">
                                                <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{ errors.idType }}
                                            </ErrorMessage>
                                        </div>

                                        <div class="space-y-2">
                                            <label for="idNumber" class="block text-sm font-medium text-gray-700">
                                                ID Number <span class="text-red-500">*</span>
                                            </label>
                                            <Field 
                                                name="idNumber" 
                                                type="text" 
                                                v-model="form.idNumber"
                                                placeholder="Enter ID number"
                                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                                :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.idNumber }"
                                            />
                                            <ErrorMessage name="idNumber" class="text-sm text-red-600 flex items-center mt-1">
                                                <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{ errors.idNumber }}
                                            </ErrorMessage>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="idDocument" class="block text-sm font-medium text-gray-700">
                                            ID Document <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <Field
                                                name="idDocument"
                                                v-slot="{ errorMessage, handleChange }"
                                            >
                                                <input
                                                    id="idDocument"
                                                    ref="fileInput"
                                                    type="file"
                                                    @change="(event) => { handleFileChange(event); handleChange(event); }"
                                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200 bg-gray-50 focus:bg-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100"
                                                    accept=".pdf,.jpg,.jpeg,.png"
                                                    :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errorMessage }"
                                                />
                                            </Field>
                                        </div>
                                        <ErrorMessage name="idDocument" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.idDocument }}
                                        </ErrorMessage>
                                        <p class="mt-2 text-sm text-gray-500">Upload ID document (PDF, JPG, PNG, max 5MB)</p>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="flex justify-between pt-6 border-t border-gray-200">
                                    <button
                                        type="button"
                                        :disabled="isSubmitting"
                                        @click="window.location = route('business.cac-info')"
                                        class="px-6 py-3 border border-gray-300 text-gray-700 rounded-xl font-medium hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    >
                                        Back
                                    </button>
                                    <button 
                                        type="submit" 
                                        :disabled="isSubmitting"
                                        class="px-8 py-3 bg-gradient-to-r from-purple-600 to-indigo-600 text-white rounded-xl font-semibold hover:from-purple-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center shadow-lg min-w-[180px]"
                                    >
                                        <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        {{ isSubmitting ? 'Processing...' : 'Complete Registration' }}
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
