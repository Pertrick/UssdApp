<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Form, Field, ErrorMessage } from 'vee-validate';
import * as yup from 'yup';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

// Validation schema
const loginSchema = yup.object({
    email: yup.string().email('Please enter a valid email address').required('Email is required'),
    password: yup.string().required('Password is required'),
});

const submit = (values, actions) => {
    form.email = values.email;
    form.password = values.password;
    form.remember = values.remember;
    
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
        onError: (errors) => {
            actions.setErrors(errors);
        }
    });
};
</script>

<template>
    <Head title="Log in" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100">
        <!-- Header Section -->
        <div class="relative overflow-hidden bg-white shadow-sm">
            <div class="absolute inset-0 bg-gradient-to-r from-indigo-600 to-purple-600 opacity-5"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-lg mb-6">
                        <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                        </svg>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-3">Welcome Back</h1>
                    <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                        Sign in to your USSD business account and continue managing your services
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

                <!-- Login Form -->
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 p-8">
                    <Form @submit="submit" :validation-schema="loginSchema" v-slot="{ errors, isSubmitting }">
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

                            <!-- Password Section -->
                            <div class="space-y-2">
                                <label for="password" class="block text-sm font-medium text-gray-700">
                                    Password <span class="text-red-500">*</span>
                                </label>
                                <Field 
                                    name="password" 
                                    type="password" 
                                    v-model="form.password"
                                    placeholder="Enter your password"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all duration-200 bg-gray-50 focus:bg-white"
                                    :class="{ 'border-red-300 focus:ring-red-500 focus:border-red-500': errors.password }"
                                    autocomplete="current-password"
                                />
                                <ErrorMessage name="password" class="text-sm text-red-600 flex items-center mt-1">
                                    <svg class="h-4 w-4 mr-1 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    {{ errors.password }}
                                </ErrorMessage>
                            </div>

                            <!-- Remember Me & Forgot Password -->
                            <div class="flex items-center justify-between">
                                <label class="flex items-center">
                                    <Field 
                                        name="remember" 
                                        type="checkbox" 
                                        v-model="form.remember"
                                        class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                                    />
                                    <span class="ml-2 text-sm text-gray-600">Remember me</span>
                                </label>

                                <Link
                                    v-if="canResetPassword"
                                    :href="route('password.request')"
                                    class="text-sm font-medium text-indigo-600 hover:text-indigo-500 transition-colors"
                                >
                                    Forgot password?
                                </Link>
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
                                    {{ (isSubmitting || form.processing) ? 'Signing In...' : 'Sign In' }}
                                </button>
                            </div>

                            <!-- Register Link -->
                            <div class="text-center pt-4 border-t border-gray-200">
                                <p class="text-sm text-gray-600">
                                    Don't have an account? 
                                    <Link :href="route('business.register')" class="font-semibold text-indigo-600 hover:text-indigo-500 transition-colors">
                                        Create one here
                                    </Link>
                                </p>
                            </div>
                        </div>
                    </Form>
                </div>

                <!-- Footer -->
                <div class="text-center mt-8">
                    <p class="text-sm text-gray-500">
                        By signing in, you agree to our 
                        <a href="#" class="text-indigo-600 hover:text-indigo-500">Terms of Service</a> 
                        and 
                        <a href="#" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
