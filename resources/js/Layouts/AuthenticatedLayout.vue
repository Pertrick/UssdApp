<script setup>
import { ref, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { Link } from '@inertiajs/vue3';
import { handleFlashMessages } from '@/helpers/toast';

const sidebarOpen = ref(false);
const page = usePage();

// Handle flash messages when component mounts
onMounted(() => {
    handleFlashMessages(page.props);
});
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
                            </ul>
                        </li>
                        <li class="mt-auto">
                            <div class="bg-gray-800 rounded-lg p-3">
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
                                    <Link
                                        :href="route('logout')"
                                        method="post"
                                        as="button"
                                        class="block w-full text-left rounded-md px-3 py-2 text-base font-medium text-gray-400 hover:bg-gray-800 hover:text-white"
                                        @click="sidebarOpen = false"
                                    >
                                        Log Out
                                    </Link>
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
                    <DropdownLink
                        :href="route('logout')"
                        method="post"
                        as="button"
                    >
                        Log Out
                    </DropdownLink>
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
