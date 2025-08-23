<template>
    <div class="min-h-screen bg-gray-100">
        <!-- Navigation -->
        <nav class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <!-- Logo -->
                        <div class="flex-shrink-0 flex items-center">
                            <Link :href="route('admin.dashboard')" class="text-xl font-bold text-gray-900">
                                USSD Admin
                            </Link>
                        </div>

                        <!-- Navigation Links -->
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <NavLink :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">
                                Dashboard
                            </NavLink>
                            <NavLink :href="route('admin.businesses')" :active="route().current('admin.businesses*')">
                                Businesses
                            </NavLink>
                            <NavLink :href="route('admin.users')" :active="route().current('admin.users*')">
                                Users
                            </NavLink>
                            <NavLink :href="route('admin.analytics')" :active="route().current('admin.analytics')">
                                Analytics
                            </NavLink>
                            <NavLink :href="route('admin.settings')" :active="route().current('admin.settings')">
                                Settings
                            </NavLink>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="flex items-center">
                        <Dropdown align="right" width="48">
                            <template #trigger>
                                <button class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                                    <div>{{ $page.props.auth.user.name }}</div>
                                    <div class="ml-1">
                                        <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </template>

                            <template #content>
                                <DropdownLink :href="route('dashboard')">
                                    User Dashboard
                                </DropdownLink>
                                <DropdownLink :href="route('profile.edit')">
                                    Profile
                                </DropdownLink>
                                <DropdownLink :href="route('admin.logout')" method="post" as="button">
                                    Log Out
                                </DropdownLink>
                            </template>
                        </Dropdown>
                    </div>
                </div>
            </div>

            <!-- Mobile menu -->
            <div class="sm:hidden">
                <div class="pt-2 pb-3 space-y-1">
                    <ResponsiveNavLink :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">
                        Dashboard
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('admin.businesses')" :active="route().current('admin.businesses*')">
                        Businesses
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('admin.users')" :active="route().current('admin.users*')">
                        Users
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('admin.analytics')" :active="route().current('admin.analytics')">
                        Analytics
                    </ResponsiveNavLink>
                    <ResponsiveNavLink :href="route('admin.settings')" :active="route().current('admin.settings')">
                        Settings
                    </ResponsiveNavLink>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="py-6">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <slot />
            </div>
        </main>
    </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3'
import NavLink from '@/Components/NavLink.vue'
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue'
import Dropdown from '@/Components/Dropdown.vue'
import DropdownLink from '@/Components/DropdownLink.vue'
</script>
