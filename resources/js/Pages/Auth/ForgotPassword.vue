<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Form, Field, ErrorMessage } from 'vee-validate';
import * as yup from 'yup';

defineProps({
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
});

// Validation schema
const forgotPasswordSchema = yup.object({
    email: yup.string().email('Please enter a valid email address').required('Email is required'),
});

const submit = (values, actions) => {
    form.email = values.email;
    
    form.post(route('password.email'), {
        onError: (errors) => {
            actions.setErrors(errors);
        }
    });
};
</script>

<template>
    <Head title="Forgot Password" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
        <!-- Header Section -->
        <div class="relative overflow-hidden bg-white shadow-sm">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 opacity-5"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg mb-6">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-3">Reset Your Password</h1>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        Enter your email address and we'll send you a link to reset your password
                    </p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="py-12 px-4 sm:px-6 lg:px-8">
            <div class="max-w-md mx-auto">
                <!-- Status Message -->
                <div v-if="status" class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl">
                    <div class="flex items-center">
                        <svg class="h-5 w-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-sm font-medium text-green-800">{{ status }}</span>
                    </div>
                </div>

                <!-- Forgot Password Form -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
                    <Form @submit="submit" :validation-schema="forgotPasswordSchema" v-slot="{ errors, isSubmitting }">
                        <div class="space-y-6">
                            <!-- Email Section -->
                            <div class="space-y-2">
                                <label for="email" class="block text-sm font-medium text-gray-700">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <Field 
                                    name="email" 
                                    type="email" 
                                    v-model="form.email"
                                    placeholder="your.email@example.com"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                    :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.email }"
                                    autocomplete="username"
                                    autofocus
                                />
                                <ErrorMessage name="email" class="text-sm text-red-600 flex items-center mt-1">
                                    <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ errors.email }}
                                </ErrorMessage>
                            </div>

                            <!-- Submit Button -->
                            <div class="pt-4">
                                <button 
                                    type="submit" 
                                    :disabled="isSubmitting || form.processing"
                                    class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-3 px-6 rounded-xl font-semibold hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed transform hover:scale-[1.02] active:scale-[0.98] flex items-center justify-center shadow-lg"
                                >
                                    <svg v-if="isSubmitting || form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    {{ (isSubmitting || form.processing) ? 'Sending Reset Link...' : 'Send Password Reset Link' }}
                                </button>
                            </div>

                            <!-- Back to Login Link -->
                            <div class="text-center pt-4 border-t border-gray-200">
                                <p class="text-sm text-gray-600">
                                    Remember your password? 
                                    <Link :href="route('login')" class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
                                        Sign in here
                                    </Link>
                                </p>
                            </div>
                        </div>
                    </Form>
                </div>

                <!-- Footer -->
                <div class="text-center mt-8">
                    <p class="text-sm text-gray-500">
                        Need help? Contact our 
                        <a href="#" class="text-indigo-600 hover:text-indigo-500">support team</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
