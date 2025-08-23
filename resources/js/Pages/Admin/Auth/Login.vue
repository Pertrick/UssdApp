<template>
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="text-center">
                <h2 class="text-3xl font-bold text-gray-900">
                    Admin Login
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Access the USSD administration panel
                </p>
            </div>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            Email Address
                        </label>
                        <div class="mt-1">
                            <input
                                id="email"
                                v-model="form.email"
                                type="email"
                                required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                :class="{ 'border-red-500': errors.email }"
                                placeholder="admin@ussd.com"
                            />
                        </div>
                        <p v-if="errors.email" class="mt-2 text-sm text-red-600">
                            {{ errors.email }}
                        </p>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">
                            Password
                        </label>
                        <div class="mt-1">
                            <input
                                id="password"
                                v-model="form.password"
                                type="password"
                                required
                                class="appearance-none block w-full px-3 py-2 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                :class="{ 'border-red-500': errors.password }"
                                placeholder="••••••••"
                            />
                        </div>
                        <p v-if="errors.password" class="mt-2 text-sm text-red-600">
                            {{ errors.password }}
                        </p>
                    </div>

                    <div>
                        <button
                            type="submit"
                            :disabled="processing"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <svg v-if="processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            {{ processing ? 'Signing in...' : 'Sign in' }}
                        </button>
                    </div>
                </form>

                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300" />
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">Demo Credentials</span>
                        </div>
                    </div>

                    <div class="mt-6 bg-gray-50 p-4 rounded-md">
                        <div class="text-sm text-gray-600 space-y-1">
                            <div><strong>Super Admin:</strong> admin@ussd.com / password</div>
                            <div><strong>System Admin:</strong> system@ussd.com / password</div>
                            <div><strong>Moderator:</strong> moderator@ussd.com / password</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3'
import { ref } from 'vue'

const processing = ref(false)

const form = useForm({
    email: '',
    password: '',
})

const props = defineProps({
    errors: Object,
})

const submit = () => {
    processing.value = true
    
    form.post(route('admin.login'), {
        onFinish: () => {
            processing.value = false
        },
    })
}
</script>

