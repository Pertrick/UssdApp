<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
        <!-- Header Section -->
        <div class="relative overflow-hidden bg-white shadow-sm">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 opacity-5"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg mb-6">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-3">Business Registration</h1>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        Create your USSD business account and start providing services to your customers in minutes
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
                            <div class="flex items-center justify-center w-8 h-8 bg-indigo-600 text-white rounded-full text-sm font-medium">
                                1
                            </div>
                            <span class="ml-2 text-sm font-medium text-indigo-600">Registration</span>
                        </div>
                        <div class="w-12 h-0.5 bg-gray-300"></div>
                        <div class="flex items-center">
                            <div class="flex items-center justify-center w-8 h-8 bg-gray-300 text-gray-500 rounded-full text-sm font-medium">
                                2
                            </div>
                            <span class="ml-2 text-sm font-medium text-gray-500">CAC Verification</span>
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

                <!-- Registration Form -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
                    <Form @submit="handleSubmit" :validation-schema="businessRegistrationSchema" v-slot="{ errors, isSubmitting }">
                        <div class="space-y-8">
                            <!-- Personal Information Section -->
                            <div class="mb-10">
                                <div class="flex items-center mb-6">
                                    <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-xl mr-4">
                                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-900">Personal Information</h2>
                                        <p class="text-sm text-gray-600">Tell us about yourself</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label for="name" class="block text-sm font-medium text-gray-700">
                                            Your Name <span class="text-red-500">*</span>
                                        </label>
                                        <Field 
                                            name="name" 
                                            type="text" 
                                            v-model="form.name"
                                            placeholder="Enter your full name"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.name }"
                                        />
                                        <ErrorMessage name="name" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.name }}
                                        </ErrorMessage>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="email" class="block text-sm font-medium text-gray-700">
                                            Your Email <span class="text-red-500">*</span>
                                        </label>
                                        <Field 
                                            name="email" 
                                            type="email" 
                                            v-model="form.email"
                                            placeholder="your.email@example.com"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.email }"
                                        />
                                        <ErrorMessage name="email" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.email }}
                                        </ErrorMessage>
                                    </div>
                                </div>
                            </div>

                            <!-- Business Information Section -->
                            <div class="mb-10">
                                <div class="flex items-center mb-6">
                                    <div class="flex items-center justify-center w-10 h-10 bg-green-100 rounded-xl mr-4">
                                        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-900">Business Information</h2>
                                        <p class="text-sm text-gray-600">Details about your business</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="space-y-2">
                                        <label for="business_name" class="block text-sm font-medium text-gray-700">
                                            Business Name <span class="text-red-500">*</span>
                                        </label>
                                        <Field 
                                            name="business_name" 
                                            type="text" 
                                            v-model="form.business_name"
                                            placeholder="Enter your business name"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.business_name }"
                                        />
                                        <ErrorMessage name="business_name" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.business_name }}
                                        </ErrorMessage>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="business_email" class="block text-sm font-medium text-gray-700">
                                            Business Email <span class="text-red-500">*</span>
                                        </label>
                                        <Field 
                                            name="business_email" 
                                            type="email" 
                                            v-model="form.business_email"
                                            placeholder="business@example.com"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.business_email }"
                                        />
                                        <ErrorMessage name="business_email" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.business_email }}
                                        </ErrorMessage>
                                    </div>
                                </div>

                                <div class="space-y-2 mb-6">
                                    <label for="phone" class="block text-sm font-medium text-gray-700">
                                        Phone Number <span class="text-red-500">*</span>
                                    </label>
                                    <Field 
                                        name="phone" 
                                        type="tel" 
                                        v-model="form.phone"
                                        placeholder="+234 801 234 5678"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                        :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.phone }"
                                    />
                                    <ErrorMessage name="phone" class="text-sm text-red-600 flex items-center mt-1">
                                        <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ errors.phone }}
                                    </ErrorMessage>
                                </div>
                            </div>

                            <!-- Location Information Section -->
                            <div class="mb-10">
                                <div class="flex items-center mb-6">
                                    <div class="flex items-center justify-center w-10 h-10 bg-purple-100 rounded-xl mr-4">
                                        <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-900">Location Information</h2>
                                        <p class="text-sm text-gray-600">Where is your business located?</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                                    <div class="space-y-2">
                                        <label for="state" class="block text-sm font-medium text-gray-700">
                                            State <span class="text-red-500">*</span>
                                        </label>
                                        <Field 
                                            name="state" 
                                            type="text" 
                                            v-model="form.state"
                                            placeholder="Lagos"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.state }"
                                        />
                                        <ErrorMessage name="state" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.state }}
                                        </ErrorMessage>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="city" class="block text-sm font-medium text-gray-700">
                                            City <span class="text-red-500">*</span>
                                        </label>
                                        <Field 
                                            name="city" 
                                            type="text" 
                                            v-model="form.city"
                                            placeholder="Victoria Island"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.city }"
                                        />
                                        <ErrorMessage name="city" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.city }}
                                        </ErrorMessage>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label for="address" class="block text-sm font-medium text-gray-700">
                                        Business Address <span class="text-red-500">*</span>
                                    </label>
                                    <Field 
                                        name="address" 
                                        as="textarea" 
                                        v-model="form.address"
                                        rows="3"
                                        placeholder="Enter your complete business address"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white resize-none"
                                        :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.address }"
                                    />
                                    <ErrorMessage name="address" class="text-sm text-red-600 flex items-center mt-1">
                                        <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ errors.address }}
                                    </ErrorMessage>
                                </div>
                            </div>

                            <!-- Security Section -->
                            <div class="mb-8">
                                <div class="flex items-center mb-6">
                                    <div class="flex items-center justify-center w-10 h-10 bg-orange-100 rounded-xl mr-4">
                                        <svg class="h-5 w-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h2 class="text-xl font-semibold text-gray-900">Account Security</h2>
                                        <p class="text-sm text-gray-600">Create a strong password</p>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-2">
                                        <label for="password" class="block text-sm font-medium text-gray-700">
                                            Password <span class="text-red-500">*</span>
                                        </label>
                                        <Field 
                                            name="password" 
                                            type="password" 
                                            v-model="form.password"
                                            placeholder="Create a strong password"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.password }"
                                        />
                                        <ErrorMessage name="password" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.password }}
                                        </ErrorMessage>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                                            Confirm Password <span class="text-red-500">*</span>
                                        </label>
                                        <Field 
                                            name="password_confirmation" 
                                            type="password" 
                                            v-model="form.password_confirmation"
                                            placeholder="Confirm your password"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                            :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.password_confirmation }"
                                        />
                                        <ErrorMessage name="password_confirmation" class="text-sm text-red-600 flex items-center mt-1">
                                            <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ errors.password_confirmation }}
                                        </ErrorMessage>
                                    </div>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-6 border-t border-gray-200">
                                <button 
                                    type="submit" 
                                    :disabled="isSubmitting"
                                    class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-4 px-6 rounded-xl font-semibold hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center shadow-lg"
                                >
                                    <svg v-if="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ isSubmitting ? 'Creating Account...' : 'Create Business Account' }}
                                </button>
                            </div>

                            <!-- Login Link -->
                            <div class="text-center mt-6">
                                <p class="text-sm text-gray-600">
                                    Already have an account? 
                                    <a href="/login" class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
                                        Sign in here
                                    </a>
                                </p>
                            </div>
                        </div>
                    </Form>
                </div>

                <!-- Footer -->
                <div class="text-center mt-8">
                    <p class="text-sm text-gray-500">
                        By creating an account, you agree to our 
                        <a href="#" class="text-indigo-600 hover:text-indigo-500">Terms of Service</a> 
                        and 
                        <a href="#" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { onMounted } from 'vue';
