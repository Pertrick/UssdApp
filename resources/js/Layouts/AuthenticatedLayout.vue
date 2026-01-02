<script setup>
import { ref, onMounted, watch } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';
import { handleFlashMessages } from '@/helpers/toast';

const sidebarOpen = ref(false);
const page = usePage();

// Handle flash messages when component mounts and when page props change
onMounted(() => {
    // Only handle flash messages if page props are available
    if (page.props) {
        handleFlashMessages(page.props);
    }
});

// Watch for changes in page props to handle flash messages after navigation
watch(() => page.props, (newProps) => {
    if (newProps) {
        handleFlashMessages(newProps);
    }
}, { deep: true, immediate: true });

const logout = () => {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    
    if (!csrfToken) {
        window.location.href = route('login');
        return;
    }
    
    window.axios.post(route('logout'), {}, {
        headers: {
            'X-CSRF-TOKEN': csrfToken,
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(() => {
        // Logout successful, redirect to login (controller should handle this, but just in case)
        window.location.href = route('login');
    })
    .catch((error) => {
        // Handle 419 CSRF token mismatch or expired session
        if (error.response?.status === 419 || error.response?.status === 401) {
            // Session expired - redirect to login page
            window.location.href = route('login');
        } else {
            // Other errors - still redirect to login since logout failed
            console.error('Logout error:', error);
            window.location.href = route('login');
        }
    });
};
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Sidebar for desktop -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            <!-- Sidebar component -->
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4">
                <!-- Logo -->
                <div class="flex h-16 shrink-0 items-center">
                    <Link :href="route('dashboard')" class="flex items-center">
                        <ApplicationLogo class="h-8 w-auto text-white" />
                        <span class="ml-2 text-xl font-bold text-white">USSD App</span>
                    </Link>
                </div>

                <!-- Navigation -->
                <nav class="flex flex-1 flex-col">
                    <ul role="list" class="flex flex-1 flex-col gap-y-7">
                        <li>
                            <ul role="list" class="-mx-2 space-y-1">
                                <li>
                                    <Link
                                        :href="route('dashboard')"
                                        :class="[
                                            route().current('dashboard')
                                                ? 'bg-gray-800 text-white'
                                                : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                            'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-medium transition-colors'
                                        ]"
                                    >
                                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                        </svg>
                                        Dashboard
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="route('ussd.index')"
                                        :class="[
                                            route().current('ussd.*')
                                                ? 'bg-gray-800 text-white'
                                                : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                            'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-medium transition-colors'
                                        ]"
                                    >
                                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        USSD Services
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="route('integration.index')"
                                        :class="[
                                            route().current('integration.*')
                                                ? 'bg-gray-800 text-white'
                                                : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                            'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-medium transition-colors'
                                        ]"
                                    >
                                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                        Integrations
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="route('environment.overview')"
                                        :class="[
                                            route().current('environment.overview')
                                                ? 'bg-gray-800 text-white'
                                                : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                            'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-medium transition-colors'
                                        ]"
                                    >
                                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                        Environment Management
                                    </Link>
                                </li>
                               
                                <li>
                                    <Link
                                        :href="route('analytics.dashboard')"
                                        :class="[
                                            route().current('analytics.*')
                                                ? 'bg-gray-800 text-white'
                                                : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                            'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-medium transition-colors'
                                        ]"
                                    >
                                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                        </svg>
                                        Analytics
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="route('billing.dashboard')"
                                        :class="[
                                            route().current('billing.*')
                                                ? 'bg-gray-800 text-white'
                                                : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                            'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-medium transition-colors'
                                        ]"
                                    >
                                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                        </svg>
                                        Billing
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="route('payment.history')"
                                        :class="[
                                            route().current('payment.*')
                                                ? 'bg-gray-800 text-white'
                                                : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                            'group flex gap-x-3 rounded-md p-2 text-sm leading-6 font-medium transition-colors'
                                        ]"
                                    >
                                        <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6-2.25h6m2.25-6H3.75m0 0a2.25 2.25 0 01-2.25-2.25V3.75m0 0A2.25 2.25 0 013.75 1.5h16.5a2.25 2.25 0 012.25 2.25v16.5a2.25 2.25 0 01-2.25 2.25H3.75a2.25 2.25 0 01-2.25-2.25V3.75z" />
                                        </svg>
                                        Payment History
                                    </Link>
                                </li>
                            </ul>
                        </li>
                        <li class="mt-auto">
                            <div class="bg-gray-800 rounded-lg p-3">
                                <div class="flex items-center gap-x-3 mb-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center">
                                        <span class="text-sm font-medium text-white">
                                            {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-white truncate">
                                            {{ $page.props.auth.user.name }}
                                        </p>
                                        <p class="text-xs text-gray-400 truncate">
                                            {{ $page.props.auth.user.email }}
                                        </p>
                                    </div>
                                </div>
                                <div class="space-y-1">
                                    <Link
                                        :href="route('profile.edit')"
                                        class="block rounded-md px-3 py-2 text-sm font-medium text-gray-400 hover:bg-gray-700 hover:text-white transition-colors"
                                    >
                                        Profile Settings
                                    </Link>
                                    <form @submit.prevent="logout" class="w-full">
                                        <button
                                            type="submit"
                                            class="block w-full text-left rounded-md px-3 py-2 text-sm font-medium text-red-400 hover:bg-red-900 hover:text-red-200 transition-colors"
                                        >
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                </svg>
                                                Logout
                                            </div>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Mobile menu -->
        <div class="lg:hidden">
            <div class="fixed inset-0 z-50" v-if="sidebarOpen">
                <div class="fixed inset-0 bg-gray-900/80" @click="sidebarOpen = false"></div>
                <div class="fixed inset-y-0 left-0 z-50 w-full overflow-y-auto bg-gray-900 px-6 py-6 sm:max-w-sm">
                    <div class="flex items-center justify-between">
                        <Link :href="route('dashboard')" class="flex items-center">
                            <ApplicationLogo class="h-8 w-auto text-white" />
                            <span class="ml-2 text-xl font-bold text-white">USSD App</span>
                        </Link>
                        <button
                            type="button"
                            class="-m-2.5 rounded-md p-2.5 text-gray-400"
                            @click="sidebarOpen = false"
                        >
                            <span class="sr-only">Close sidebar</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-6 flow-root">
                        <div class="-my-2 divide-y divide-gray-700">
                            <div class="space-y-2 py-6">
                                <Link
                                    :href="route('dashboard')"
                                    :class="[
                                        route().current('dashboard')
                                            ? 'bg-gray-800 text-white'
                                            : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                        'group flex gap-x-3 rounded-md px-3 py-2 text-base font-medium transition-colors'
                                    ]"
                                    @click="sidebarOpen = false"
                                >
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                                    </svg>
                                    Dashboard
                                </Link>
                                <Link
                                    :href="route('ussd.index')"
                                    :class="[
                                        route().current('ussd.*')
                                            ? 'bg-gray-800 text-white'
                                            : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                        'group flex gap-x-3 rounded-md px-3 py-2 text-base font-medium transition-colors'
                                    ]"
                                    @click="sidebarOpen = false"
                                >
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    USSD Services
                                </Link>
                                <Link
                                    :href="route('ussd.create')"
                                    :class="[
                                        route().current('ussd.create')
                                            ? 'bg-gray-800 text-white'
                                            : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                        'group flex gap-x-3 rounded-md px-3 py-2 text-base font-medium transition-colors'
                                    ]"
                                    @click="sidebarOpen = false"
                                >
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                    </svg>
                                    Create USSD
                                </Link>
                                <Link
                                    :href="route('analytics.dashboard')"
                                    :class="[
                                        route().current('analytics.*')
                                            ? 'bg-gray-800 text-white'
                                            : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                        'group flex gap-x-3 rounded-md px-3 py-2 text-base font-medium transition-colors'
                                    ]"
                                    @click="sidebarOpen = false"
                                >
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    Analytics
                                </Link>
                                <Link
                                    :href="route('billing.dashboard')"
                                    :class="[
                                        route().current('billing.*')
                                            ? 'bg-gray-800 text-white'
                                            : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                        'group flex gap-x-3 rounded-md px-3 py-2 text-base font-medium transition-colors'
                                    ]"
                                    @click="sidebarOpen = false"
                                >
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                    </svg>
                                    Billing
                                </Link>
                                <Link
                                    :href="route('payment.history')"
                                    :class="[
                                        route().current('payment.*')
                                            ? 'bg-gray-800 text-white'
                                            : 'text-gray-400 hover:text-white hover:bg-gray-800',
                                        'group flex gap-x-3 rounded-md px-3 py-2 text-base font-medium transition-colors'
                                    ]"
                                    @click="sidebarOpen = false"
                                >
                                    <svg class="h-6 w-6 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6-2.25h6m2.25-6H3.75m0 0a2.25 2.25 0 01-2.25-2.25V3.75m0 0A2.25 2.25 0 013.75 1.5h16.5a2.25 2.25 0 012.25 2.25v16.5a2.25 2.25 0 01-2.25 2.25H3.75a2.25 2.25 0 01-2.25-2.25V3.75z" />
                                    </svg>
                                    Payment History
                                </Link>
                            </div>
                            <div class="py-6">
                                <div class="flex items-center gap-x-3">
                                    <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center">
                                        <span class="text-sm font-medium text-white">
                                            {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                                        </span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-white truncate">
                                            {{ $page.props.auth.user.name }}
                                        </p>
                                        <p class="text-xs text-gray-400 truncate">
                                            {{ $page.props.auth.user.email }}
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-4 space-y-1">
                                    <Link
                                        :href="route('profile.edit')"
                                        class="block rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-800 hover:text-white"
                                        @click="sidebarOpen = false"
                                    >
                                        Profile
                                    </Link>
                                    <form @submit.prevent="logout" class="w-full">
                                        <button
                                            type="submit"
                                            class="block w-full text-left rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-800 hover:text-white"
                                            @click="sidebarOpen = false"
                                        >
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                                </svg>
                                                Log Out
                                            </div>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top bar for mobile -->
        <div class="sticky top-0 z-40 flex items-center gap-x-6 bg-white px-4 py-4 shadow-sm sm:px-6 lg:hidden">
            <button
                type="button"
                class="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                @click="sidebarOpen = true"
            >
                <span class="sr-only">Open sidebar</span>
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </button>
            <div class="flex-1 text-sm font-semibold leading-6 text-gray-900">Dashboard</div>
            <Dropdown align="right" width="48">
                <template #trigger>
                    <button class="flex items-center gap-x-4 text-sm font-medium leading-6 text-gray-900">
                        <span class="sr-only">Open user menu</span>
                        <div class="h-8 w-8 rounded-full bg-indigo-600 flex items-center justify-center">
                            <span class="text-sm font-medium text-white">
                                {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                            </span>
                        </div>
                        <span class="hidden lg:flex lg:items-center">
                            <span class="sr-only">Your profile</span>
                            <span aria-hidden="true">{{ $page.props.auth.user.name }}</span>
                        </span>
                    </button>
                </template>

                <template #content>
                    <DropdownLink :href="route('profile.edit')">
                        Profile
                    </DropdownLink>
                    <form @submit.prevent="logout" class="w-full">
                        <button
                            type="submit"
                            class="block w-full text-left rounded-md px-3 py-2 text-sm font-medium text-red-400 hover:bg-red-900 hover:text-red-200 transition-colors"
                        >
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                                Log Out
                            </div>
                        </button>
                    </form>
                </template>
            </Dropdown>
        </div>

        <!-- Main content -->
        <div class="lg:pl-72">
            <!-- Page Heading -->
            <header class="bg-white shadow-sm border-b border-gray-200" v-if="$slots.header">
                <div class="px-4 py-6 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="py-6">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