import { useBusinessRegistration } from '@/stores/useBusinessRegistration';
import { useForm } from '@inertiajs/vue3';
import { Form, Field, ErrorMessage } from 'vee-validate';
import { businessRegistrationSchema } from '@/validation/schemas';

const businessStore = useBusinessRegistration();

const form = useForm({
    // User fields
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    
    // Business fields
    business_name: '',
    business_email: '',
    phone: '',
    state: '',
    city: '',
    address: '',
});

// Initialize form with stored data
onMounted(() => {
    businessStore.initializeFromLocalStorage();
    
    // Load stored data if available (but don't redirect automatically)
    const { businessData } = businessStore;
    if (businessData) {
        form.name = businessData.name || '';
        form.email = businessData.email || '';
        form.business_name = businessData.business_name || '';
        form.business_email = businessData.business_email || '';
        form.phone = businessData.phone || '';
        form.state = businessData.state || '';
        form.city = businessData.city || '';
        form.address = businessData.address || '';
    }
});

const handleSubmit = (values, { setSubmitting }) => {
    console.log('Form submitted with values:', values);
    
    // First save to store before server submission
    businessStore.setBusinessData({
        name: values.name,
        email: values.email,
        business_name: values.business_name,
        business_email: values.business_email,
        phone: values.phone,
        state: values.state,
        city: values.city,
        address: values.address,
    });

    // Update form values
    Object.keys(values).forEach(key => {
        if (form[key] !== undefined) {
            form[key] = values[key];
        }
    });

    // Now submit with Inertia
    form.post(route('business.store'), {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            console.log('Form submitted successfully');
            businessStore.setCurrentStep(2);
            window.location = route('business.cac-info');
        },
        onError: (errors) => {
            console.error('Submission errors:', errors);
            setSubmitting(false);
        },
        onFinish: () => {
            setSubmitting(false);
        }
    });
};
</script>
